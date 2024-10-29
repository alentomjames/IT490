<?php
session_start();

require_once '/webserver/vendor/autoload.php';
require_once '/webserver/rabbitmq_connection.php';  
$client = new \GuzzleHttp\Client();

function fetchDetails ($type, $parameter, $messageBody) {
    $url = '';

    switch ($type) {
        case 'movie_details':
            $url = "https://api.themoviedb.org/3/movie/{$parameter['movie_id']}?language=en-US";
            helperQueueResponse ($messageBody, 'movie_details');
            break;
        case 'person_details':
            $url = "https://api.themoviedb.org/3/person/{$parameter['person_id']}?language=en-US";
            helperQueueResponse ($messageBody, 'person_details');
            break;
        case 'review_details':
            $url = "https://api.themoviedb.org/3/review/{$parameter['review_id']}?language=en-US";
            helperQueueResponse ($messageBody, 'review_details');
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


function helperQueueResponse ($messageBody, $type) {
  list($connection, $channel) = getRabbit();

  // define direct exchange
  $exchange = 'directExchange';

  // create a message
  $message = new AMQPMessage($messageBody);

  // publish message to specific queue using routing keys
  $channel->basic_publish($message, $exchange, 'dmzQueue');

  echo "Message sent to queue 'dmzQueue': $messageBody\n";
  
  // communicates through the RabbitMQ to contact the front end, sends the type and parameter

  $channel->queue_declare('frontendQueue', false, true, false, false);
  $data = json_encode([
    'type' => $type,
    'parameter' => $parameter
  ]);
  
  // consumes message from queue
  $channel->basic_consume('frontendQueue', false, true, false, false);
  
  // wait for messages
  while ($channel->is_consuming()) {
    $channel->wait();
  }

  // closes the rabbitmq connection
  closeRabbit($connection, $channel);
  
  sendMessage('Hello, Frontend Queue! Here are the ', $type); 
}


?> 