#!/usr/bin/php
<?php
require_once 'db_connection.php';
require_once 'auth/register.php';
require_once 'auth/login.php';
// $dbConnection = getDbConnection();

// $stmt = $dbConnection->prepare("INSERT INTO users (username, user_pwd, name) VALUES (?, ?, ?)");
// $stmt->bind_param("sss", $username, $hashed_password, $name);

// // Example data
// $username = 'exampleUser';
// $hashed_password = password_hash('password123', PASSWORD_DEFAULT); // Hash the password
// $name = 'John Doe';

// // Execute the prepared statement
// $stmt->execute();


// Check if the insertion was successful
// if ($stmt->affected_rows > 0) {
//     echo "New user added successfully!";
// } else {
//     echo "Error: " . $stmt->error;
// }

// // Close the statement and connection
// $stmt->close();
// $dbConnection->close();

//register('bill', 'billsworld', 'password');
login('billsworld', 'password');

?>