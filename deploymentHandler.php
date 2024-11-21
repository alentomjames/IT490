<?php

require_once 'db_connection.php';
require_once 'rmq_connection.php';
require_once 'vendor/autoload.php';

use PhpAmqpLib\Message\AMQPMessage;

list($connection, $channel) = getRabbit();

$channel->queue_declare('devToDeployment', false, true, false, false);

$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);

    $bundleName = $data['bundle_name'] ?? null;
    $version = $data['version'] ?? null;
    $status = 'new';

    if (!$bundleName || !$version) {
        echo "Invalid deployment messages received\n";
        return;
    }

    echo "Received deployment request: Bundle: $bundleName, Version: $version\n";

    $db = getDbConnection();
    $stmt = $db->prepare("INSERT INTO deployments (bundle_name, version_number, status, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("sss", $bundleName, $version, $status);

    if ($stmt->execute()) {
        echo "Deployment saved to database successfully\n";
        // we probably use rsync here??
        // triggerDeployment($bundleName, $data['target']);
    } else {
        echo "Failed to save deployment to database: " . $stmt->error . "\n";
    }

    $stmt->close();
    $db->close();
};

$channel->basic_consume('devToDeployment', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

closeRabbit($connection, $channel);
