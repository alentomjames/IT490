<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ
require_once 'constraints.php';

use Ramsey\Uuid\Uuid;  // used to generate random ID

function register(
    string $name,
    string $username,
    string $password
) {
    global $TYPES, $USERNAME_MAX_LENGTH, $USERNAME_MIN_LENGTH, $USERNAME_PATTERN, $NAME_MAX_LENGTH, $NAME_MIN_LENGTH, $NAME_PATTERN, $PASSWORD_MAX_LENGTH, $PASSWORD_MIN_LENGTH;

    //$id = UUid::uuid4()->toString();
    $type = 'register';

    // if (strlen($name) > $NAME_MAX_LENGTH || strlen($name) < $NAME_MIN_LENGTH) {
    //     return json_encode(['type' => 'failure', 'reason' => 'Invalid name length']);
    // }

    // if (!preg_match($NAME_PATTERN, $name)) {
    //     return json_encode(['type' => 'failure', 'reason' => 'Invalid name pattern']);
    // }

    // if (strlen($username) > $USERNAME_MAX_LENGTH || strlen($username) < $USERNAME_MIN_LENGTH) {
    //     return json_encode(['type' => 'failure', 'reason' => 'Invalid username length']);
    // }

    // if (!preg_match($USERNAME_PATTERN, $username)) {
    //     return json_encode(['type' => 'failure', 'reason' => 'Invalid username pattern']);
    // }

    // if (strlen($password) > $PASSWORD_MAX_LENGTH || strlen($password) < $PASSWORD_MIN_LENGTH) {
    //     return json_encode(['type' => 'failure', 'reason' => 'Invalid password length']);
    // }

    $dbConnection = getDbConnection();

    // check for existing username
    $checkQuery = "SELECT username FROM users WHERE username = ?";
    $stmt = $dbConnection->prepare($checkQuery);
    $stmt->bind_param('s', $username);
    try {
        //code...
        $stmt->execute();
    } catch (\Throwable $th) {
        //throw $th;
        echo "Error executing register query";
    }

    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $response = json_encode(['type' => 'failure', 'reason' => 'User already exists']);
        echo "Username already exists: $username\n";
    } else {
        // hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        // insert the new user into the database
        $insertQuery = "INSERT INTO users (id, name, username, password) VALUES (?, ?, ?, ?)";
        $stmt = $dbConnection->prepare($insertQuery);
        $stmt->bind_param('ssss', $id, $name, $username, $hashedPassword);

        if ($stmt->execute()) {
            $id = $stmt->insert_id;
            // successful registration
            // $id = $stmt->insert_id; // insert_id gets the last inserted id
            $response = json_encode([
                'type' => 'success',
                'name' => $name,
                'userID' => $id
            ]);
            echo "New user registered: $username\n";
        } else {
            // failed to register user
            $response = json_encode(['type' => 'failure', 'reason' => 'Database error']);
            echo "Failed to register user: $username\n";
        }
    }
    $stmt->close();
    $dbConnection->close();
    echo "Closed Connection on register";
    return $response;
}
