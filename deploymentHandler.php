<?php

require_once 'db_connection.php';
require_once 'rmq_connection.php';
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once 'deployment/update.php';

list($connection, $channel) = getRabbit();

$channel->queue_declare('toDeployment', false, true, false, false);

$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type'];
    $queueName = $data['queue'];

    if ($type === 'deploy_update') {
        $targetVM = $data['target_vm'];
        $bundle = $data['bundle_name'];
        $response = deployUpdate($targetVM, $bundle);
        echo "Deploy update request received for VM: $targetVM\n";
    } elseif ($type === 'rollback_update') {
        $targetVM = $data['target_vm'];
        $version = $data['version'];
        $response = rollbackUpdate($targetVM, $version);
        echo "Rollback update request received for VM: $targetVM, version: $version\n";
    } elseif ($type === 'status_update') {
        $bundle = $data['bundle_name'];
        $updateStatus = $data['status'];
        $response = logUpdate($bundle, $updateStatus);
        echo "Log update request received\n";
    } else {
        echo "Received unknown deployment command or missing required data fields\n";
        return;
    }

    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, 'directExchange', $queueName);
};

$channel->basic_consume('toDeployment', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

closeRabbit($connection, $channel);
