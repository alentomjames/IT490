<?php
// Start the session
session_start();

// Check if the user is logged in by checking if they have a user id stored in the session storage 

// If they are logged in then redirect them to the index.php page 
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Page</title>
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
        <form name="signupForm" action="../php/sign_up.php" method="post" onsubmit="return validateSignupForm()">
            <h2>Sign Up</h2>
            <div>
                <label for="user_id">Username </label>
                <input type="text" name="user_id" id="user_id" required/>
            </div>
            <div>
                <label for="user_pwd">Password </label>
                <input type="password" name="user_pwd" id="user_pwd" required/>
            </div>
            <div>
                <label for="user_pwd_confirm">Confirm Password </label>
                <input type="password" name="user_pwd_confirm" id="user_pwd_confirm" required/>
            </div>
            <div class="button">
                <input type="submit" value="Sign Up">
            </div>
        </form>
    </div>
</body>
</html>
