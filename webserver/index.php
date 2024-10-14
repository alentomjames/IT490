<?php
// Start the session
session_start();
// Check if the user is logged in by checking if they have a session token stored in the session storage 
$loggedIn = isset($_SESSION['userID']);
?>

<!DOCTYPE html>
<html lang="en">
<head> 
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BreadWinners</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <!-- Add a php if statement that changes the navbar based on if theres a session id and change it to display the welcome user and login --->
    <nav class="navbar">
		<a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <?php if ($loggedIn): ?>
                <!-- If they are logged in then display a "Welcome [user]" text at the top where the buttons would usually be and a logout button --->
                <p class="nav-title">Welcome, <?php echo $_SESSION['name']; ?>!</p>
                <!-- Logout button that calls logout.php to delete the userID from session and redirects them to the login page ---> 
                <li><button onclick="location.href='logout.php'">Logout</button></li>
            <?php else: ?>
                <!-- If they aren't logged in then display the buttons for login or sign up on the navbar --->

            <li><button onclick="location.href='login.php'">Login</button></li>
            <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
            <?php endif; ?>
        </ul>
    </nav>
</body>
</html>