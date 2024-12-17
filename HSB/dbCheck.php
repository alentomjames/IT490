<?php
// Database configuration
$host = '172.29.123.139';
$username = 'rabbitProd';
$password = 'tetra2345';
$database = 'it490db';

// Check database connection
function isDatabaseUp($host, $username, $password, $database)
{
    try {
        $conn = new mysqli($host, $username, $password, $database);
        if ($conn->connect_error) {
            echo "Connection failed: " . $conn->connect_error . "\n";
            return false;
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

// Return status
if (isDatabaseUp($host, $username, $password, $database)) {
    echo "Database UP\n";
    exit(0);
} else {
    echo "Database DOWN\n";
    exit(1);
}
?>