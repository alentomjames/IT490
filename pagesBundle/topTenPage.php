<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

// if (!$loggedIn) {
//     header('Location: login.php');
//     exit();
// }

require_once '../rabbitmq_connection.php';
require_once('../vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$envFilePath = '.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
} else {
    error_log('Here is the cluster: ' . $getenv['CLUSTER']);
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($cluster === null) {
    error_log('CLUSTER not set in .env file');
    exit;
}
$client = new \GuzzleHttp\Client();

function getMovieDetails($movie_id){
    global $cluster;
    error_log("Cluster: $cluster");
    $type = 'movie_details';
    error_log("Type: $type");
    sendRequest($type, 'day', 'frontendForDMZ', $cluster);
    error_log("Sent request");
    error_log('Sent message to RabbitMQ');
    $recieveDmz = recieveDMZ($cluster);
    error_log("Recieved DMZ in index.php");

    error_log("DMZ response structure: " . print_r($recieveDmz, true));

    if ($recieveDmz === null || !isset($recieveDmz['type']) || $recieveDmz['type'] !== 'success') {
        error_log("Invalid response from DMZ");
        error_log("Response missing 'type' field. Response structure: " . json_encode($recieveDmz, JSON_PRETTY_PRINT));
        error_log("Response type is not 'success'. Type is: " . $recieveDmz['type']);
        return ['results' => []];
    }

    if (!isset($recieveDmz['data']) || !isset($recieveDmz['data']['results'])) {
        error_log("Response doesn't contain expected data structure");
        return ['results' => []];
    }

    return $recieveDmz['data'];
}


$loggedIn = isset($_SESSION['userID']);
$userName = $loggedIn ? $_SESSION['name'] : null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Rated Movies - BreadWinners</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="../script.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', loadTopTenMovies);
    </script>
</head>

<body>
<nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>

        <ul class="nav-links">
        <?php if ($loggedIn): ?>
            <li>
                <button onclick="location.href='/pagesBundle/Reccomend.php'" class="smoothie-button">
                    <img src="smoothie.png" alt="Movie Smoothie" class="smoothie-icon">
                </button>
            </li>
            <li><button onclick="location.href='/pagesBundle/recBasedonLikesPage.php'">Recommended Movies</button></li>
            <li><button onclick="location.href='/pagesBundle/MovieTrivia.php'">Movie Trivia</button></li>
            <li><button onclick="location.href='/pagesBundle/watchlistPage.php'">Watch Later</button></li>
            <li><button onclick="location.href='/pagesBundle/topTenPage.php'">Top Movies</button></li>
            <li><button onclick="location.href='/loginBundle/logout.php'">Logout</button></li>
        <?php else: ?>
            <li><button onclick="location.href='/loginBundle/login.php'">Login</button></li>
            <li><button onclick="location.href='/loginBundle/sign_up.php'">Sign Up</button></li>
        <?php endif; ?>
    </ul>
</nav>

    <div class="welcome-message">
        <h1>These are our top-rated movies on our page</h1>
    </div>

    <div class="top-movies" id="top-movies-container">
        <!-- Movies will be loaded here by JavaScript -->
    </div>

</body>

</html>