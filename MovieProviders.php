<?php

require_once 'webserver/rabbitmq_connection.php';
require_once 'webserver/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\Exception\RequestException;

// contacts the message router

list($connection, $channel) = getRabbit();
echo "Connected to RabbitMQ\n";

/*
// Declare the queue to listen to
$channel->queue_declare('frontendForDMZ', false, true, false, false);
echo "Declared queue 'frontendQueue'\n";
*/

$movie_id = isset($_GET['id']) ? $_GET['id'] : null;


?>