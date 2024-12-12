<?php

session_start();
header('Content-Type: application/json');
require_once '../vendor/autoload.php';
require_once '../rabbitmq_connection.php';

use PhpAmqpLib\Message\AMQPMessage;
$getenv = parse_ini_file('../.env');

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($cluster === null) {
    error_log('CLUSTER not set in .env file');
    exit;
}
if ($_SERVER["REQUEST_METHOD"] == "DELETE") {
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['type' => 'failure', 'message' => 'User not logged in']);
        exit;
    }


    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['movie_id'])) {
        echo json_encode(['type' => 'failure', 'message' => 'Movie ID not provided']);
        exit;
    }

    $movieId = $input['movie_id'];
    $userId = $_SESSION['userID'];
    $type = "remove_from_watchlist";
    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }


    $channel->queue_declare('frontendForDB', false, true, false, false);

    $data = json_encode([
        'type' => $type,
        'movie_id' => $movieId,
        'user_id' => $userId
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendForDB');

    receiveRabbitMQResponse($connection, $channel);
}

function receiveRabbitMQResponse($connection, $channel)
{
    if (ob_get_length()) {
        ob_clean();
    }

    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            echo json_encode(['type' => 'success', 'message' => 'Movie removed from watchlist']);
        } else {
            echo json_encode(['type' => 'failure', 'message' => 'Failed to remove movie from watchlist']);
        }
        exit;
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
