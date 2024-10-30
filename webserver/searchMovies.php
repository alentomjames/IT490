<?php
require_once 'vendor/autoload.php';  
require 'rabbitmq_connection.php';

// Setting page & query parameter 
$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Setting type thats being sent to the DMZ
$type = 'movie_search';

// Sending request 
sendRequest($type, $page, 'frontendForDMZ');

$filteredMovies = recieveDMZ();

if ($filteredMovies){
    header('Content-Type: application/json');
    echo json_encode($filteredMovies);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>