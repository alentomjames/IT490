<?php
session_start();

require_once '../vendor/autoload.php';
require_once '../db_connection.php'; // file has db connection
require_once '../rmq_connection.php'; // how I connect to RabbitMQ

$userId = $_SESSION['userID'] = 1; // Hardcoded user ID for testing
$loggedIn = isset($userId);
$userName = $loggedIn ? $name : null;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <li><a href="index.php">Home</a></li>
            <li><a href="watchlistPage.php">Watchlist</a></li>
            <?php if ($loggedIn): ?>
                <li><span>Welcome, <?php echo htmlspecialchars($userName); ?></span></li>
                <li><a href="logout.php">Logout</a></li>
            <?php else: ?>
                <li><a href="login.php">Login</a></li>
                <li><a href="sign_up.php">Sign Up</a></li>
            <?php endif; ?>
        </ul>
    </nav>

    <header class="hero-section">
        <h1>Welcome to BreadWinners</h1>
        <p>Your ultimate movie watchlist manager.</p>
    </header>

    <section class="features">
        <div class="feature-item">
            <h2>Track Your Favorites</h2>
            <p>Keep a list of movies you want to watch.</p>
        </div>
        <div class="feature-item">
            <h2>Get Recommendations</h2>
            <p>Receive personalized movie recommendations.</p>
        </div>
        <div class="feature-item">
            <h2>Share with Friends</h2>
            <p>Share your watchlist with friends and family.</p>
        </div>
    </section>
</body>

</html>