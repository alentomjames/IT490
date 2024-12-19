<?php

require_once '../vendor/autoload.php';
require_once '../backendBundle/db_connection.php'; // file has db connection
require_once '../rabbitmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();


function addComment(int $movieId, int $userId, string $content)
{
    global $dbConnection;

    $query = 'INSERT INTO comments (movie_id, user_id, content) VALUES (?, ?, ?)';
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("iis", $movieId, $userId, $content);

    try {
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $response = json_encode(['type' => 'success', 'message' => 'Comment added successfully']);
        } else {
            error_log("Failed to add comment\n", 3, '/var/log/database/error.log');
            $response = json_encode(['type' => 'failure', 'message' => 'Failed to add comment']);
        }
    } catch (mysqli_sql_exception $e) {
        error_log("Error adding comment: " . $e->getMessage() . "\n", 3, '/var/log/database/error.log');
        $response = json_encode(['type' => 'failure', 'message' => 'Error adding comment: ' . $e->getMessage()]);
    }

    $stmt->close();
    return $response;
}

function deleteComment(int $commentId, int $userId)
{
    global $dbConnection;

    $query = 'DELETE FROM comments WHERE comment_id = ? AND user_id = ?';
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("ii", $commentId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response = json_encode(['type' => 'success', 'message' => 'Comment deleted successfully']);
    } else {
        error_log("Comment does not exist or permission denied\n", 3, '/var/log/database/error.log');
        $response = json_encode(['type' => 'failure', 'message' => 'Comment does not exist or permission denied']);
    }

    $stmt->close();
    return $response;
}

function getCommentsForMovie(int $movieId)
{
    global $dbConnection;

    $query = "SELECT comments.comment_id, comments.content, users.username, comments.created_at 
              FROM comments 
              JOIN users ON comments.user_id = users.id 
              WHERE comments.movie_id = ?
              ORDER BY comments.created_at ASC";
    $stmt = $dbConnection->prepare($query);
    $stmt->bind_param("i", $movieId);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }

    $stmt->close();

    if (!empty($comments)) {
        $response = json_encode(['type' => 'success', 'comments' => $comments]);
    } else {
        error_log("No comments found for movie ID: $movieId\n", 3, "/var/log/database/error.log");
        $response = json_encode(['type' => 'failure', 'message' => 'No comments found']);
    }

    return $response;
}
