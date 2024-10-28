<?php
session_start();
require_once 'rabbitmq_connection.php';
require_once 'vendor/autoload.php';  

use PhpAmqpLib\Message\AMQPMessage;

if (!isset($_SESSION['userID'])) {
    header('Location: login.php'); // Redirect if the user is not logged in
    exit;
}

function removeFromWatchlist($movieId, $userId)
{
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('frontendQueue', false, true, false, false);

    $data = json_encode([
        'type'     => 'remove_from_watchlist',
        'movie_id' => $movieId,
        'user_id'  => $userId
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendQueue');
    closeRabbit($connection, $channel);

    receiveRemoveResponse();
}

$userId = $_SESSION['userID'];
$watchlist = fetchWatchlist($userId);

function fetchWatchlist($userId)
{
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('frontendQueue', false, true, false, false);

    $data = json_encode([
        'type'   => 'get_watchlist',
        'user_id' => $userId
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendQueue');
    closeRabbit($connection, $channel);

    return receiveWatchlistResponse();
}

function receiveRemoveResponse()
{
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('databaseQueue', false, true, false, false);

    $callback = function ($msg) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            echo json_encode(['success' => true, 'message' => 'Movie removed from watchlist']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove movie from watchlist']);
        }
    };

    $channel->basic_consume('databaseQueue', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}

function receiveWatchlistResponse()
{
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('databaseQueue', false, true, false, false);

    $watchlist = [];

    $callback = function ($msg) use (&$watchlist) {
        $response = json_decode($msg->body, true);
        if ($response['type'] === 'success') {
            $watchlist = $response['watchlist'];
        }
    };

    $channel->basic_consume('databaseQueue', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
    return $watchlist;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Watchlist</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>

<body>
    <h1>Your Watchlist</h1>
    <div class="watchlist-container">
        <?php if (!empty($watchlist)): ?>
            <?php foreach ($watchlist as $movie): ?>
                <div class="watchlist-item">
                    <img src="https://image.tmdb.org/t/p/w200<?php echo $movie['poster_path']; ?>" alt="<?php echo $movie['title']; ?>">
                    <p><?php echo $movie['title']; ?></p>
                    <button onclick="removeFromWatchlist(<?php echo $movie['movie_id']; ?>)" class="remove-button">Remove</button>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Your watchlist is empty!</p>
        <?php endif; ?>
    </div>
</body>

</html>