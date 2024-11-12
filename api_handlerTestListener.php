<?php

// Creates api_handler error logs that tell of any issues that occurred when the api_handler was running.

require_once 'webserver/rabbitmq_connection.php';
require_once 'webserver/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\Exception\RequestException;

list($connection, $channel) = getRabbit();
echo "Connected to RabbitMQ\n";

// Declare the queue to listen to
$channel->queue_declare('frontendForDMZ', false, true, false, false);
echo "Declared queue 'frontendQueue'\n";

function sendErrorLog($channel, $errorMessage) {
    $msg = new AMQPMessage($errorMessage);
    $channel->basic_publish($msg, '', $GLOBALS['frontendForDMZ']);
    echo "Error log sent to '$GLOBALS[frontendForDMZ]'.\n";
}

function processMessage($msg) {
    try {
        $messageBody = $msg->getBody();
        echo "Received: $messageBody\n";
        
        if (strpos($messageBody, 'error') !== false) {
            throw net Exception("Processing error detected in the message!");
        }

        // Simulate successful processing
        echo "Message processed successfully.\n";
    }
    catch (Exception $e) {

    }

}




?>