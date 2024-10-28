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


function HelperQueueResponse ($messageBody, $type) {
  // DMZ Queue server details
  $host = 172.29.2.108;
  $port = 5672;
  $user = admin;
  $vhost = 'IT490_HOST';
  // connection creation
  $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
  $channel = $connection->channel();

  // define direct exchange
  $exchange = 'directExchange';

  // create a message
  $message = new AMQPMessage($messageBody);

  // publish message to specific queue using routing key
  $channel->basic_publish($message, $exchange, $queue);

  echo "Message sent to queue '$queue': $messageBody\n";
  
  // communicates through the RabbitMQ to contact the front end, sends the type and parameter

  $channel->queue_declare('frontendQueue', false, true, false, false);
  data = json_encode({
    'type' = $type
    'parameter' = $parameter
  });
  
  $channel->close();
  $connection->close();

  
  sendMessage('Hello, Frontend Queue! Here are the ', $type); 
}


?> 