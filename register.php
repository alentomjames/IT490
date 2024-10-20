<?php

require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ
require_once 'constraints.php';
require_once __DIR__ . '/vendor/autoload.php';

// use Ramsey\Uuid\Uuid;  Eventually used to generate random ID

function register(
    string $name,
    string $username,
    string $password
) {
    global $TYPES, $USERNAME_MAX_LENGTH, $USERNAME_MIN_LENGTH, $USERNAME_PATTERN, $NAME_MAX_LENGTH, $NAME_MIN_LENGTH, $NAME_PATTERN, $PASSWORD_MAX_LENGTH, $PASSWORD_MIN_LENGTH;

    // $id = UUid::uuid4()->toString();
    $type = 'register';

    if (strlen($name) > strlen($NAME_MAX_LENGTH) || strlen($name) < strlen($NAME_MIN_LENGTH)) {
        echo "Invalid name length\n";
    }

    if (!preg_match($NAME_PATTERN, $name)) {
        echo "Invalid name format\n";
    }

    if (strlen($username) > $USERNAME_MAX_LENGTH || strlen($username) < $USERNAME_MIN_LENGTH) {
        echo "Invalid username length\n";
    }

    if (!preg_match($USERNAME_PATTERN, $username)) {
        echo "Invalid username format\n";
    }

    if (strlen($password) > $PASSWORD_MAX_LENGTH || strlen($password) < $PASSWORD_MIN_LENGTH) {
        echo "Invalid password length\n";
    }

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
        return $response;
    } else {
        // hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

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
            return $response;
        } else {
            // failed to register user
            $response = json_encode(['type' => 'failure', 'reason' => 'Database error']);
            echo "Failed to register user: $username\n";
            return $response;
        }
    }
    $stmt->close();
    $dbConnection->close();
}
