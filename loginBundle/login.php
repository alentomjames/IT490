<?php
// Start the session
session_start();

// Check if the user is logged in by checking if they have a user id stored in the session storage
// If they are logged in then redirect them to the index.php page
if (isset($_SESSION['userID'])) {
    header("Location: ../index.php");
}

// If they aren't logged in then use RabbitMQ to access the database and find a matching userID and password
    // If there is a matching then return the users sessionID and add it to the session storage
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="../script.js" defer></script>
</head>
<body>
	<nav class="navbar">
		<a href="../index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <li><button onclick="location.href='login.php'">Login</button></li>
            <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
        </ul>
    </nav>
    <div class="modal">
        <form name="loginForm" method="post" action="login_chk.php" onsubmit="return validateLoginForm()">
            <h2>Login</h2>
            <div>
                <label for="user_id">Username </label>
                <input type="text" name="username" required/>
            </div>
            <div>
                <label for="user_pwd">Password </label>
                <input type="password" name="password" required/>
            </div>
            <div class="button">
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
</body>
</html>
