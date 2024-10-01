<?php
// Start the session
session_start();
$_SESSION["userID"] = "1";

// Check if the user is logged in by checking if they have a user id stored in the session storage 
// Check if the user is logged in by checking if they have a user id stored in the session storage 
if (isset($_SESSION['userID'])) {
    echo $_SESSION['userID'];
  }  else {
    echo['NOT WORKING']
  }
// If they aren't logged in then display the buttons for login or sign up on the navbar

// If they are logged in then display a "Welcome [user]" text at the top where the buttons would usually be 

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
	<nav class="navbar">
		<a href="index.php" class="nav-title">BreadWinners</a>
        <ul class="nav-links">
            <li><button onclick="location.href='login.php'">Login</button></li>
            <li><button onclick="location.href='sign_up.php'">Sign Up</button></li>
        </ul>
    </nav>

<?php
    print_r($_SESSION);
?>

</body>
</html>
