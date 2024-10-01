<?php
// Start the session
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
	<nav class="navbar">
		<a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <li><button onclick="location.href='login.php'">Login</button></li>
            <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
        </ul>
    </nav>
    <div class="modal">
        <form name="loginForm" method="post" action="../php/login_chk.php" onsubmit="return validateLoginForm()">
            <h2>Login</h2>
            <div>
                <label for="user_id">Username </label>
                <input type="text" name="user_id" required/>
            </div>
            <div>
                <label for="user_pwd">Password </label>
                <input type="password" name="user_pwd" required/>
            </div>
            <div class="button">
                <button type="submit">Login</button>
            </div>
        </form>
    </div>
</body>
</html>
