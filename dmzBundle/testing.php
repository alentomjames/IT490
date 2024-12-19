<?php
require_once '../vendor/autload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('172.29.4.30', 5672, 'admin', 'admin', 'IT490_Host');
$channel = $connection->channel();

$channel->exchange_declare('directExchange', 'direct', false, true, false);
$channel->queue_bind('dmzForFrontend', 'directExchange', 'dmzForFrontend');

$msg = new AMQPMessage('Test Message', ['delivery_mode' => 2]);
$channel->basic_publish($msg, 'directExchange', 'dmzForFrontend');

echo "Message published\n";

$channel->close();
$connection->close();
?>