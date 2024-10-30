<?php
require_once 'vendor/autoload.php';
require 'rabbitmq_connection.php';

$movieid= isset($_GET['movieid']) ? (int)$_GET['movieid'] : 1;
// Setting type thats being sent to the DMZ
$type = 'movie_details';

// Sending request
sendRequest($type, $movieId, 'frontendForDMZ');

$movie = recieveDMZ();

if ($movie) {
    return $movie;
} else {
    return null;
}
?>