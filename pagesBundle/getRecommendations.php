<?php

session_start();
header('Content-Type: application/json');
require_once '../vendor/autoload.php';
require_once '../rabbitmq_connection.php';

use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (!isset($_SESSION['userID'])) {
        echo json_encode(['type' => 'failure', 'message' => 'User not logged in']);
        exit;
    }

    $userId = $_SESSION['userID'];
    $type = "get_likedMovies";

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

    if (ob_get_length()) {
        ob_clean();
    }
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            echo json_encode(['type' => 'success', 'recommendations' => $response]);
        } else {
            echo json_encode(['type' => 'failure', 'message' => 'Failed to retrieve recommendations']);
        }
        exit;
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
