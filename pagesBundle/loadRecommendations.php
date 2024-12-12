<?php
require_once '../vendor/autoload.php';
require '../rabbitmq_connection.php';

$movieid= isset($_GET['movieid']) ? (int)$_GET['movieid'] : 1;

// Setting type thats being sent to the DMZ
$type = 'recommendations';
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

$recommendationsData = recieveDMZ($cluster);

if ($recommendationsData) {
    return isset($recommendationsData['results'][0]) ? $recommendationsData['results'][0] : null;
} else {
    return [];
}
?>