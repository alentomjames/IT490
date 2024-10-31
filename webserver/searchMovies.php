<?php
require_once 'vendor/autoload.php';  
require 'rabbitmq_connection.php';

// Setting page parameter 
$query = isset($_GET['query']) ? (int)$_GET['query'] : 1;

// Setting type thats being sent to the DMZ
$type = 'search_movies';

// Sending request 
sendRequest($type, $query, 'frontendForDMZ');

$moviesData = recieveDMZ();

if ($moviesData){
    header('Content-Type: application/json');
    echo json_encode($moviesData);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>