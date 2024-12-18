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
            return false;
        }
        $conn->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Return status
if (isDatabaseUp($host, $username, $password, $database)) {
    echo "OK\n";
    exit(0);
} else {
    echo "DOWN\n";
    exit(1);
}
?>
