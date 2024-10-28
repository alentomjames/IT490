<?php

require_once __DIR__ . 'vendor/autoload.php';  

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

function sendRequest($type, $parameter){
    list($connection, $channel) = getRabbit();
    // Declaring the channel its being sent on
    $channel->queue_declare('frontendQueue', false, true, false, false);
   
    $data = json_encode([
        'type'     => $type,
        'parameter' => $parameter
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendQueue');
    debug_to_console("Frontend Message Sent");
    closeRabbit($connection, $channel);

}

function recieveDMZ(){
    list($connection, $channel) = getRabbit();
    // Declare the response channel 
    $channel->queue_declare('dmzQueue', false, true, false, false);
    // Function waiting for the response from RabbitMQ 
    $callback = function($msg) {
        $response = json_decode($msg->body, true);
        // Checks the status variable in the message to see if it's a success or failure 
        $data = $response['data'];

    };
    
    $channel->basic_consume('dmzQueue', '', false, true, false, false, $callback);
    debug_to_console("Waiting for response");

    // Wait for the response
    while ($channel->is_consuming()) {
        $channel->wait();  
    }
    debug_to_console("Response Recieved");

    // Close the channel and connection
    closeRabbit($connection, $channel);

    return $data;
}
?>