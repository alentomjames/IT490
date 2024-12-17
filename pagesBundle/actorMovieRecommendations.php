<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';

use GuzzleHttp\Client;

$client = new Client();
$apiKey = 'YOUR_API_KEY'; // Replace with your TMDB API key
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actor Movie Recommendations - BreadWinners</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="../script.js" defer></script>
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <li><button onclick="location.href='Reccomend.php'" class="smoothie-button"><img src="smoothie.png" alt="Movie Smoothie" class="smoothie-icon"></button></li>
                <li><button onclick="location.href='recBasedonLikesPage.php'">Recommended Movies</button></li>
                <li><button onclick="location.href='MovieTrivia.php'">Movie Trivia</button></li>
                <li><button onclick="location.href='watchlistPage.php'">Watch Later</button></li>
                <li><button onclick="location.href='topTenPage.php'">Top Movies</button></li>
                <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
                <li><button onclick="location.href='../loginBundle/logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='../loginBundle/login.php'">Login</button></li>
                <li><button onclick="location.href='../loginBundle/sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="search-container">
        <input type="text" id="actor-search-bar" placeholder="Search for actors...">
        <div id="actor-search-results"></div>
    </div>

    <div class="movie-recommendations">
        <h2>Movie Recommendations</h2>
        <div id="movie-recommendation-results"></div>
    </div>
</body>

</html>