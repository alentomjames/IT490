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
    <title>Recommendations</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <li><button onclick="location.href='Reccomend.php'">Recommended Movies</button></li>
                <li><button onclick="location.href='MovieTrivia.php'">Movie Trivia</button></li>
                <li><button onclick="location.href='watchlistPage.php'">Watch Later</button></li>
                <li><button onclick="location.href='topTen.php'">Top Movies</button></li>
                <p class="nav-title">Welcome, <?php echo htmlspecialchars($userName); ?>!</p>
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='login.php'">Login</button></li>
                <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <h1>Based on your Likes You Might Enjoy: </h1>
    <div class="watchlist-container">
        <p>Loading your recommended movies...</p>
    </div>
</body>

</html>