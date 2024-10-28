<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();

function addToWatchlist(int $movieId, int $userId)
{
    global $dbConnection;

    $query = 'INSERT INTO watchlist (movie_id, user_id) VALUES (?, ?)';
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("ii", $movieId, $userId);

    try {
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $response = ['type' => 'success', 'message' => 'Movie added to watchlist'];
        } else {
            $response = ['type' => 'failure', 'message' => 'Failed to add movie to watchlist'];
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) { // duplicate entry error code
            $response = ['type' => 'failure', 'message' => 'Movie already exists in watchlist'];
        } else {
            $response = ['type' => 'failure', 'message' => 'Error adding movie: ' . $e->getMessage()];
        }
    }

    $stmt->close();
    return $response;
}

function removeFromWatchlist(int $movieId, int $userId)
{
    global $dbConnection;

    $query = 'DELETE FROM watchlist WHERE movie_id = ? AND user_id = ?';
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("ii", $movieId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response = ['type' => 'success', 'message' => 'Movie removed from watchlist'];
    } else {
        $response = ['type' => 'failure', 'message' => 'Movie does not exist in watchlist'];
    }

    $stmt->close();
    return $response;
}

function getFromWatchlist(int $userId)
{
    global $dbConnection;

    $query = "SELECT movie_id FROM watchlist WHERE user_id = ?";
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
        $response = ['type' => 'success', 'watchlist' => $watchlist];
    } else {
        $response = ['type' => 'failure', 'message' => 'Watchlist is empty'];
    }

    return $response;
}

// Example usage and output
$responseAdd = addToWatchlist(222, 1);
$responseRemove = removeFromWatchlist(222, 1);
$responseGet = getFromWatchlist(1);

var_dump($responseAdd);
var_dump($responseRemove);
var_dump($responseGet);
