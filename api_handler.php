<?php

require_once 'webserver/rabbitmq_connection.php';
require_once 'webserver/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\Exception\RequestException;

// Get the RabbitMQ connection

list($connection, $channel) = getRabbit();
echo "Connected to RabbitMQ\n";

// Declare the queue to listen to
$channel->queue_declare('frontendForDMZ', false, true, false, false);
echo "Declared queue 'frontendQueue'\n";

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
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'reccomendations':
            $url = "https://api.themoviedb.org/3/movie/{$parameter}/recommendations?language=en-US&page=1";
            echo "Fetching reccomendations: $url\n";
            $response = fetchDetails($type, $parameter, $url);
            break;

        case 'discover_movies':
            // https://api.themoviedb.org/3/discover/movie
            // request = https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&page=1&sort_by=popularity.desc
            $url = "https://api.themoviedb.org/3/discover/movie?include_adult=false&include_video=false&language=en-US&page={$parameter}";
            echo "Fetching discover movie details for URL: $url\n";
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
        $channel->basic_publish($responseMsg, 'directExchange', 'dmzForFrontend');
    } else {
        echo "No response generated to send\n";
    }
};

// Consume the messages from the queue
$channel->basic_consume('frontendForDMZ', '', false, true, false, false, $callback);
echo "Waiting for messages on 'frontendQueue'\n";

// Wait for messages
while ($channel->is_consuming()) {
    $channel->wait();
    echo "Waiting for the next message...\n";
}

// Close the RabbitMQ connection
closeRabbit($connection, $channel);
echo "RabbitMQ connection closed\n";

function fetchDetails($type, $parameter, $url) {
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
