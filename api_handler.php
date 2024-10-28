<?php

require_once 'webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ
require_once  'webserver/vendor/autoload.php';

use PhpAmpqLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;


// get the rabbitmq connection
list($connection, $channel) = getRabbit();

// Listening on the frontEnd queue for the type of API requests needed 
$channel->queue_declare('frontendQueue', false, true, false, false);

// process the login/register requests
$callback = function ($msg) use ($channel) {
    $data = json_decode($msg->body, true);
    $type = isset($data['type']) ? $data['type'] : null;
    $parameter = isset($data['parameter']) ? $data['parameter'] : null;
    
    if (!$type || !$parameter) {
        error_log("Missing 'type' or 'parameter' in the message");
        return;
    }
    echo($parameter);
    switch ($type) {
        case 'movie_details':
            $url = "https://api.themoviedb.org/3/movie/121?language=en-US";
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
    $client = new \GuzzleHttp\Client();
    $response = $client->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTE3NDI3NS44MTA5NTUsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.3wTZmroEtn8GSIHD92r7p-3G4iAjPMHZa3aojxycDIM',
          'accept' => 'application/json',
        ],
      ]);

      $responseBody = json_decode($response->getBody(), true);

      return json_encode([
        'type' => 'success',
        'data' => $responseBody,
    ]);
    
      //send back to RabbitMQ
}
