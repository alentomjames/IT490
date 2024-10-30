<?php

require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ
require_once 'vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once 'auth/login.php';
require_once 'auth/register.php';
require_once 'movies/watchlist.php';

// get the rabbitmq connection
list($connection, $channel) = getRabbit();

// queue where i'll consume login/register requests
$channel->queue_declare('frontendForDB', false, true, false, false);

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // for now types are: login, register

    if ($type === 'login') {
        // calling login function
        $username = $data['username'];
        $password = $data['password'];
        $response = login($username, $password);
    } elseif ($type === 'register') {
        // register request
        $username = $data['username'];
        $password = $data['password'];
        $name = $data['name'];
        $response = register($name, $username, $password);
    } elseif ($type === "add_to_watchlist") {
        $movieId = $data['movie_id'];
        $userId = $data['user_id'];
        // add to watchlist
        $response = addToWatchlist($movieId, $userId);
    } elseif ($type === "remove_from_watchlist") {
        $movieId = $data['movie_id'];
        $userId = $data['user_id'];

        // remove from watchlist
        $response = removeFromWatchlist($movieId, $userId);
    } elseif ($type === "get_watchlist") {
        $userId = (int) $data['user_id'];
        // get all watchlist
        $response = getFromWatchlist($userId);
    } else {
        echo "Received unknown command or missing required data fields\n";
        return;
    }
    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, 'directExchange', 'databaseForFrontend');
};


// consume the messages from the queue
$channel->basic_consume('frontendForDB', '', false, true, false, false, $callback);

// wait for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

// close the rabbitmq connection
closeRabbit($connection, $channel);

// userid, sessionID, timestamp
