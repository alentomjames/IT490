<?php


session_start();
header('Content-Type: application/json');
//require_once './rmq_connection.php';
//require_once './vendor/autoload.php';
require_once '../vendor/autoload.php';
require_once '../db_connection.php'; // file has db connection
require_once '../rmq_connection.php'; // how I connect to RabbitMQ

require_once '../movies/watchlist.php';

use PhpAmqpLib\Message\AMQPMessage;

$userId = $_SESSION['userID'] = 1; // Hardcoded user ID for testing
// $loggedIn = isset($userId);
// if (!isset($_SESSION['userID'])) {
//     echo json_encode(['success' => false, 'message' => 'User not logged in']);
//     exit;
// }



if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    if (isset($_SESSION['userID'])) {
        $input = json_decode(file_get_contents('php://input'), true);
        $movieId = $input['movie_id'];
        $userId = $_SESSION['userID'];
        $type = "remove_from_watchlist";
        $response = removeFromWatchlist($movieId, $userId);
        // Encode the response as JSON
        echo json_encode($response);
    } else {
        echo json_encode(['type' => 'failure', 'message' => 'User not logged in']);
    }
}

// $input = json_decode(file_get_contents('php://input'), true);
// $movieId = $input['movie_id'];
// $userId = $_SESSION['userID'];
// $type = 'add_to_watchlist';

// list($connection, $channel) = getRabbit();

// $channel->queue_declare('frontendQueue', false, true, false, false);

// $data = json_encode([
//     'type'     => $type,
//     'movie_id' => $movieId,
//     'user_id'  => $userId
// ]);

// $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
// $channel->basic_publish($msg, 'directExchange', 'frontendQueue');
// closeRabbit($connection, $channel);

// receiveRabbitMQResponse();
//}
// list($connection, $channel) = getRabbit();
// $channel->queue_declare('frontendForDB', false, true, false, false);

// $data = json_encode([
//     'type'   => 'get_watchlist',
//     'user_id' => $userId
// ]);

// $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
// $channel->basic_publish($msg, 'directExchange', 'frontendForDB');
// closeRabbit($connection, $channel);


//echo ($response['watchlist']);


// function receiveWatchlistResponse()
// {
//     $response = getFromWatchlist($_SESSION['userID']);
//     return ($response);
    // list($connection, $channel) = getRabbit();
    // $channel->queue_declare('databaseForFrontend', false, true, false, false);

    // $watchlist = [];

    // $callback = function ($msg) use (&$watchlist) {
    //     $response = json_decode($msg->body, true);
    //     if (isset($response['type']) && $response['type'] === 'success' && isset($response['watchlist'])) {
    //         echo "Watchlist is hrere";
    //         $watchlist = $response['watchlist'];
    //         echo json_encode(['success' => true, 'watchlist' => $watchlist]);
    //     } else {
    //         echo "wata";
    //         echo json_encode(['success' => false, 'message' => 'Failed to retrieve watchlist']);
    //     }
    //     exit();
    // };

    // // $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);

    // // while ($channel->is_consuming()) {
    // //     $channel->wait();
    // // }

    // // closeRabbit($connection, $channel);
    // echo "wda";
    // return json_encode(['success' => true, 'watchlist' => $watchlist]);
//}
