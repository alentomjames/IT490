<?php

require_once 'rabbitmq_connection.php'; // how I connect to RabbitMQ
require_once  'vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$client = new \GuzzleHttp\Client();

// get the rabbitmq connection
list($connection, $channel) = getRabbit();

// Listening on the frontEnd queue for the type of API requests needed 
$channel->queue_declare('frontendQueue', false, true, false, false);

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = $data['type']; // for now types are: movie_details
    $parameter = $data['parameter'];

    switch ($type) {
        case 'movie_details':
            $url = "https://api.themoviedb.org/3/movie/{$parameter}?language=en-US";
            $response = fetchDetails($type, $parameter, $url);
            break;
        case 'person_details':
            $url = "https://api.themoviedb.org/3/person/{$parameter}?language=en-US";
            $response = fetchDetails($type, $parameter, $url);
            break;
    }
    
    // send the response back to the client
    $responseMsg = new AMQPMessage($response, ['delivery_mode' => 2]);
    $channel->basic_publish($responseMsg, 'directExchange', 'dmzQueue');
};


// consume the messages from the queue
$channel->basic_consume('frontendQueue', '', false, true, false, false, $callback);

// wait for messages
while ($channel->is_consuming()) {
    $channel->wait();
}

// close the rabbitmq connection
closeRabbit($connection, $channel);

// userid, sessionID, timestamp

function fetchDetails ($type, $parameter, $url) {
    // Call the API to get a response 
    $response = $client->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTE3NDI3NS44MTA5NTUsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.3wTZmroEtn8GSIHD92r7p-3G4iAjPMHZa3aojxycDIM',
          'accept' => 'application/json',
        ],
      ]);

      $responseBody = json_decode($response->getBody(), true);
      //send back to RabbitMQ

      return $responseBody;
}
