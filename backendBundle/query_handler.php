<?php

require_once 'db_connection.php'; // file has db connection
require_once '../rabbitmq_connection.php'; // how I connect to RabbitMQ
require_once '../vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

require_once '../loginBundleDB/login.php';
require_once '../loginBundleDB/register.php';
require_once '../moviesDB/watchlist.php';
require_once '../moviesDB/rating.php';
require_once '../moviesDB/topTen.php';
require_once '../moviesDB/likedMovies.php';
$envFilePath = __DIR__ . '/../.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($cluster === null) {
    error_log('CLUSTER not set in .env file');
    exit;
}
// get the rabbitmq connection
if ($cluster == 'QA') {
    list($connection, $channel) = getQARabbit();
} else if ($cluster == 'PROD') {
    list($connection, $channel) = getProdRabbit();
} else {
    list($connection, $channel) = getRabbit();
}
// queue where i'll consume login/register requests
$channel->queue_declare('frontendForDB', false, true, false, false);

trigger_error("Oh no, there was an error! This message should be in the error.log file.\n");
error_log("This is a test error\n", 3, '/var/log/database/error.log');

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // for now types are: login, register

    if ($type === 'login') {
        // calling login function
        $username = $data['username'];
        $password = $data['password'];
        $response = login($username, $password);
        echo "Login request received for username: $username\n";
        echo "Response: $response\n";
    } elseif ($type === 'register') {
        // register request
        $username = $data['username'];
        $password = $data['password'];
        $name = $data['name'];
        $response = register($name, $username, $password);
        echo "Register request received for username: $username, name: $name\n";
    } elseif ($type === "add_to_watchlist") {
        $movieId = $data['movie_id'];
        $userId = $data['user_id'];
        // add to watchlist
        $response = addToWatchlist($movieId, $userId);
        echo "Add to watchlist request received for movie ID: $movieId, user ID: $userId\n";
    } elseif ($type === "remove_from_watchlist") {
        $movieId = $data['movie_id'];
        $userId = $data['user_id'];
        // remove from watchlist
        $response = removeFromWatchlist($movieId, $userId);
        echo "Remove from watchlist request received for movie ID: $movieId, user ID: $userId\n";
    } elseif ($type === "get_watchlist") {
        $userId = (int) $data['user_id'];
        // get all watchlist
        $response = getFromWatchlist($userId);
        echo "Get watchlist request received for user ID: $userId\n";
    } elseif ($type === "set_rating") {
        $movieId = (int) $data['movie_id'];
        $userId = (int) $data['user_id'];
        $rating = (int) $data['rating'];
        $response = rateMovie($movieId, $userId, $rating);
        echo "Set rating request received for movie ID: $movieId, user ID: $userId, rating: $rating\n";
    } elseif ($type === "get_top_ten") {
        $response = getTopTenMovies();
        echo "Get top ten movies request received\n";
    } elseif ($type === "get_likedMovies") {
        $userId = (int) $data['user_id'];
        $response = getFromRatings($userId);
        echo "Get liked movies request received for user ID: $userId\n";
    } else {
        error_log("Received unknown command or missing required data fields\n", 3, '/var/log/database/error.log');
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
