<?php
session_start();
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $movieId = $input['movie_id'];
    $userId = $_SESSION['userID'];
    $type = 'add_to_watchlist';

    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }
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

    if (ob_get_length()) {
        ob_clean();
    }
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            echo json_encode(['type' => 'success', 'message' => 'Movie added to watchlist']);
        } else {
            echo json_encode(['type' => 'failure', 'message' => 'Failed to add movie']);
        }
        exit();
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
