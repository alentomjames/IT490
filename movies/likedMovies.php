<?php

require_once './vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './rabbitmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();

function getFromRatings(int $userId)
{
    global $dbConnection;

    $query = "SELECT movie_id FROM rating WHERE user_id = ? AND rating >= 4";
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $liked = [];
    while ($row = $result->fetch_assoc()) {
        $liked[] = $row['movie_id'];
    }

    $stmt->close();

    if (!empty($liked)) {
        $response = json_encode(['type' => 'success', 'liked' => $liked]);
    } else {
        $response = json_encode(['type' => 'failure', 'message' => 'Liked movies is empty']);
        error_log("Liked movies is empty", 3, "/var/log/database/error.log");
    }

    return $response;
}
