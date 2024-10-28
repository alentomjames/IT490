<?php

require_once 'vendor/autoload.php';  

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
    $data = null;
    // Declare the response channel 
    $channel->queue_declare('dmzQueue', false, true, false, false);
    // Function waiting for the response from RabbitMQ 
    $callback = function($msg) use (&$data) {
        $response = json_decode($msg->body, true);
        // Check if the response type is 'success' and data is present
        if (isset($response['type']) && $response['type'] === 'success' && isset($response['data'])) {
            $data = $response['data'];
        } else {
            echo 'Error: Failed to retrieve data or invalid response format received from DMZ.';
        }
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