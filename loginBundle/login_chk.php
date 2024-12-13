<?php
ob_start();

session_start();
// Script to conenct to RabbitMQ
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';
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
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $type = 'login';

    // Get RabbitMQ connection from rabbitmq_connection.php
    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }

    // Declaring the channel its being sent on
    $channel->queue_declare('frontendForDB', false, true, false, false);

    $data = json_encode([
        'type' => $type,
        'username' => $username,
        'password' => $password
    ]);

    // Send the message to the queue with username and password, delivery mode 2 means the message will be saved ot the disk
    // meaning it won't be lost from the queue even if RabbitMQ restarts
    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendForDB');
    echo "<script>console.log('Frontend Message Sent');</script>";
    debug_to_console("Frontend Message Sent");


    //Close the connection and channel to RabbitMQ using rabbitmq_connection.php
    closeRabbit($connection, $channel);
    // Waits for a response from RabbitMQ (sucess + userID or failure)
    receiveRabbitMQResponse();
}

function receiveRabbitMQResponse()
{
    global $cluster;
    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }

    // Declare the response channel
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    $is_consuming = true;

    // Function waiting for the response from RabbitMQ
    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        echo 'Response variale: $response';
        echo "<script>console.log('$response');</script>";
        // Checks the status variable in the message to see if it's a success or failure
        if ($response['type'] === 'success') {
            // Retrieves the userID from the $msg and stores it in the sessionID to login user
            $_SESSION['name'] = $response['name'];
            $_SESSION['userID'] = $response['userID'];
            echo "<script>console.log('Response Success');</script>";
            $is_consuming = false;
            header(header: "Location: ../index.php");
            exit();
        } else {
            echo 'Login Failed';
            $is_consuming = false;
            header("Location: login.php");
            exit();
        }
    };
    // Use basic_consume to access the queue and call $callback for success or failure
    // https://www.rabbitmq.com/tutorials/tutorial-six-php
    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);
    debug_to_console("Waiting for response");


    // Wait for the response
    while ($is_consuming && $channel->is_consuming()) {
        $channel->wait();

    }
    debug_to_console("Response Recieved");

    // Close the channel and connection
    closeRabbit($connection, $channel);

}

function debug_to_console($data)
{
    $output = $data;
    if (is_array($output))
        $output = implode(',', $output);

    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}

?>