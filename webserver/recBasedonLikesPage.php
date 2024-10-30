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
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <p class="nav-title">Welcome, <?php echo $userName; ?>!</p>
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <li><button onclick="location.href='login.php'">Login</button></li>
                <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>

    <div class="welcome-message">
        <h1>Recommended Movies Based on Your Likes</h1>
    </div>

    <div class="recommendations" id="recommendations-container">
        <!-- Recommendations will be loaded here by JavaScript -->
    </div>

    <script src="script.js"></script>
</body>

</html>