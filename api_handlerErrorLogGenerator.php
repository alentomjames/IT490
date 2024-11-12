<?php

// Creates api_handler error logs that tell of any issues that occurred when the api_handler was running.
/*
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
    echo "Error log sent to '$GLOBALS[frontendForDMZ"
}
*/



?>