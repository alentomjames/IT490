<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

if (!$loggedIn) {
    header('Location: login.php');
    exit();
}

require_once 'rabbitmq_connection.php';
require_once('vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function sendRequest($type, $movie_id) {
    list($connection, $channel) = getRabbit();

    if (!$connection || !$channel) {
        error_log("Failed to connect to RabbitMQ");
        echo '<p>Failed to connect to RabbitMQ</p>';
        return;
    }

    $channel->queue_declare('frontendQueue', false, true, false, false);

    $data = json_encode([
        'type' => $type,
        'movie_id' => $movie_id
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendQueue');

    closeRabbit($connection, $channel);
}

function receiveDMZ() {
    list($connection, $channel) = getRabbit();

    if (!$connection || !$channel) {
        error_log("Failed to connect to RabbitMQ");
        echo '<p>Failed to connect to RabbitMQ</p>';
        return null;
    }

    $channel->queue_declare('databaseQueue', false, true, false, false);

    $response = null;

    $callback = function($msg) use (&$response) {
        $response = json_decode($msg->body, true);
    };

    $channel->basic_consume('databaseQueue', '', false, true, false, false, $callback);

    while (!$response) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);

    return $response;
}

$type = 'top_rated_movies';
sendRequest($type, null);

$topMovies = receiveDMZ();

if (!$topMovies) {
    echo '<p>Failed to retrieve top-rated movies!</p>';
    error_log("Failed to retrieve top-rated movies");
    exit;
}

function getMovieDetails($movie_id) {
    $type = 'moviedetails';
    sendRequest($type, $movie_id);
    return receiveDMZ();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Rated Movies - BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="script.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='login.php'">Login</button></li>
                <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="welcome-message">
        <h1>These are our top-rated movies on our page</h1>
    </div>

    <div class="top-movies">
        <?php foreach (array_slice($topMovies, 0, 10) as $movie): ?>
            <?php
            $movieDetails = getMovieDetails($movie['id']);
            if (!$movieDetails) {
                echo '<p>Failed to retrieve details for movie ID: ' . $movie['id'] . '</p>';
                error_log("Failed to retrieve details for movie ID: " . $movie['id']);
                continue;
            }
            ?>
            <div class="movie-item">
                <a href="moviePage.php?id=<?php echo $movie['id']; ?>">
                    <img src="https://image.tmdb.org/t/p/w200<?php echo $movieDetails['poster_path']; ?>" alt="<?php echo $movieDetails['title']; ?> Poster">
                    <p><?php echo $movieDetails['title']; ?></p>
                    <p class="vote-average"><?php echo round($movieDetails['vote_average'] / 2, 1); ?> <i class="fa fa-star"></i></p>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>