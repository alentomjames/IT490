<?php

require_once 'db_connection.php'; // Include the DB connection (adjust if needed)
require_once 'rmq_connection.php'; // Include the RabbitMQ connection (getRabbit, closeRabbit)
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Get RabbitMQ connection from rabbitmq_connection.php
list($connection, $channel) = getRabbit();

// Declare the queue to consume the login/register requests from
$channel->queue_declare('databaseQueue', false, true, false, false);

// Function to process login or register requests from the queue
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // Identify whether it's a login or register request

    // Connect to the MySQL database
    $dbConnection = getDbConnection(); // from db_connection.php

    if ($type === 'login') {
        // Handle login request
        $username = $data['username'];
        $name = $data['name'];
        $inputPassword = $data['password']; // Received in plaintext now

        // Query to fetch user data for login
        $query = "SELECT userID, user_pwd, name FROM users WHERE username = ?";
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userID, $storedHash);
            $stmt->fetch();

            // Verify the plaintext password against the stored hash
            if (password_verify($inputPassword, $storedHash)) {
                // If password is correct, send a success response with userID
                $response = json_encode([
                    'type' => 'success',
                    'name'   => $name,
                    'username' => $username,
                    'userID' => $userID
                ]);
                echo "Login successful for user: $username\n";
            } else {
                // Password doesn't match
                $response = json_encode(['type' => 'failure']);
                echo "Login failed for user: $username\n";
            }
        } else {
            // No user found
            $response = json_encode(['type' => 'failure']);
            echo "User not found: $username\n";
        }
    } elseif ($type === 'register') {
        // Handle register request
        $username = $data['username'];
        $inputPassword = $data['password']; // Received in plaintext now
        $name = $data['name'];

        // Check if username already exists
        $checkQuery = "SELECT username FROM users WHERE username = ?";
        $stmt = $dbConnection->prepare($checkQuery);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Username already exists
            $response = json_encode(['type' => 'failure', 'reason' => 'User already exists']);
            echo "Username already exists: $username\n";
        } else {
            // Hash the password before storing
            $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);

            // Insert new user into the database
            $insertQuery = "INSERT INTO users (username, user_pwd, name) VALUES (?, ?, ?)";
            $stmt = $dbConnection->prepare($insertQuery);
            $stmt->bind_param('sss', $username, $hashedPassword, $name);

            if ($stmt->execute()) {
                // Registration successful
                $userID = $stmt->insert_id; // Get the new user's ID
                $response = json_encode([
                    'type' => 'success',
                    'userID' => $userID
                ]);
                echo "New user registered: $username\n";
            } else {
                // Registration failed due to a database error
                $response = json_encode(['type' => 'failure', 'reason' => 'Database error']);
                echo "Failed to register user: $username\n";
            }
        }
    }

    // Send the response back to the frontend/backend VM
    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, 'directExchange', 'dbResponse');

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
