<?php
session_start();



$loggedIn = isset($_SESSION['userID']);
$userName = $loggedIn ? $_SESSION['name'] : null;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Watchlist</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="../script.js" defer></script>
    <script>
        // display user watchlist
        document.addEventListener('DOMContentLoaded', () => {
            loadWatchlist();
        });
    </script>
</head>

<body>
<nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>

        <button class="hamburger" aria-label="Toggle navigation">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>


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

    <h1>Your Watchlist</h1>
    <div class="watchlist-container">
        <p>Loading your watchlist...</p>
    </div>
</body>

</html>