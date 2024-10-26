<?php

require_once './vendor/autoload.php';
require_once 'db_connection.php'; // file has db connection
require_once 'rmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();

// add to watchlist paramter movie id user id
function addToWatchlist(int $movieId, int $userId)
{
    global $dbConnection;

    $query = ('INSERT INTO watchlist (movie_id, user_id) VALUES (?, ?)');
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("ii", $movieId, $userId);
    try {
        //code...
        $stmt->execute();
    } catch (\Throwable $th) {
        //throw $th;
        echo "Wrong movie_id format\n";
    }

    $stmt->store_result();

    if ($stmt->num_rows() > 0) {
        echo "Error, movie already exists in watchlist\n";
    }
}

function removeFromWatchlist(int $movieId, int $userId)
{

    global $dbConnection;

    $query = ('DELETE FROM watchlist WHERE movie_id = ? AND user_id = ?');
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("ii", $movieId, $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->affected_rows == 0) {
        echo "Error, movie does not exist in watchlist\n";
    }
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
        $watchlist[] = $row['movie_id']; // collects a user's watchlist
    }

    $stmt->close();
    return $watchlist;
}

//addToWatchlist('movie1', 1);
addToWatchlist(222, 1);
addToWatchlist(333, 1);
addToWatchlist(333, 2);
addToWatchlist(333, 2);

//removeFromWatchlist('movie1', 1);

//getFromWatchlist(1);

$userWatchlist = getFromWatchlist(2);

var_dump($userWatchlist);
