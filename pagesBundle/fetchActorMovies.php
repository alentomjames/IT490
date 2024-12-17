
<?php

// require_once '../vendor/autoload.php';

// use GuzzleHttp\Client;

// $client = new Client();
// $apiKey = 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTczMDE0NjIzMi42NTYyMzMsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.LeSCQRrzMForzIXdWNNhdpxeiUaGzojfm5xi-K0Zj-s'; // Replace with your TMDB API key

// $actorId = $_GET['actorId'];
// $response = $client->request('GET', "https://api.themoviedb.org/3/person/{$actorId}/movie_credits", [
//     'headers' => [
//         'Authorization' => 'Bearer ' . $apiKey,
//         'accept' => 'application/json',
//     ],
// ]);

// echo $response->getBody();
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
$actorId = isset($_GET['actorId']) ? (int) $_GET['actorId'] : 1;

// Setting type thats being sent to the DMZ
$type = 'movie_credits';

// Sending request
sendRequest($type, $actorId, 'frontendForDMZ', $cluster);

$moviesData = recieveDMZ($cluster);

if ($moviesData) {
    header('Content-Type: application/json');
    echo json_encode($moviesData);
} else {
    error_log('Failed to retrieve movie data');
    exit;
}
?>