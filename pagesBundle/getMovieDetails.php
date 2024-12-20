<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';
$envFilePath = __DIR__ . '/../.env';
$getenv = parse_ini_file($envFilePath);

if ($getenv === false) {
    error_log('Failed to parse .env file');
    exit;
}

$cluster = isset($getenv['CLUSTER']) ? $getenv['CLUSTER'] : null;

if ($cluster === null) {
    error_log('CLUSTER not set in .env file');
    exit;
}
// Setting page parameter
$movieId = isset($_GET['movieId']) ? (int) $_GET['movieId'] : 1;

// Setting type thats being sent to the DMZ
$type = 'movie_details';

// Sending request
sendRequest($type, $movieId, 'frontendForDMZ', $cluster);

$moviesData = recieveDMZ($cluster);

if ($moviesData) {
    header('Content-Type: application/json');
    echo json_encode($moviesData['data']);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>