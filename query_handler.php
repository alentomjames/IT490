<?php

require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once 'login.php';
require_once 'register.php';

// get the rabbitmq connection
list($connection, $channel) = getRabbit();

// queue where i'll consume login/register requests
$channel->queue_declare('frontendQueue', false, true, false, false);

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // for now types are: login, register

    if ($type === 'login') {
        $username = $data['username'];
        $password = $data['password'];

        // calling login function
        $response = login($username, $password);
    } elseif ($type === 'register') {
        // register request
        $username = $data['username'];
        $password = $data['password'];
        $name = $data['name'];

        $response = register($name, $username, $password);
    }
    // send the response back to the client
    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, 'directExchange', 'databaseQueue');
};


// consume the messages from the queue
$channel->basic_consume('frontendQueue', '', false, true, false, false, $callback);

// wait for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

// close the rabbitmq connection
closeRabbit($connection, $channel);

// userid, sessionID, timestamp
