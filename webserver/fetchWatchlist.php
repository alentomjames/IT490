<?php

session_start();
header('Content-Type: application/json');
require_once './vendor/autoload.php';
require_once './db_connection.php';
require_once './rabbitmq_connection.php';

use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['type' => 'failure', 'message' => 'User not logged in']);
        exit;
    }

    $userId = $_SESSION['userID'];
    $type = "get_watchlist";

    list($connection, $channel) = getRabbit();

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
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            echo $response;
            return ['type' => 'success', 'watchlist' => $response];
        } else {
            return ['type' => 'failure', 'message' => 'Failed to retrieve watchlist'];
        }
        exit;
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
