<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';
$getenv = parse_ini_file('../.env');

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
$query = isset($_GET['query']) ? (int) $_GET['query'] : '';

// Setting type thats being sent to the DMZ
$type = 'search_movie';

// Sending request
sendRequest($type, $query, 'frontendForDMZ', $cluster);

$moviesData = recieveDMZ($cluster);

if ($moviesData) {
    header('Content-Type: application/json');
    echo json_encode($moviesData);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>