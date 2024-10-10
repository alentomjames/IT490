<?php

require_once 'db_connection.php'; // Include the DB connection (adjust if needed)
require_once 'rmq_connection.php'; // Include the RabbitMQ connection (getRabbit, closeRabbit)
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Message\AMQPMessage;

// Get RabbitMQ connection from rabbitmq_connection.php
list($connection, $channel) = getRabbit();

// Declare the queue to consume the login/register requests from
$channel->queue_declare('databaseQueue', false, false, false, false);

// Function to process login or register requests from the queue
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // Identify whether it's a login or register request

    // Connect to the MySQL database
    $dbConnection = getDbConnection(); // from db_connection.php

    if ($type === 'login') {
        // Handle login request
        $username = $data['username'];
        $inputPassword = $data['password']; // Hashed on frontend

        // Query to fetch user data for login (replace this query with your actual column/table names)
        $query = "SELECT userID, user_pwd FROM users WHERE username = ?";
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        // If a user exists, verify the password
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userID, $storedHash);
            $stmt->fetch();

            // Verify if the hashed password matches
            if (password_verify($inputPassword, $storedHash)) {
                // If password is correct, send a success response with userID
                $response = json_encode([
                    'status' => 'success',
                    'name' => $username,
                    'userID' => $userID
                ]);
            } else {
                // If password doesn't match
                $response = json_encode(['status' => 'failure']);
            }
        } else {
            // No such user exists
            $response = json_encode(['status' => 'failure']);
        }
    } elseif ($type === 'register') {
        // Handle register request
        $username = $data['username'];
        $inputPassword = $data['password']; // Already hashed
        $name = $data['name']; // New user's name

        // Check if the username already exists
        $checkQuery = "SELECT username FROM users WHERE username = ?";
        $stmt = $dbConnection->prepare($checkQuery);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Username already exists
            $response = json_encode(['status' => 'failure', 'reason' => 'User already exists']);
        } else {
            // Insert new user into the database
            $insertQuery = "INSERT INTO users (username, user_pwd, name) VALUES (?, ?, ?)";
            $stmt = $dbConnection->prepare($insertQuery);
            $stmt->bind_param('sss', $username, $inputPassword, $name);

            if ($stmt->execute()) {
                // If registration is successful, send a success response
                $userID = $stmt->insert_id; // Get the new user's ID
                $response = json_encode([
                    'status' => 'success',
                    'userID' => $userID
                ]);
            } else {
                // Registration failed due to some database error
                $response = json_encode(['status' => 'failure', 'reason' => 'Database error']);
            }
        }
    }

    // Send the response back to the frontend/backend VM
    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, '', 'dbResponseQueue'); // Send to responseQueue

    // Close database connection
    $stmt->close();
    $dbConnection->close();
};

// Consume the login/register queue
$channel->basic_consume('databaseQueue', '', false, true, false, false, $callback);

// Wait for messages from RabbitMQ
while ($channel->is_consuming()) {
    $channel->wait();
}

// Close the RabbitMQ connection and channel when done
closeRabbit($connection, $channel);
