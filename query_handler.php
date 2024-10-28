<?php

require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once 'auth/login.php';
require_once 'auth/register.php';
require_once 'movies/watchlist.php';

// get the rabbitmq connection
list($connection, $channel) = getRabbit();

// queue where i'll consume login/register requests
$channel->queue_declare('frontendQueue', false, true, false, false);

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type'] ?? null;
    $username = $data['username'] ?? null;
    $password = $data['password'] ?? null;
    $movieId = $data['movie_id'] ?? null;
    $userId = $data['user_id'] ?? null;
    $response = null;

    if ($type === 'login' && $username && $password) {
        $response = login($username, $password);
    } elseif ($type === 'register' && isset($data['name'], $username, $password)) {
        $name = $data['name'];
        $response = register($name, $username, $password);
    } elseif ($type === "add_to_watchlist" && $movieId && $userId) {
        $response = addToWatchlist($movieId, $userId);
    } elseif ($type === "remove_from_watchlist" && $movieId && $userId) {
        $response = removeFromWatchlist($movieId, $userId);
    } elseif ($type === "get_watchlist" && $userId) {
        $response = getFromWatchlist($userId);
    } else {
        echo "Received unknown command or missing required data fields\n";
        $response = json_encode(["error" => "Invalid request type or missing parameters"]);
    }

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
