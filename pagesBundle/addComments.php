<?php
session_start();
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


$envFilePath = __DIR__ . '/../.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $input = json_decode(file_get_contents('php://input'), true);
    $movieId = $input['movie_id'];
    $content = $input['content'];
    $userId = $_SESSION['userID'];
    $type = 'add_comment';

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
        'movie_id' => $movieId,
        'user_id' => $userId,
        'content' => $content
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
        	$comment = json_encode($response);
        	error_log("RESPONSE:" .  $comment);
        	if ($response['type'] === 'success') {
          		echo json_encode(['type' => 'success', 'message' => "Message recieved successfully"]); 
        	} else {
          		echo json_encode(['type' => 'failure', 'message' => 'Failed to retrieve comments']);
        	}
        exit();
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}
