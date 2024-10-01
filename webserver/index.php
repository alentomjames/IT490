<?php
// Start the session
session_start();
$_SESSION["userID"] = "1";
$_SESSION["loggedIn"] = false;
// Check if the user is logged in by checking if they have a session token stored in the session storage 
if (isset($_SESSION['userID'])) {
    $_SESSION["loggedIn"] = true;
    echo $_SESSION['userID'];
  }  else {
    echo['NOT WORKING'];
  }
// If they aren't logged in then display the buttons for login or sign up on the navbar

// If they are logged in then display a "Welcome [user]" text at the top where the buttons would usually be 

// Add a logout button if the user is logged in to delete the session token

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
    <!-- Add a php if statement that changes the navbar based on if theres a session id and change it to display the welcome user---> 
	<? php 
        if ($_SESSION['loggedIn'] == true){

    ?>
    <nav class="navbar">
		<a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <li><button onclick="location.href='login.php'">Login</button></li>
            <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
        </ul>
    </nav>

    <?php
        } else {
    ?>
    <nav class="navbar">
		<a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <p class="nav-title">Welcome User</p>
            <li><button onclick="location.href='logout.php'">Logout</button></li>
            <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
        </ul>
    </nav>
    <?php
        } 
    ?>

</body>
</html>
