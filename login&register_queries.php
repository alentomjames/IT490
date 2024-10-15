<?php

require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// get the rabbitmq connection
list($connection, $channel) = getRabbit();

// queue where i'll consume login/register requests
$channel->queue_declare('databaseQueue', false, true, false, false);

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // for now types are: login, register

    // we initialize connection to the database
    $dbConnection = getDbConnection(); // from db_connection.php

    if ($type === 'login') {
        // login request
        $username = $data['username'];
        $inputPassword = $data['password']; // plaintext password

        // query that fetches the user's ID, hashed password and name
        $query = "SELECT userID, user_pwd, name FROM users WHERE username = ?";
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($userID, $storedHash, $name); // fetches the results
            $stmt->fetch();

            // plaintext password is compared to the hashed password
            if (password_verify($inputPassword, $storedHash)) {
                // if password = hashed password, login is successful
                $response = json_encode([
                    'type'    => 'success',
                    'name'    => $name,
                    'userID'  => $userID
                ]);
                echo "Login successful for user: $username\n";
            } else {
                // when passwords dont match, send failure response
                $response = json_encode(['type' => 'failure']);
                echo "Login failed for user: $username\n";
            }
        } else {
            // user not found
            $response = json_encode(['type' => 'failure']);
            echo "User not found: $username\n";
        }
    } elseif ($type === 'register') {
        // register request
        $username = $data['username'];
        $inputPassword = $data['password'];
        $name = $data['name'];

        // check for existing username
        $checkQuery = "SELECT username FROM users WHERE username = ?";
        $stmt = $dbConnection->prepare($checkQuery);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $response = json_encode(['type' => 'failure', 'reason' => 'User already exists']);
            echo "Username already exists: $username\n";
        } else {
            // hash the password
            $hashedPassword = password_hash($inputPassword, PASSWORD_DEFAULT);

            // insert the new user into the database
            $insertQuery = "INSERT INTO users (username, user_pwd, name) VALUES (?, ?, ?)";
            $stmt = $dbConnection->prepare($insertQuery);
            $stmt->bind_param('sss', $username, $hashedPassword, $name);

            if ($stmt->execute()) {
                // successful registration
                $userID = $stmt->insert_id; // insert_id gets the last inserted id
                $response = json_encode([
                    'type' => 'success',
                    'name' => $name,
                    'userID' => $userID
                ]);
                echo "New user registered: $username\n";
            } else {
                // failed to register user
                $response = json_encode(['type' => 'failure', 'reason' => 'Database error']);
                echo "Failed to register user: $username\n";
            }
        }
    }

    // send the response back to the client
    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, 'directExchange', 'dbResponse');

    // close the statement and connection
    $stmt->close();
    $dbConnection->close();
};

// consume the messages from the queue
$channel->basic_consume('databaseQueue', '', false, true, false, false, $callback);

// wait for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

// close the rabbitmq connection
closeRabbit($connection, $channel);
