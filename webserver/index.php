<?php
// Start the session
session_start();
$_SESSION["favcolor"] = "yellow";

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
