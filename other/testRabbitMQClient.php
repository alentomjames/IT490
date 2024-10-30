<?php
require 'vendor/autoload.php'; // Adjust path as necessary

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendMessage($queue, $messageBody)
{
    // RabbitMQ server details
    $host = '172.29.4.30'; // Change if your RabbitMQ server is on another host
    $port = 5672; // Default RabbitMQ port
    $user = 'admin'; // Default username
    $password = 'admin'; // Default password
    $vhost = 'IT490_Host'; // Specify your vhost here, use '/' for the default
    // Create a connection
    $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
    $channel = $connection->channel();

    // Define the direct exchange
    $exchange = 'directExchange'; // Change this to your exchange name

    // Create a message
    $message = new AMQPMessage($messageBody);

    // Publish the message to the specified queue using its routing key
    $channel->basic_publish($message, $exchange, $queue);

    echo "Message sent to queue '$queue': $messageBody\n";

    // Close the channel and connection
    $channel->close();
    $connection->close();
}

// Usage: sendMessage('queue_name', 'your_message');
sendMessage('dmz', 'Hello, DMZ Queue!');
sendMessage('db', 'Hello, DB Queue!');
sendMessage('frontend', 'Hello, Frontend Queue!');
