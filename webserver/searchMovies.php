<?php
require_once 'vendor/autoload.php';  
require 'rabbitmq_connection.php';

// Setting page parameter 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Setting type thats being sent to the DMZ
$type = 'search_movie';

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