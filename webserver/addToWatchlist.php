<?php
session_start();
require_once 'rabbitmq_connection.php';
require_once 'vendor/autoload.php';  

use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $movieId = $input['movie_id'];
    $userId = $_SESSION['userID'];
    $type = 'add_to_watchlist';

    list($connection, $channel) = getRabbit();

    $channel->queue_declare('frontendForDB', false, true, false, false);

    $data = json_encode([
        'type'     => $type,
        'movie_id' => $movieId,
        'user_id'  => $userId
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendForDB');
    closeRabbit($connection, $channel);

    receiveRabbitMQResponse();
}

function receiveRabbitMQResponse()
{
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            echo json_encode(['success' => true, 'message' => 'Movie added to watchlist']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add movie']);
        }
        exit();
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
