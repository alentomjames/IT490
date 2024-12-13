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
    <title>Recommended Movies Based on Your Likes - BreadWinners</title>
    <link rel="stylesheet" href="../styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            loadLikedMovies();
            loadRecommendations();
        });
    </script>
</head>

<body>
    <nav class="navbar">
        <a href="../index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <li><button onclick="location.href='Reccomend.php'" class="smoothie-button">
                        <img src="smoothie.png" alt="Movie Smoothie" class="smoothie-icon">
                    </button></li>
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

    <div class="welcome-message">
        <h1>Movies You Liked and Recommendations Based on Them</h1>
    </div>


    <section>
        <h2>Recommended Movies Based on Your Likes</h2>
        <div id="recommendations-container" class="recommendations">
        </div>
    </section>

    <section>
        <h2>Your Liked Movies</h2>
        <div id="liked-movies-container" class="liked-movies">
        </div>
    </section>

    <script src="../script.js"></script>

</body>

</html>