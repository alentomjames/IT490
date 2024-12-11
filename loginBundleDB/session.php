<?php

require_once '../vendor/autoload.php';
require_once '../backendBundle/db_connection.php';
require_once 'constraints.php';

use Ramsey\Uuid\Uuid;

// connect to db
$dbConnection = getDbConnection();

// create, read, invalidate, validate
function sessionCreate(string $userId)
{
    global $dbConnection;

    $sessionId = Uuid::uuid4()->toString(); // create new session ID
    $hashedSessionId = hash('sha256', $sessionId); // hash the session ID
    $maxAge = round(microtime(true) * 1000); // current epoch timestamp

    //when session created & exists, delete all sessions and insert new one
    $query = "SELECT * FROM sessions WHERE user_id = ?";
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $query = "DELETE FROM sessions WHERE user_id = ?";
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $stmt->store_result();
    }

    $query = "INSERT INTO sessions (session_id, user_id, max_age)
              VALUES (?, ?, ?)";

    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("sss", $hashedSessionId, $userId, $maxAge);
    $stmt->execute();
    $stmt->close();
    echo "New session started for user $userId.\n";
    // returning normal sessionId because of the pass-the-hash problem
    // https://security.stackexchange.com/questions/221841/hashing-session-id
    return $sessionId;
}
