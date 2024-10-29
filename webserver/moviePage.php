<?php
// Start the session
session_start();
// Check if the user is logged in by checking if they have a session token stored in the session storage 
$loggedIn = isset($_SESSION['userID']);

require_once 'rabbitmq_connection.php';  
require_once('vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Get the movie ID from the URL
$movie_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($movie_id) {
    $type = 'movie_details';
    //Sends request to rabbitMQ_connection.php to call API 
    sendRequest($type, $movie_id);

    //Sends request to rabbitMQ_connection.php to recieve API movie data
    $movie = recieveDMZ();

    // Movie data
    if ($movie){
    $title = $movie['title'];
    $vote_average = round($movie['vote_average'] / 2, 1); 
    $overview = $movie['overview'];
    $poster = 'https://image.tmdb.org/t/p/w500' . $movie['poster_path'];
    $genres = implode(', ', array_column($movie['genres'], 'name'));
    $languages = implode(', ', array_column($movie['spoken_languages'], 'english_name'));
    $production_companies = implode(', ', array_column($movie['production_companies'], 'name'));
    } else {
        echo '<p>Failed to retrieve movie!</p>';
        die('<p>Failed to retrieve movie! Please try again later.</p>');
    }
} else {
    echo '<p>No movie ID provided!</p>';
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?> - BreadWinners</title>
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

    <!-- Movie Content -->
    <div class="movie-page">
        <div class="movie-poster">
            <img src="<?php echo $poster; ?>" alt="<?php echo $title; ?> Poster">
        </div>
        <div class="movie-details">
            <!-- Add to Watchlist Button -->
            <h1>
                <?php echo $title; ?>
                <span
                    class="vote-average"> <?php echo $vote_average; ?> <i class="fa fa-star"></i>
                </span>
                <button
                    onclick="addToWatchlist(<?php echo $movie_id; ?>)" class="watchlist-button">Add to Watchlist <i class="fa-solid fa-check"></i>
                </button>
            </h1>
            <p><strong>Overview:</strong> <?php echo $overview; ?></p>
            <p><strong>Genres:</strong> <?php echo $genres; ?></p>
            <p><strong>Spoken Languages:</strong> <?php echo $languages; ?></p>
            <p><strong>Production Companies:</strong> <?php echo $production_companies; ?></p>
        </div>
    </div>

    <!-- Recommended Movies -->
    <div class="recommendations">
        <h2>Recommended Movies</h2>
        <div class="carousel-container">
            <button class="carousel-button prev" onclick="moveCarousel(-1)">&#10094;</button>
            <div class="recommendation-carousel">
                <?php foreach ($recommendations as $recMovie): ?>
                    <div class="carousel-item">
                        <a href="moviePage.php?id=<?php echo $recMovie['id']; ?>">
                            <img src="https://image.tmdb.org/t/p/w200<?php echo $recMovie['poster_path']; ?>" alt="<?php echo $recMovie['title']; ?> Poster">
                        </a>
                        <p><?php echo $recMovie['title']; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="carousel-button next" onclick="moveCarousel(1)">&#10095;</button>
        </div>
    </div>
</body>
</html>
