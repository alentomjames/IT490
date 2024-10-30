<?php
session_start();
$loggedIn = isset($_SESSION['userID']);

// if (!$loggedIn) {
//     header('Location: login.php');
//     exit();
// }

require_once 'rabbitmq_connection.php';
require_once('vendor/autoload.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


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
            <?php $movieDetails = getMovieDetails($movie['id']); ?>
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