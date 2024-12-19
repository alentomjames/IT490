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
                    'similar_movies' => [
                        ['id' => 1, 'title' => 'Similar Movie 1'],
                        ['id' => 2, 'title' => 'Similar Movie 2'],
                    ],
                    'reviews' => [
                        ['author' => 'Reviewer 1', 'content' => 'Review content 1'],
                        ['author' => 'Reviewer 2', 'content' => 'Review content 2'],
                        ['author' => 'Reviewer 3', 'content' => 'Review content 3'],
                        ['author' => 'Reviewer 4', 'content' => 'Review content 4'],
                        ['author' => 'Reviewer 5', 'content' => 'Review content 5'],
                        ['author' => 'Reviewer 6', 'content' => 'Review content 6'],
                        ['author' => 'Reviewer 7', 'content' => 'Review content 7'],
                        ['author' => 'Reviewer 8', 'content' => 'Review content 8'],
                        ['author' => 'Reviewer 9', 'content' => 'Review content 9'],
                        ['author' => 'Reviewer 10', 'content' => 'Review content 10'],
                        ['author' => 'Reviewer 11', 'content' => 'Review content 11'],
                        ['author' => 'Reviewer 12', 'content' => 'Review content 12'],
                        ['author' => 'Reviewer 13', 'content' => 'Review content 13'],
                        ['author' => 'Reviewer 14', 'content' => 'Review content 14'],
                        ['author' => 'Reviewer 15', 'content' => 'Review content 15'],
                        ['author' => 'Reviewer 16', 'content' => 'Review content 16'],
                        ['author' => 'Reviewer 17', 'content' => 'Review content 17'],
                        ['author' => 'Reviewer 18', 'content' => 'Review content 18'],
                        ['author' => 'Reviewer 19', 'content' => 'Review content 19'],
                        ['author' => 'Reviewer 20', 'content' => 'Review content 20'],
                        ['author' => 'Reviewer 21', 'content' => 'Review content 21'],
                        ['author' => 'Reviewer 22', 'content' => 'Review content 22'],
                        ['author' => 'Reviewer 23', 'content' => 'Review content 23'],
                        ['author' => 'Reviewer 24', 'content' => 'Review content 24'],
                        ['author' => 'Reviewer 25', 'content' => 'Review content 25'],
                        ['author' => 'Reviewer 26', 'content' => 'Review content 26'],
                        ['author' => 'Reviewer 27', 'content' => 'Review content 27'],
                        ['author' => 'Reviewer 28', 'content' => 'Review content 28'],
                        ['author' => 'Reviewer 29', 'content' => 'Review content 29'],
                        ['author' => 'Reviewer 30', 'content' => 'Review content 30'],
                        ['author' => 'Reviewer 31', 'content' => 'Review content 31'],
                        ['author' => 'Reviewer 32', 'content' => 'Review content 32'],
                        ['author' => 'Reviewer 33', 'content' => 'Review content 33'],
                        ['author' => 'Reviewer 34', 'content' => 'Review content 34'],
                        ['author' => 'Reviewer 35', 'content' => 'Review content 35'],
                        ['author' => 'Reviewer 36', 'content' => 'Review content 36'],
                        ['author' => 'Reviewer 37', 'content' => 'Review content 37'],
                        ['author' => 'Reviewer 38', 'content' => 'Review content 38'],
                        ['author' => 'Reviewer 39', 'content' => 'Review content 39'],
                        ['author' => 'Reviewer 40', 'content' => 'Review content 40'],
                        ['author' => 'Reviewer 41', 'content' => 'Review content 41'],
                        ['author' => 'Reviewer 42', 'content' => 'Review content 42'],
                        ['author' => 'Reviewer 43', 'content' => 'Review content 43'],
                        ['author' => 'Reviewer 44', 'content' => 'Review content 44'],
                        ['author' => 'Reviewer 45', 'content' => 'Review content 45'],
                        ['author' => 'Reviewer 46', 'content' => 'Review content 46'],
                        ['author' => 'Reviewer 47', 'content' => 'Review content 47'],
                        ['author' => 'Reviewer 48', 'content' => 'Review content 48'],
                        ['author' => 'Reviewer 49', 'content' => 'Review content 49'],
                        ['author' => 'Reviewer 50', 'content' => 'Review content 50'],
                    ],
                    ]
                ]
            );
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

