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
        echo "Attempting to report MySQLi errors...\n";
        mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);

        echo "Attempting to connect to the database...\n";
        $conn = new mysqli($host, $username, $password, $database);
        echo "Connection successful!\n";
        $conn->close();
        echo "Connection closed.\n";
        return true;
    } catch (mysqli_sql_exception $e) {
        echo "Connection failed: " . $e->getMessage() . "\n";
        return false;
    } catch (Exception $e) {
        echo "Exception: " . $e->getMessage() . "\n";
        return false;
    }
}

// Call the function to check the database connection
isDatabaseUp($host, $username, $password, $database);
?>