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

        mysqli_report(MYSQLI_REPORT_STRICT | MYSQLI_REPORT_ERROR);


        $conn = new mysqli($host, $username, $password, $database);
        $conn->close();
        return true;
    } catch (mysqli_sql_exception $e) {

        echo "Connection failed: " . $e->getMessage() . "\n";
        return false;
    } catch (Exception $e) {

        echo "Exception: " . $e->getMessage() . "\n";
        return false;
    }
}
?>