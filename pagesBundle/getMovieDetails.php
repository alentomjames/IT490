<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';

// Setting page parameter
$movieId = isset($_GET['movieId']) ? (int)$_GET['movieId'] : 1;

// Setting type thats being sent to the DMZ
$type = 'movie_details';

// Sending request
sendRequest($type, $movieId, 'frontendForDMZ');

$moviesData = recieveDMZ();

if ($moviesData){
    header('Content-Type: application/json');
    echo json_encode($moviesData);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>