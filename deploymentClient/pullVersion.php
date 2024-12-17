<?php
require_once '../rabbitmq_connection.php'; // RabbitMQ connection

require_once '../vendor/autoload.php';
//require_once '/var/www/it490/vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($argc < 3) {
    echo "Please type it in the following format: php deployVersion.php [bundle] [machine]\n";
    exit(1);
}

$bundleName = $argv[1];
$machineName = $argv[2];

list($connection, $channel) = getDeployRabbit();
switch ($machineName) {
    case 'beDev':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToBeDev';
        $user = 'ppetroski';

        break;
    case 'beQA':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToBeQA';
        $user = 'ppetroski';
        break;
    case 'beProd':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToBeProd';
        $user = 'ppetroski';
        break;
    case 'feDev':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToFeDev';
        $user = 'alen';

        break;
    case 'feQA':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToFeQA';
        $user = 'alen';

        break;
    case 'feProd':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToFeProd';
        $user = 'alen';

        break;
    case 'dmzDev':
        $queueName = 'toDeploy';
        $user = 'al643';

        $responseQueue = 'deployToDmzDev';
        break;
    case 'dmzQA':
        $queueName = 'toDeploy';
        $user = 'al643';

        $responseQueue = 'deployToDmzQA';
        break;
    case 'dmzProd':
        $queueName = 'toDeploy';
        $responseQueue = 'deployToDmzProd';
        $user = 'al643';

        break;
    default:
        echo "Invalid machine name '$machineName'.\n";
        exit(1);
}
// Declare the queue
$channel->queue_declare($queueName, false, true, false, false);


// Declare the request queue
$requestQueue = 'toDeploy';
$channel->queue_declare($requestQueue, false, true, false, false);

// Prepare the request message
$requestData = json_encode([
    'type' => 'pull_version',
    'bundle' => $bundleName,
    'queue' => $responseQueue
]);

$requestMsg = new AMQPMessage($requestData, ['delivery_mode' => 2]);

// Send the request to the deployment machine
$channel->basic_publish($requestMsg, 'directExchange', $requestQueue);
echo " Sent pull request for bundle '$bundleName' to deployment.\n";

$bundlePath = "/var/log/current/";

// Remove the existing bundle file or folder starting with $bundleName_
foreach (glob($bundlePath . $bundleName . '_*') as $file) {
    $command = "rm -r " . escapeshellarg($file);
    exec($command, $output, $returnVar);
    if ($returnVar === 0) {
        echo "Removed existing bundle at '$file'.\n";
    } else {
        echo "Failed to remove existing bundle at '$file'.\n";
        exit(1);
    }
}

// Declare the response queue
$channel->queue_declare($responseQueue, false, true, false, false);

// Callback function to wait for the 'sent' message from deployment
$callback = function ($msg) use ($bundleName, $channel, $bundlePath, $machineName, $user) {
    $data = json_decode($msg->body, true);


    if (isset($data['type']) && $data['type'] == 'sent' && $data['bundle'] == $bundleName) {
        echo " Received 'sent' confirmation for bundle '$bundleName'.\n";

        // Wait for the deployment machine to SCP the new version
        echo "Waiting for the new version to be deployed...\n";

        $maxAttempts = 10;
        $attempt = 0;
        $zipFilePath = null;

        while ($attempt < $maxAttempts) {
            $zipFiles = glob($bundlePath . $bundleName . '_*.zip');
            if (!empty($zipFiles)) {
                $zipFilePath = $zipFiles[0];
                break;
            }
            // If not found, wait for a second and try again
            sleep(1);
            $attempt++;
        }

        if ($zipFilePath === null) {
            echo "No zip files found for pattern {$bundlePath}{$bundleName}_*.zip after waiting.\n";
            exit(1);
        }

        $bundleFileName = basename($zipFilePath);

        // Assuming SCP is done, unzip the new version
        $zipFilePath = $bundlePath . $bundleFileName;

        if (file_exists($zipFilePath) && pathinfo($zipFilePath, PATHINFO_EXTENSION) == 'zip') {
            $zip = new ZipArchive;
            if ($zip->open($zipFilePath) === TRUE) {
                $zip->extractTo($bundlePath);
                $zip->close();
                echo "Unzipped bundle '$bundleFileName' successfully.\n";
            } else {
                echo "Failed to unzip bundle '$bundleFileName'.\n";
                exit(1);
            }
        } else {
            echo "Expected zip file '$zipFilePath' not found.\n";
            exit(1);
        }
        $unzipPath = $bundlePath . $bundleName;

        if (strpos($machineName, 'fe') === 0) {
            $repoPath = "/var/www/it490";
            $bundlePath = "/var/www/it490/{$bundleName}";
        } else {
            $repoPath = "/home/{$user}/git/IT490";
            $bundlePath = "/home/{$user}/git/IT490/{$bundleName}";
        }
        // Remove current files
        if (file_exists($repoPath)) {
            exec("rm -rf $bundlePath");
        } else {
            echo "Repo path does not exist\n";
        }

        // Copy the contents of the unzipped folder to the repository path
        exec("cp -r $unzipPath/* $repoPath");
        $msg->ack();
        $channel->basic_cancel($msg->delivery_info['consumer_tag']);
    } else {
        $msg->ack();
    }
};

$channel->basic_consume($responseQueue, '', false, false, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

closeRabbit($connection, $channel);
?>