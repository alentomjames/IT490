<?php

require_once '../webserver/vendor/autoload.php';
require_once '../webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

use PhpAmqpLib\Message\AMQPMessage;

if ($argc < 4) {
    echo "Usage: php script.php <bundleName> <status> <machine>\n";
    echo "Example: php script.php login pass feDev\n";
    exit(1);
}

$bundleName = $argv[1]; // Bundle name
$status = $argv[2];
$machine = $argv[3];

function rollbackFunction($bundleName, $versionNumber, $machine, $user)
{
    try {
        // if (strpos($machine, 'fe') === 0) {
        //     $repoPath = "var/www/it490";
        // } else {
        //     $repoPath = "home/{$user}/git/IT490";
        // }
        $repoPath = "var/log/current/{$bundleName}";
        $badBundle = "var/log/current/{$bundleName}_" . ($versionNumber + 1);
        $rollbackPath = "var/log/current/{$bundleName}_{$versionNumber}.zip";
        if (!file_exists($repoPath)) {
            throw new Exception("Current path does not exist");
        }

        // Remove current files
        if (file_exists($repoPath)) {
            exec("rm -rf $repoPath");
        }

        // Unzip the archived version to the current path
        exec("unzip $rollbackPath -d $repoPath");

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
        $user = 'alen';
        break;
    case 'beQA':
        $returnQueue = 'deployToBeQA';
        $target_vm = '172.29.87.41';
        $user = 'ppetroski';
        break;
    case 'dmzQA':
        $returnQueue = 'deployToDmzQA';
        $target_vm = '172.29.63.70';
        $user = 'al643';
        break;
    case 'feProd':
        $returnQueue = 'deployToFeProd';
        $target_vm = '172.29.87.169';
        $user = 'alen';
        break;
    case 'beProd':
        $returnQueue = 'deployToBeProd';
        $target_vm = '172.29.87.41';
        $user = 'ppetroski';
        break;
    case 'dmzProd':
        $returnQueue = 'deployToDmzProd';
        $target_vm = '172.29.87.70';
        $user = 'al643';
        break;
    default:
        echo "Invalid machine\n";
        exit(1);
}
// Create the message
$data = json_encode([
    'type' => 'status_update',
    'bundle' => $bundleName,
    'status' => $status,
    'return_queue' => $returnQueue,
    'target_vm' => $target_vm,
    'user' => $user

]);
$msg = new AMQPMessage($data, ['delivery_mode' => 2]);

// Send the message to the queue
$channel->basic_publish($msg, '', 'toDeploy');
echo " [x] Sent '$bundleName' with status '$status'\n";

if ($status === 'fail') {
    // Listen for a message on the return queue
    $channel->queue_declare($returnQueue, false, true, false, false);

    $callback = function ($msg) use ($bundleName, $channel, $machine, $user) {
        $data = json_decode($msg->body, true);
        $sendStatus = $data['status'];
        $bundleName = $data['bundle'];
        $previousVersion = $data['previous_version'];
        echo " [x] Received previous good version: $previousVersion\n";

        if ($sendStatus === 'sent') {
            echo " [x] Received '$bundleName' with status '$sendStatus'\n";
            echo " [x] Rolling back to version $previousVersion\n";

            try {
                rollbackFunction($bundleName, $previousVersion, $machine, $user);
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