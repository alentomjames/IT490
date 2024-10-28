<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

if (!$loggedIn) {
    header('Location: login.php');
    exit();
}

require_once('../vendor/autoload.php');
require_once 'rabbitmq_connection.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$client = new \GuzzleHttp\Client();

if (isset($_GET['id'])) {
    $movie_id = $_GET['id'];

    $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $movie_id . '?language=en-US', [
        'headers' => [
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
            'accept' => 'application/json',
        ],
    ]);

    $movie = json_decode($response->getBody(), true);

    $recommendationResponse = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $movie_id . '/recommendations?language=en-US&page=1', [
        'headers' => [
            'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
            'accept' => 'application/json',
        ],
    ]);
    $recommendations = json_decode($recommendationResponse->getBody(), true)['results'];

    $title = $movie['title'];
    $vote_average = round($movie['vote_average'] / 2, 1);
    $overview = $movie['overview'];
    $poster = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
    $genres = implode(', ', array_column($movie['genres'], 'name'));
    $languages = implode(', ', array_column($movie['spoken_languages'], 'english_name'));
    $production_companies = implode(', ', array_column($movie['production_companies'], 'name'));
} else {
    echo '<p>No movie ID provided!</p>';
    exit;
}

function sendMessageToQueue($data) {
    list($connection, $channel) = getRabbit();

    $channel->queue_declare('frontendQueue', false, true, false, false);

    $msg = new AMQPMessage(json_encode($data), ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', 'frontendQueue');

    closeRabbit($connection, $channel);

    receiveRabbitMQResponse();
}

function receiveRabbitMQResponse(){
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('databaseQueue', false, true, false, false);

    $callback = function($msg) {
        $response = json_decode($msg->body, true);

        if ($response['type'] === 'success'){
            echo json_encode(['type' => 'success']);
        } else {
            echo json_encode(['type' => 'failure']);
        }
    };

    $channel->basic_consume('databaseQueue', '', false, true, false, false, $callback);

    while ($channel->is_consuming()) {
        $channel->wait();
    }

    closeRabbit($connection, $channel);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);
    sendMessageToQueue($data);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
            <li><button onclick="location.href='logout.php'">Logout</button></li>
        </ul>
    </nav>

    <div class="movie-page">
        <div class="movie-poster">
            <img src="<?php echo $poster; ?>" alt="<?php echo $title; ?> Poster">
        </div>
        <div class="movie-details">
            <h1><?php echo $title; ?> <span class="vote-average"> <?php echo $vote_average; ?> <i class="fa fa-star"></i> </span> </h1>
            <p><strong>Overview:</strong> <?php echo $overview; ?></p>
            <p><strong>Genres:</strong> <?php echo $genres; ?></p>
            <p><strong>Spoken Languages:</strong> <?php echo $languages; ?></p>
            <p><strong>Production Companies:</strong> <?php echo $production_companies; ?></p>

            <button onclick="addToWatchlist(<?php echo $movie_id; ?>)">Add to Watchlist</button>
            <button onclick="removeFromWatchlist(<?php echo $movie_id; ?>)">Remove from Watchlist</button>

            <div class="rating-buttons">
                <p>Rate this movie:</p>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button onclick="rateMovie(<?php echo $movie_id; ?>, <?php echo $i; ?>)"><?php echo $i; ?></button>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="recommendations">
        <h2>Recommendations</h2>
        <div class="recommendation-list">
            <?php foreach ($recommendations as $recommendation): ?>
                <div class="recommendation-item">
                    <a href="MoviePage.php?id=<?php echo $recommendation['id']; ?>">
                        <img src="https://image.tmdb.org/t/p/w200<?php echo $recommendation['poster_path']; ?>" alt="<?php echo $recommendation['title']; ?> Poster">
                        <p><?php echo $recommendation['title']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function addToWatchlist(movieId) {
            const userId = <?php echo $_SESSION['userID']; ?>;
            const data = { movieId, userId, type: 'add_to_watchlist' };

            fetch('moviePage.php?id=<?php echo $movie_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'success') {
                    alert('Movie added to watchlist!');
                } else {
                    alert('Failed to add movie to watchlist.');
                }
            });
        }

        function removeFromWatchlist(movieId) {
            const userId = <?php echo $_SESSION['userID']; ?>;
            const data = { movieId, userId, type: 'remove_from_watchlist' };

            fetch('moviePage.php?id=<?php echo $movie_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'success') {
                    alert('Movie removed from watchlist!');
                } else {
                    alert('Failed to remove movie from watchlist.');
                }
            });
        }

        function rateMovie(movieId, rating) {
            const userId = <?php echo $_SESSION['userID']; ?>;
            const data = { movieId, userId, rating, type: 'rate_movie' };

            fetch('moviePage.php?id=<?php echo $movie_id; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data),
            })
            .then(response => response.json())
            .then(data => {
                if (data.type === 'success') {
                    alert('Rating submitted!');
                } else {
                    alert('Failed to submit rating.');
                }
            });
        }
    </script>
</body>
</html>