<?php

require_once './vendor/autoload.php';
require_once './db_connection.php';

$dbConnection = getDbConnection();

function getTopTenMovies()
{
    global $dbConnection;

    // Query to get top 10 movies based on average rating
    $query = "
        SELECT
            movie_id,
            AVG(rating) AS average_rating
        FROM
            rating
        GROUP BY
            movie_id
        ORDER BY
            average_rating DESC
        LIMIT 10;
    ";

    $stmt = $dbConnection->prepare($query);
    $stmt->execute();
    $movieId = null;
    $averageRating = null;
    $stmt->bind_result($movieId, $averageRating);

    $topMovies = [];
    while ($stmt->fetch()) {
        $topMovies[] = [
            'movie_id' => $movieId,
            'average_rating' => $averageRating
        ];
    }

    $stmt->close();

    if (!empty($topMovies)) {
        return json_encode(['type' => 'success', 'top_movies' => $topMovies]);
    } else {
        error_log("No movies found", 3, "/var/log/database/error.log");
        return json_encode(['type' => 'failure', 'message' => 'No movies found']);
    }
}
