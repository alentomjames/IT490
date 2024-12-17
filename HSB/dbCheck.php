<?php
// Database configuration
$host = '127.0.0.1'; // Replace with DB server IP if external
$username = 'your_db_username';
$password = 'your_db_password';
$database = 'your_db_name';

// Check database connection
try {
    $conn = new mysqli($host, $username, $password, $database);
    if ($conn->connect_error) {
        echo "Database DOWN";
        exit(1);
    }
    echo "Database UP";
    $conn->close();
} catch (Exception $e) {
    echo "Database DOWN";
    exit(1);
}
?>