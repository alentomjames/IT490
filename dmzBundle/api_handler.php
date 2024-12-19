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
            $response = json_encode([
                'type' => 'success',
                'data' => [
                    'adult' => false,
                    'backdrop_path' => '/hZkgoQYus5vegHoetLkCJzb17zJ.jpg',
                    'belongs_to_collection' => null,
                    'budget' => 63000000,
                    'genres' => [
                        ['id' => 18, 'name' => 'Drama']
                    ],
                    'homepage' => 'http://www.foxmovies.com/movies/fight-club',
                    'id' => 550,
                    'imdb_id' => 'tt0137523',
                    'origin_country' => ['US'],
                    'original_language' => 'en',
                    'original_title' => 'Fight Club',
                    'overview' => 'A ticking-time-bomb insomniac and a slippery soap salesman channel primal male aggression into a shocking new form of therapy. Their concept catches on, with underground "fight clubs" forming in every town, until an eccentric gets in the way and ignites an out-of-control spiral toward oblivion.',
                    'popularity' => 95.569,
                    'poster_path' => '/pB8BM7pdSp6B6Ih7QZ4DrQ3PmJK.jpg',
                    'production_companies' => [
                        ['id' => 711, 'logo_path' => '/tEiIH5QesdheJmDAqQwvtN60727.png', 'name' => 'Fox 2000 Pictures', 'origin_country' => 'US'],
                        ['id' => 508, 'logo_path' => '/7cxRWzi4LsVm4Utfpr1hfARNurT.png', 'name' => 'Regency Enterprises', 'origin_country' => 'US'],
                        ['id' => 4700, 'logo_path' => '/A32wmjrs9Psf4zw0uaixF0GXfxq.png', 'name' => 'The Linson Company', 'origin_country' => 'US'],
                        ['id' => 25, 'logo_path' => '/qZCc1lty5FzX30aOCVRBLzaVmcp.png', 'name' => '20th Century Fox', 'origin_country' => 'US'],
                        ['id' => 20555, 'logo_path' => '/hD8yEGUBlHOcfHYbujp71vD8gZp.png', 'name' => 'Taurus Film', 'origin_country' => 'DE']
                    ],
                    'production_countries' => [
                        ['iso_3166_1' => 'DE', 'name' => 'Germany'],
                        ['iso_3166_1' => 'US', 'name' => 'United States of America']
                    ],
                    'release_date' => '1999-10-15',
                    'revenue' => 100853753,
                    'runtime' => 139,
                    'spoken_languages' => [
                        ['english_name' => 'English', 'iso_639_1' => 'en', 'name' => 'English']
                    ],
                    'status' => 'Released',
                    'tagline' => 'Mischief. Mayhem. Soap.',
                    'title' => 'Fight Club',
                    'video' => false,
                    'vote_average' => 8.4,
                    'vote_count' => 29512
                ]
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
        $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
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

        // Get the response body as a string
        $responseBody = $response->getBody()->getContents();

        // Decode it to check the structure
        $decodedResponse = json_decode($responseBody, true);

        error_log("API Response Body: " . print_r($decodedResponse, true));

        // Return the properly structured response
        return json_encode([
            'type' => 'success',
            'data' => $decodedResponse
        ]);

    } catch (RequestException $e) {
        // Rest of error handling remains the same
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

        error_log("Error fetching details: " . $e->getMessage());
        return json_encode([
            'type' => 'failure',
            'message' => 'An error occurred while fetching details'
        ]);
    }
}