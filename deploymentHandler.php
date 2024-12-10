<?php

require_once 'db_connection.php';
require_once 'webserver/rabbitmq_connection.php';
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once 'deployment/deployment_functions.php';

list($connection, $channel) = getRabbit();

$channel->queue_declare('toDeploy', false, true, false, false);

$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type'];
    $queueName = $data['queue'];
    echo "hit callback\n";
    if ($type === 'store_package') {
        $targetVMiP = $data['target_vm'];
        $bundleName = $data['bundle_name'];
        $versionNumber = $data['version_number'];
        $filePath = $data['file_path'];
        $response = storePackage($targetVMiP, $bundleName, $versionNumber, $filePath);
        echo "Deploy update request received for VM: $targetVMiP\n";
    } elseif ($type === 'get_version') {
        $bundleName = $data['bundle'];
        $response = getVersion($bundleName);
        echo "Version number request received\n";

    }elseif ($type === 'status_update') {
        $response = updateStatus($data);
        echo "Status request received\n";
    }
     else {
        echo "Received unknown deployment command or missing required data fields\n";
        return;
    };

    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->queue_purge($queueName);
    $channel->basic_publish($responseMsg, 'directExchange', $queueName);
};

$channel->basic_consume('toDeploy', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

closeRabbit($connection, $channel);
