<?php
require_once 'vendor/autoload.php';
require 'rabbitmq_connection.php';

$movieid= isset($_GET['movieid']) ? (int)$_GET['movieid'] : 1;

// Setting type thats being sent to the DMZ
$type = 'recommendations';

// Sending request
sendRequest($type, $movieId, 'frontendForDMZ');

$recommendationsData = recieveDMZ();

if ($recommendationsData) {
    return isset($recommendationsData['results'][0]) ? $recommendationsData['results'][0] : null;
} else {
    return [];
}
?>