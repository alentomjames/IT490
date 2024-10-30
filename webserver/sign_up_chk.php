<?php
ob_start();

session_start();
// Script to conenct to RabbitMQ
require_once 'rabbitmq_connection.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $type = 'register';

    // Get RabbitMQ connection from rabbitmq_connection.php
    list($connection, $channel) = getRabbit();

    // Declaring the channel its being sent on
    $channel->queue_declare('frontendForDB', false, true, false, false);

    $data = json_encode([
        'type'     => $type,
        'name'     => $name,
        'username' => $username,
        'password' => $password
    ]);

    // Send the message to the queue with username and password, delivery mode 2 means the message will be saved ot the disk
    // meaning it won't be lost from the queue even if RabbitMQ restarts
    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendForDB');

    //Close the connection and channel to RabbitMQ using rabbitmq_connection.php
    closeRabbit($connection, $channel);

    // Waits for a response from RabbitMQ (sucess + userID or failure)
    receiveRabbitMQResponse();
}

function receiveRabbitMQResponse(){
    list($connection, $channel) = getRabbit();
    // Declare the response channel
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    // Function waiting for the response from RabbitMQ
    $callback = function($msg) {
        $response = json_decode($msg->body, true);

        // Checks the status variable in the message to see if it's a success or failure
        if ($response['type'] === 'success'){
            // Retrieves the userID from the $msg and stores it in the sessionID to login user
            $_SESSION['userID'] = $response['userID'];
            $_SESSION['name'] = $response['name'];
            header("Location: index.php");
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

      // Wait for the response
      while ($channel->is_consuming()) {
        $channel->wait();
    }
        // Close the channel and connection
        closeRabbit($connection, $channel);

}
?>

