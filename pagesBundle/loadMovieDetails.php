<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';

$movieid= isset($_GET['movieid']) ? (int)$_GET['movieid'] : 1;
// Setting type thats being sent to the DMZ
$type = 'movie_details';
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
sendRequest($type, $movieId, 'frontendForDMZ', $cluster);

$movie = recieveDMZ($cluster);

if ($movie) {
    return $movie;
} else {
    return null;
}
?>