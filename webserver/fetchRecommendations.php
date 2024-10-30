<?php
require_once 'vendor/autoload.php';  
require 'rabbitmq_connection.php';

// Setting type thats being sent to the DMZ
$type = 'reccomendations';

//setting movieId
$movieId = isset($_GET['movieId']) ? (int)$_GET['movieId'] : null;

// Sending request 
sendRequest($type, $movieId, 'frontendForDMZ');

$recommendationResults = recieveDMZ();

if ($recommendationResults){
    header('Content-Type: application/json');
    echo json_encode($recommendationResults);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>