<?php

require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\Exception\RequestException;
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
// Get the RabbitMQ connection

if ($cluster == 'QA') {
    list($connection, $channel) = getQARabbit();
} else if ($cluster == 'PROD') {
    list($connection, $channel) = getProdRabbit();
} else {
    list($connection, $channel) = getRabbit();
}
echo "Connected to RabbitMQ\n";


// Declare the queue to listen to
$channel->queue_declare('frontendForDMZ', false, true, false, false);
echo "Declared queue 'frontendForDMZ'\n";

$channel->queue_declare('dmzForFrontend', false, true, false, false);
echo "Declared queue 'dmzForFrontend'\n";

// Process the login/register requests
$callback = function ($msg) use ($channel) {
    echo "Received a message\n";

    $data = json_decode($msg->body, true);
    echo "Decoded message data: ";
    print_r($data);

    $type = $data['type'] ?? null;
    $parameter = $data['parameter'] ?? null;

    if (!$type || !$parameter) {
        error_log("Missing 'type' or 'parameter' in the message");
        echo "Error: Missing 'type' or 'parameter'\n";
        return;
    }

    echo "Message type: $type\n";
    echo "Parameter: $parameter\n";

    $response = null;
    switch ($type) {
        case 'movie_details':
            $url = "https://api.themoviedb.org/3/movie/{$parameter}?language=en-US";
            echo "Fetching movie details for URL: $url\n";
            //$response = fetchDetails($type, $parameter, $url);
            $response = json_encode([
                'type' => 'success',
                'data' => [
                    'title' => 'Movie Title',
                    'overview' => 'Movie overview',
                    'release_date' => '2021-01-01',
                    'runtime' => 120,
                    'vote_average' => 7.5,
                    'genres' => [
                        ['id' => 1, 'name' => 'Action'],
                        ['id' => 2, 'name' => 'Adventure'],
                    ],
                    'budget' => 150000000,
                    'revenue' => 500000000,
                    'production_companies' => [
                        ['id' => 1, 'name' => 'Company A'],
                        ['id' => 2, 'name' => 'Company B'],
                    ],
                    'production_countries' => [
                        ['iso_3166_1' => 'US', 'name' => 'United States of America'],
                        ['iso_3166_1' => 'CA', 'name' => 'Canada'],
                    ],
                    'spoken_languages' => [
                        ['iso_639_1' => 'en', 'name' => 'English'],
                        ['iso_639_1' => 'fr', 'name' => 'French'],
                    ],
                    'status' => 'Released',
                    'tagline' => 'An epic movie',
                    'popularity' => 100.0,
                    'vote_count' => 2000,
                    'video' => false,
                    'adult' => false,
                    'backdrop_path' => '/path/to/backdrop.jpg',
                    'poster_path' => '/path/to/poster.jpg',
                    'homepage' => 'https://www.example.com',
                    'imdb_id' => 'tt1234567',
                    'original_language' => 'en',
                    'original_title' => 'Original Movie Title',
                    'reccomendations' => [
                        ['id' => 1, 'title' => 'Reccomendation 1'],
                        ['id' => 2, 'title' => 'Reccomendation 2'],
                    ],
                    'images' => [
                        ['file_path' => '/path/to/image1.jpg'],
                        ['file_path' => '/path/to/image2.jpg'],
                    ],
                    'credits' => [
                        'cast' => [
                            ['id' => 1, 'name' => 'Actor 1', 'character' => 'Character 1'],
                            ['id' => 2, 'name' => 'Actor 2', 'character' => 'Character 2'],
                        ],
                        'crew' => [
                            ['id' => 3, 'name' => 'Crew 1', 'job' => 'Job 1'],
                            ['id' => 4, 'name' => 'Crew 2', 'job' => 'Job 2'],
                        ],
                    ],
                    
                ],
            ]);
            break;
        case 'reccomendations':
            $url = "https://api.themoviedb.org/3/movie/{$parameter}/recommendations?language=en-US&page=1";
            echo "Fetching reccomendations: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'movie_gallery':
            $url = "https://api.themoviedb.org/3/movie/{$parameter}/images?language=en-US";
            echo "Fetching movie gallery: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'search_actors':
            $url = "https://api.themoviedb.org/3/search/person?query={$parameter}&include_adult=false&language=en-US&page=1";
            echo "Searching actors: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'movie_credits':
            $url = "https://api.themoviedb.org/3/person/{$parameter}/movie_credits?language=en-US&page=1";
            echo "Fetching movie credits: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'search_movie':
            $url = "https://api.themoviedb.org/3/search/movie?query={$parameter}&include_adult=false&language=en-US&page=1";
            $type = 'search_movie';
            // parameter = movieTitle , aka movie name
            // no path parameters, only query parameters available
            echo "Fetching search details for URL: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'discover_movies':
            // https://api.themoviedb.org/3/discover/movie
            // request = https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&page=1&sort_by=popularity.desc
            $url = "https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&page={$parameter}";
            echo "Fetching discover movie details for URL: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'trending_movies':
            $url = "https://api.themoviedb.org/3/trending/movie/{$parameter}?language=en-US";
            echo "Fetching trending movie details for URL: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;
        default:
            echo "Unrecognized type: $type\n";
            return;
    }

    // Send the response back to the client
    if ($response) {
        echo "Sending response back to client: $response\n";
        $compressedResponse = base64_encode(gzencode($response));
        $responseMsg = new AMQPMessage($compressedResponse, ['delivery_mode' => 2]);
        error_log("DMZ about to send: " . $response);
        $channel->basic_publish($responseMsg, 'directExchange', 'dmzForFrontend');
        echo "Response sent\n";
    } else {
        echo "No response generated to send\n";
    }
};

// Consume the messages from the queue
$channel->basic_consume('frontendForDMZ', '', false, true, false, false, $callback);
echo "Waiting for messages on 'frontendForDMZ'\n";

// Wait for messages
while ($channel->is_consuming()) {
    $channel->wait();
    echo "Waiting for the next message...\n";
}

// Close the RabbitMQ connection
closeRabbit($connection, $channel);
echo "RabbitMQ connection closed\n";

function fetchDetails($type, $parameter, $url)
{
    echo "Starting fetchDetails for $type with parameter $parameter\n";

    try {
        // Call the API to get a response
        $client = new \GuzzleHttp\Client();
        $response = $client->request('GET', $url, [
            'headers' => [
                'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTE3NDI3NS44MTA5NTUsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.3wTZmroEtn8GSIHD92r7p-3G4iAjPMHZa3aojxycDIM',
                'accept' => 'application/json',
            ],
        ]);
        echo "API request successful\n";
        $responseBody = json_decode($response->getBody(), true);
        echo "API response body: ";
        print_r($responseBody);
        return json_encode([
            'type' => 'success',
            'data' => $responseBody,
        ]);

    } catch (RequestException $e) {
        // Handle exceptions, especially if the resource is not found (404)
        echo "API request failed\n";
        if ($e->hasResponse()) {
            $statusCode = $e->getResponse()->getStatusCode();
            echo "HTTP status code: $statusCode\n";
            if ($statusCode === 404) {
                echo "Resource not found (404)\n";
                return json_encode([
                    'type' => 'failure',
                    'message' => 'Resource not found',
                ]);
            }
        }

        // Log other exceptions or handle them accordingly
        error_log("Error fetching details: " . $e->getMessage());
        echo "Error message: " . $e->getMessage() . "\n";
        return json_encode([
            'type' => 'failure',
            'message' => 'An error occurred while fetching details',
        ]);
    }
}

