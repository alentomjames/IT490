<?php
require_once 'vendor/autoload.php';  
require 'rabbitmq_connection.php';

// Setting page & query parameter 
$query = isset($_GET['query']) ? trim($_GET['query']) : '';

// Setting type thats being sent to the DMZ
$type = 'search_movie';

// Sending request 
sendRequest($type, $query, 'frontendForDMZ');

$filteredMovies = recieveDMZ();

if ($filteredMovies){
    header('Content-Type: application/json');
    echo json_encode($filteredMovies);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>