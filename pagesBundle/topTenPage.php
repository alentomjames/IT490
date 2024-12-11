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



// function getMovieDetails($movie_id) {
//     $type = 'movie_details';
//     sendRequest($type, $movie_id, "frontendForDMZ");
//     return recieveDMZ();
// }

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
                    <button onclick="location.href='Reccomend.php'" class="smoothie-button">
                        <img src="smoothie.png" alt="Movie Smoothie" class="smoothie-icon">
                    </button>
                </li>
                <li><button onclick="location.href='recBasedonLikesPage.php'">Recommended Movies</button></li>
                <li><button onclick="location.href='MovieTrivia.php'">Movie Trivia</button></li>
                <li><button onclick="location.href='watchlistPage.php'">Watch Later</button></li>
                <li><button onclick="location.href='topTenPage.php'">Top Movies</button></li>
                <p class="nav-title">Welcome, <?php echo htmlspecialchars($userName); ?>!</p>
                <li><button onclick="location.href='../loginBundle/logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='../loginBundle/login.php'">Login</button></li>
                <li><button onclick="location.href='../loginBundle/sign_up.php'">Sign Up</button></li>
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