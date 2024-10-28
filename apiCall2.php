<?php
session_start();

require_once('vendor/autoload.php');

$client = new \GuzzleHttp\Client();

function fetchDetails ($type, $parameter) {
    $url = '';

    switch ($type) {
        case 'movie_details':
            $url = "https://api.themoviedb.org/3/movie/{$parameter['movie_id']}?language=en-US";
            break;
        default:
            throw new Exception('Invalid type provided');
    }

    // Call the API to get a response 
    $response = $client->request('GET', $url, [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTE3NDI3NS44MTA5NTUsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.3wTZmroEtn8GSIHD92r7p-3G4iAjPMHZa3aojxycDIM',
          'accept' => 'application/json',
        ],
      ]);

      $responseBody = json_decode($response->getBody(), true);
      //send back to RabbitMQ
      sendMessageToDMZQueue($responseBody, $type);
  
      return $responseBody;
}


?> 