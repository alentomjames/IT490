<?php

require_once '../vendor/autoload.php';
require_once '../backendBundle/db_connection.php'; // file has db connection
require_once './rabbitmq_connection.php'; // how I connect to RabbitMQ
require_once 'session.php';
require_once 'constraints.php';

function login(string $username, string $password)
{
    global $USERNAME_MAX_LENGTH, $USERNAME_MIN_LENGTH, $USERNAME_PATTERN, $PASSWORD_MAX_LENGTH, $PASSWORD_MIN_LENGTH;

    $type = 'login';

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

    // query that fetches the user's ID, hashed password and name
    $query = "SELECT id, name, password FROM users WHERE username = ?";
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param('s', $username);
    try {
        //code...
        $stmt->execute();
    } catch (\Throwable $th) {
        //throw $th;
        echo "Error executing login query";
    }

    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $storedHash); // fetches the results
        $stmt->fetch();

        // plaintext password is compared to the hashed password
        if (password_verify($password, $storedHash)) {
            //$sessionId = sessionCreate($id);
            // if password = hashed password, login is successful
            $response = json_encode([
                'type'    => 'success',
                'name'    => $name,
                'userID'  => $id,
                //'sessionID' => $sessionId
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
    $stmt->close();
    $dbConnection->close();
    echo "Closed connection\n";
    echo $response . "\n";
    return $response;
}
