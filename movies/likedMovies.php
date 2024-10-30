<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();

function getFromRatings(int $userId)
{
    global $dbConnection;

    $query = "SELECT movie_id FROM ratings WHERE user_id = ? AND rating > 4";
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    $watchlist = [];
    while ($row = $result->fetch_assoc()) {
        $watchlist[] = $row['movie_id'];
    }

    $stmt->close();

    if (!empty($watchlist)) {
        $response = json_encode(['type' => 'success', 'watchlist' => $watchlist]);
    } else {
        $response = json_encode(['type' => 'failure', 'message' => 'Watchlist is empty']);
    }

    return $response;
}


