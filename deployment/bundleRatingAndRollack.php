<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

use PhpAmqpLib\Message\AMQPMessage;

if ($argc < 4) {
    echo "Usage: php script.php <bundleName> <status> <machine>\n";
    echo "Example: php script.php login good feDev\n";
    exit(1);
}

$bundleName = $argv[1];
$status = $argv[2];
$machine = $argv[3];

function rollbackFunction($bundleName, $versionNumber, $machine)
{
    try {
        if (strpos($machine, 'fe') === 0) {
            $repoPath = "var/www/it490/{$bundleName}_{$versionNumber}.zip";
        } else {
            $repoPath = "home/ppetroski/git/IT490/{$bundleName}_{$versionNumber}.zip";
        }

        if (!file_exists($repoPath)) {
            throw new Exception("Archived version $versionNumber of $bundleName does not exist.");
        }

        // Remove current files
        if (file_exists($repoPath)) {
            $jsonFilePath = "bundles.json"; // Path to the JSON file containing bundle names and files

            if (!file_exists($jsonFilePath)) {
                throw new Exception("JSON file with bundle names and files does not exist.");
            }

            $jsonData = file_get_contents($jsonFilePath);
            $bundleFiles = json_decode($jsonData, true);

            if (isset($bundleFiles[$bundleName])) {
                foreach ($bundleFiles[$bundleName] as $file) {
                    if (file_exists($file)) {
                        exec("rm -rf $file");
                    }
                }
            } else {
                throw new Exception("No files found for bundle $bundleName in the JSON file.");
            }
        }

        // Unzip the archived version to the current path
        exec("unzip $repoPath .");

        return json_encode([
            'status' => 'success',
            'message' => "Successfully rolled back to version $versionNumber of $bundleName"
        ]);
    } catch (Exception $e) {
        return json_encode([
            'status' => 'failure',
            'message' => "Rollback failed",
            'error' => $e->getMessage()
        ]);
    }
}

list($connection, $channel) = getDeployRabbit();

// Declare the queue
$channel->queue_declare('toDeploy', false, true, false, false);

switch ($machine) {
    case 'feQA':
        $returnQueue = 'deployToFeQA';
        $target_vm = '172.29.87.169';
        break;
    case 'beQA':
        $returnQueue = 'deployToBeQA';
        $target_vm = '172.29.87.41';
        break;
    case 'dmzQA':
        $returnQueue = 'deployToDmzQA';
        $target_vm = '172.29.63.70';
        break;
    case 'feProd':
        $returnQueue = 'deployToFeProd';
        $target_vm = '172.29.87.169';
        break;
    case 'beProd':
        $returnQueue = 'deployToBeProd';
        $target_vm = '172.29.87.41';
        break;
    case 'dmzProd':
        $returnQueue = 'deployToDmzProd';
        $target_vm = '172.29.87.70';
        break;
    default:
        echo "Invalid machine\n";
        exit(1);
}
// Create the message
$data = json_encode([

    'bundle' => $bundleName,
    'status' => $status,
    'return_queue' => $returnQueue,
    'target_vm' => $target_vm
]);
$msg = new AMQPMessage($data, ['delivery_mode' => 2]);

// Send the message to the queue
$channel->basic_publish($msg, '', 'toDeploy');
echo " [x] Sent '$bundleName' with status '$status'\n";

if ($status === 'bad') {
    // Listen for a message on the return queue
    $channel->queue_declare($returnQueue, false, true, false, false);

    $callback = function ($msg) use ($bundleName, $channel, $machine) {
        $data = json_decode($msg->body, true);
        $sendStatus = $data['status'];
        $bundleName = $data['bundle'];
        $previousVersion = $data['previous_version'];
        echo " [x] Received previous good version: $previousVersion\n";

        if ($sendStatus === 'sent') {
            echo " [x] Received '$bundleName' with status '$sendStatus'\n";
            echo " [x] Rolling back to version $previousVersion\n";

            try {
                rollbackFunction($bundleName, $previousVersion, $machine);
            } catch (Exception $e) {
                echo " [x] Rollback failed: " . $e->getMessage() . "\n";
            }
        } else {
            echo " [x] Received '$bundleName' with status '$sendStatus'\n";
            echo " [x] No previous good version found, cannot rollback\n";
        }

        $msg->ack();
        // Stop consuming after receiving the message
        $channel->basic_cancel($msg->delivery_info['consumer_tag']);
    };

    $channel->basic_consume($returnQueue, '', false, false, false, false, $callback);

    // Wait for the message
    while ($channel->is_consuming()) {
        $channel->wait();
    }
}
// Close the RabbitMQ connection
closeRabbit($connection, $channel);