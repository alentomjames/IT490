<?php

session_start();
header('Content-Type: application/json');
require_once '../vendor/autoload.php';
require_once '../rabbitmq_connection.php';
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
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['type' => 'failure', 'message' => 'User not logged in']);
        exit;
    }

    $userId = $_SESSION['userID'];
    $type = "get_watchlist";

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
            echo json_encode(['type' => 'success', 'watchlist' => $response['watchlist']]);
        } else {
            echo json_encode(['type' => 'failure', 'message' => 'Failed to retrieve watchlist']);
        }
        exit;
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
