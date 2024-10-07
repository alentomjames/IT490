<?php

require_once __DIR__ . '/vendor/autoload.php';  

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function getRabbit(){
    // Connect to RABBITMQ HERE and add better error handling
    $connection = new AMQPStreamConnection('ip address', portnumber, 'guest', 'guest'); 
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