<?php
session_start();
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';

$envFilePath = __DIR__ . '/../.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $commentId = $input['comment_id'];
    $userId = $_SESSION['userID'];
    $type = 'delete_comment';

    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } elseif ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }

    $channel->queue_declare('frontendForDB', false, true, false, false);

    $data = json_encode([
        'type' => $type,
        'comment_id' => $commentId,
        'user_id' => $userId
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
        echo json_encode($response);
        exit();
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
