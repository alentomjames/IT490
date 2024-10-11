<?php
require 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$host = '172.29.4.30'; // RabbitMQ server
$port = 5672;
$user = 'admin';
$passwordRMQ = 'admin';
$vhost = 'IT490_Host';

function processFrontendQueue($messageBody) {
    $data = json_decode($messageBody, true);
    
    // Check if the type is set
    if (isset($data['type'])) {
        $type = $data['type'];
        // Handle "login" type
        if ($type === 'login') {
            if (isset($data['username']) && isset($data['password'])) {
                $username = $data['username'];
                $password = $data['password'];
                echo "Processing login request from frontend queue: \n";
                print_r($data);
                // Forward the message to the DB queue for further processing
                sendMessageToDBQueue(json_encode($data));
            } else {
                echo "Invalid data for login: username or password missing.\n";
            }

        // Handle "register" type
        } elseif ($type === 'register') {
            if (isset($data['name']) && isset($data['username']) && isset($data['password'])) {
                $name = $data['name'];
                $username = $data['username'];
                $password = $data['password'];

                echo "Processing register request from frontend queue: \n";
                print_r($data);

                // Forward the message to the DB queue for further processing
                sendMessageToDBQueue(json_encode($data));
           
        }  else {
                echo "Invalid data for registration: name, username, or password missing.\n";
            }
	} elseif ($type === 'api') {
             echo "Processing register request from frontend queue: \n";
             print_r($data);

                // Forward the message to the DB queue for further processing
             sendMessageToDMZQueue(json_encode($data));
	}else {
            echo "Unknown request type received.\n";
        }
    } else {
        echo "Invalid data received: 'type' field missing.\n";
    }
}

function processDBQueue($messageBody) {
    echo "Processing message from dbQueue: $messageBody\n";
    
    sendMessageToFrontendResponseQueue($messageBody); // Forward to frontendResponse
    
}

// Function to simulate processing and forward back to frontend response
function processDMZQueue($messageBody) {
    echo "Processing message from dmzQueue: $messageBody\n";
    sendMessageToFrontendResponseQueue($messageBody); // Forward to frontendResponse
}

// Function to send message to DB queue
function sendMessageToDBQueue($messageBody) {
    global $host, $port, $user, $passwordRMQ, $vhost;
    $connection = new AMQPStreamConnection($host, $port, $user, $passwordRMQ, $vhost);
    $channel = $connection->channel();
    $exchange = 'directExchange';
    $queue = 'db';

    $message = new AMQPMessage($messageBody);
    $channel->basic_publish($message, $exchange, $queue);
    echo "Sent message to DB queue: $messageBody\n";

    $channel->close();
    $connection->close();
}

// Function to send message to DMZ queue
function sendMessageToDMZQueue($messageBody) {
    global $host, $port, $user, $passwordRMQ, $vhost;
    $connection = new AMQPStreamConnection($host, $port, $user, $passwordRMQ, $vhost);
    $channel = $connection->channel();
    $exchange = 'directExchange';
    $queue = 'dmz';

    $message = new AMQPMessage($messageBody);
    $channel->basic_publish($message, $exchange, $queue);
    echo "Sent message to DMZ queue: $messageBody\n";

    $channel->close();
    $connection->close();
}

// Function to send message to frontendResponseQueue
function sendMessageToFrontendResponseQueue($messageBody) {
    global $host, $port, $user, $passwordRMQ, $vhost;
    $connection = new AMQPStreamConnection($host, $port, $user, $passwordRMQ, $vhost);
    $channel = $connection->channel();
    $exchange = 'directExchange';
    $queue = 'frontendResponse';

    $message = new AMQPMessage($messageBody);
    $channel->basic_publish($message, $exchange, $queue);
    echo "Sent message to frontendResponseQueue: $messageBody\n";

    $channel->close();
    $connection->close();
}

// Setup RabbitMQ connection for listening to queues
$connection = new AMQPStreamConnection($host, $port, $user, $passwordRMQ, $vhost);
$channel = $connection->channel();

// Declare the queues
$channel->queue_declare('frontendQueue', false, true, false, false);
$channel->queue_declare('databaseQueue', false, true, false, false);
$channel->queue_declare('dmzQueue', false, true, false, false);
$channel->queue_declare('frontendResponseQueue', true, false, false, false);
$channel->queue_declare('dbResponseQueue', false, true, false, false);


// Listen to frontendQueue and process messages
$channel->basic_consume('frontendQueue', '', false, true, false, false, function($msg) {
    echo "Received message from frontendQueue: " . $msg->body . "\n";
    processFrontendQueue($msg->body);
});

// Listen to dbQueue and process messages
$channel->basic_consume('dbResponseQueue', '', false, true, false, false, function($msg) {
    echo "Received message from dbResponseQueue: " . $msg->body . "\n";
    processDBQueue($msg->body);
});

// Listen to dmzQueue and process messages
$channel->basic_consume('dmzResponseQueue', '', false, true, false, false, function($msg) {
    echo "Received message from dmzResponseQueue: " . $msg->body . "\n";
    processDMZQueue($msg->body);
});

// Loop to keep listening for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

$channel->close();
$connection->close();
?>

