<?php
// Start the session
session_start();

// Check if the user is logged in by checking if they have a user id stored in the session storage
// If they are logged in then redirect them to the index.php page
if (isset($_SESSION['userID'])) {
    header("Location: ../pagesBundle/index.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up Page</title>
    <link rel="stylesheet" href="../styles.css">
    <script src="../script.js" defer></script>
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
        <form name="signupForm" action="sign_up_chk.php" method="post" onsubmit="return validateSignupForm()">
            <h2>Sign Up</h2>
            <div>
                <label for="user_id">First Name </label>
                <input type="text" name="name" id="name" required/>
            </div>
            <div>
                <label for="user_id">Username </label>
                <input type="text" name="username" id="username" required/>
            </div>
            <div>
                <label for="user_pwd">Password </label>
                <input type="password" name="password" id="password" required/>
            </div>
            <div>
                <label for="user_pwd_confirm">Confirm Password </label>
                <input type="password" name="passwordConfirm" id="passwordConfirm" required/>
            </div>
            <div class="button">
                <input type="submit" value="Sign Up">
            </div>
        </form>
    </div>
</body>
</html>
