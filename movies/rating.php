<?php

require_once './vendor/autoload.php';
require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();

function rateMovie(int $movieId, int $userId, int $rating)
{
    global $dbConnection;

    if ($rating > 5 || $rating < 1) {
        return json_encode(['type' => 'failure', 'reason' => 'Invalid rating value\n']);
    }

    $query = ('INSERT INTO rating (movie_id, user_id, rating) VALUES (?, ?, ?)');
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("iii", $movieId, $userId, $rating);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->affected_rows == 0) {
        $query = 'UPDATE rating SET rating = ? WHERE movie_id = ? AND user_id = ?';
        $stmt = $dbConnection->prepare($query);
        $stmt->bind_param("iii", $movieId, $userId, $rating);
        $stmt->execute();
        $stmt->store_result();
    }
}

rateMovie("test", 1, 5);
