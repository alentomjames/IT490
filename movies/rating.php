<?php

require_once './vendor/autoload.php';
require_once 'db_connection.php';
require_once 'rmq_connection.php';

$dbConnection = getDbConnection();

function rateMovie(int $movieId, int $userId, int $rating)
{
    global $dbConnection;

    if ($rating > 5 || $rating < 1) {
        return json_encode(['type' => 'failure', 'reason' => 'Invalid rating value\n']);
    }

    //closes any other stmt objects 
    if (isset($stmt) && $stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    $checkQuery = 'SELECT rating FROM rating WHERE movie_id = ? AND user_id = ?';
    $stmt = $dbConnection->prepare($checkQuery);
    $stmt->bind_param("ii", $movieId, $userId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $updateQuery = 'UPDATE rating SET rating = ? WHERE movie_id = ? AND user_id = ?';
        $stmt = $dbConnection->prepare($updateQuery);
        $stmt->bind_param("iii", $rating, $movieId, $userId);
    } else {
        $stmt->close();
        $insertQuery = 'INSERT INTO rating (movie_id, user_id, rating) VALUES (?, ?, ?)';
        $stmt = $dbConnection->prepare($insertQuery);
        $stmt->bind_param("iii", $movieId, $userId, $rating);
    }

    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        return json_encode(['type' => 'success']);
    } else {
        return json_encode(['type' => 'failure', 'reason' => 'Unable to add/update rating']);
    }
}

function getMovieRating(int $movieId, int $userId)
{
    global $dbConnection;

    $selectQuery = 'SELECT rating FROM rating WHERE movie_id = ? AND user_id = ?';
    $stmt = $dbConnection->prepare($selectQuery);
    $stmt->bind_param("ii", $movieId, $userId);
    $stmt->execute();
    $rating = null;
    $stmt->bind_result($rating);

    if ($stmt->fetch()) {
        return ['type' => 'success', 'rating' => $rating];
    } else {
        return ['type' => 'failure', 'reason' => 'No rating found for this movie and user'];
    }
}
