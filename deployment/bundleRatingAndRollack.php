<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

use PhpAmqpLib\Message\AMQPMessage;

if ($argc < 4) {
    echo "Usage: php script.php <bundleName> <status> <machine>\n";
    echo "Example: php script.php login good QADev\n";
    exit(1);
}

$bundleName = $argv[1];
$status = $argv[2];
$machine = $argv[3];

list($connection, $channel) = getDeployRabbit();

// Declare the queue
$channel->queue_declare('toDeploy', false, true, false, false);

switch ($machine) {
    case 'feQA':
        $returnQueue = 'deployToFeQA';
        break;
    case 'BeQA':
        $returnQueue = 'deployToBeQA';
        break;
    case 'DmzQA':
        $returnQueue = 'deployToDmzQA';
        break;
    case 'feProd':
        $returnQueue = 'deployToFeProd';
        break;
    case 'BeProd':
        $returnQueue = 'deployToBeProd';
        break;
    case 'DmzProd':
        $returnQueue = 'deployToDmzProd';
        break;
    default:
        echo "Invalid machine\n";
        exit(1);
}
// Create the message
$data = json_encode([
    'bundle' => $bundleName,
    'status' => $status,
    'return_queue' => $returnQueue
]);
$msg = new AMQPMessage($data, ['delivery_mode' => 2]);

// Send the message to the queue
$channel->basic_publish($msg, '', 'toDeploy');
echo " [x] Sent '$bundleName' with status '$status'\n";

if ($status === 'bad') {
    // Listen for a message on the return queue
    $channel->queue_declare($returnQueue, false, true, false, false);

    $callback = function ($msg) use ($bundleName, $channel) {
        $data = json_decode($msg->body, true);
        $sendStatus = $data['status'];
        $bundleName = $data['bundle'];
        $previousVersion = $data['previous_version'];
        echo " [x] Received previous good version: $previousVersion\n";

        if ($sendStatus === 'sent') {
            echo " [x] Received '$bundleName' with status '$sendStatus'\n";
            echo " [x] Rolling back to version $previousVersion\n";
            rollbackUpdate($bundleName, $previousVersion);
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