<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';

// Setting page parameter
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

// Setting type thats being sent to the DMZ
$type = 'discover_movies';
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
// Sending request
sendRequest($type, $page, 'frontendForDMZ', $cluster);

$moviesData = recieveDMZ($cluster);

if ($moviesData){
    header('Content-Type: application/json');
    echo json_encode($moviesData);
} else {
    error_log('Failed to retrieve movie data!!!!!! RAAAAHHH');
    exit;
}
?>