<?php
// Import dependencies and load required files
require_once 'vendor/autoload.php';
require_once 'rabbitmq_connection.php';

// Log that the script has started
error_log("Starting DMZ Test Page...");

// Parse .env file
$envFilePath = __DIR__ . '/.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit('Failed to parse .env file');
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;
if ($cluster === null) {
    error_log('CLUSTER not set in .env file');
    exit('CLUSTER not set in .env file');
}

error_log("Cluster detected: $cluster");

// Declare a random movie ID to test
$testMovieId = 550;

try {
    // Send a request for movie details
    sendRequest('movie_details', $testMovieId, 'frontendForDMZ', $cluster);
    error_log("Sent request for movie ID: $testMovieId to DMZ");
    
    // Receive the response from DMZ
    $response = recieveDMZ($cluster);
    error_log("Received response from DMZ");

    // Output the results on the page for quick debugging
    if ($response) {
        echo "<h1>DMZ Test Results</h1>";
        echo "<pre>";
        print_r($response);
        echo "</pre>";
    } else {
        echo "<h1>DMZ Test Failed</h1>";
        echo "<p>No response received from DMZ.</p>";
    }

} catch (Exception $e) {
    error_log('Error during DMZ test: ' . $e->getMessage());
    echo "<h1>Error during DMZ test</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}

?>
