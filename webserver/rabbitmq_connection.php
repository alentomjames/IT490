<?php

require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function getRabbit(){
    // Connect to RABBITMQ HERE and add better error handling
    $connection = new AMQPStreamConnection('172.29.4.30', 5672, 'admin', 'admin', 'IT490_Host');
    $channel = $connection->channel();

    return [$connection, $channel];
}

function closeRabbit($connection, $channel){
    if ($channel){
        $channel->close();
    }
    if ($connection){
    $connection->close();
    }
}
?>