<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';

// Start the session
session_start();
// Check if the user is logged in by checking if they have a session token stored in the session storage
$loggedIn = isset($_SESSION['userID']);
$envFilePath = __DIR__ . '/../.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($cluster === null) {
    error_log('CLUSTER not set in .env file');
    exit;
}
// Get the movie ID from the URL
$movie_id = isset($_GET['id']) ? $_GET['id'] : null;

if ($movie_id) {
    // API key for TMDB
    $type = 'movie_gallery';
    sendRequest($type, $movie_id, 'frontendForDMZ', $cluster);
    $images = recieveDMZ($cluster);

    // Movie images
    if ($images) {
        $backdrops = $images['backdrops'];
        $posters = $images['posters'];
    } else {
        echo '<p>Failed to retrieve images!</p>';
        die('<p>Failed to retrieve images! Please try again later.</p>');
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
    <title>Movie Gallery - BreadWinners</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="../script.js" defer></script>
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
                <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
                <li><button onclick="location.href='../loginBundle/logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='../loginBundle/login.php'">Login</button></li>
                <li><button onclick="location.href='../loginBundle/sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <!-- Movie Gallery Content -->
    <div class="movie-gallery">
        <div class="header-container">
            <a href="moviePage.php?id=<?php echo $movie_id; ?>" class="back-arrow">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1>Movie Gallery</h1>
        </div>
        <h2>Backdrops</h2>
        <div class="gallery-container">
            <?php foreach ($backdrops as $image): ?>
                <div class="gallery-item">
                    <img src="https://image.tmdb.org/t/p/w500<?php echo $image['file_path']; ?>" alt="Backdrop Image">
                </div>
            <?php endforeach; ?>
        </div>
        <h2>Posters</h2>
        <div class="gallery-container">
            <?php foreach ($posters as $image): ?>
                <div class="gallery-item">
                    <img src="https://image.tmdb.org/t/p/w500<?php echo $image['file_path']; ?>" alt="Poster Image">
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>

</html>