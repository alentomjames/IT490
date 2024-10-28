<?php

// This is the first version of the APICall, now deprecated
session_start();

$loggedIn = isset($_SESSION['userID']);

require_once('vendor/autoload.php');


$url = 'RequiredLink';
$data = [
    'collection'  => 'RequiredAPI'
];

$client = new \GuzzleHttp\Client();
// removed curl, since we're using guzzle
 
#Function references geeksforgeeks (https://www.geeksforgeeks.org/how-to-add-api-function-to-a-simple-php-page/)
/*
function APIcall($method, $url, $data) {
    $curl = curl_init();
     
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data)
                curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
            break;
    }
    
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'APIKEY: dbfba89a323172dffa4299657a371637',
        'Content-Type: application/json',
    ));
    
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    $result = curl_exec($curl);
     
    if(!$result) {
        echo("Connection failure!");
    }
    curl_close($curl);
    return $result;
}
*/

function fetchDetails ($type, $parameter) {
    $url = '';

    switch ($type) {
        case 'movie_details':
            $url = "https://api.themoviedb.org/3/movie/{movie_id}";
            break;
            //send back to RabbitMQ
            break;
        case 'person_details':
            $url = "https://api.themoviedb.org/3/person/{person_id}";
            $response = $client->request('GET', 'https://api.themoviedb.org/3/person/person_id?language=en-US', [
                'headers' => [
                  'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTE3NDI3NS44MTA5NTUsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.3wTZmroEtn8GSIHD92r7p-3G4iAjPMHZa3aojxycDIM',
                  'accept' => 'application/json',
                ],
            ]);
            return $response; 
        case 'review_details':
            $url = "https://api.themoviedb.org/3/review/{review_id}";
            $response = $client->request('GET', 'https://api.themoviedb.org/3/review/review_id', [
                'headers' => [
                  'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTE3NDI3NS44MTA5NTUsInN1YiI6IjY3MTExYThiY2Y4ZGU4NzdiNDlmY2JlMyIsInNjb3BlcyI6WyJhcGlfcmVhZCJdLCJ2ZXJzaW9uIjoxfQ.3wTZmroEtn8GSIHD92r7p-3G4iAjPMHZa3aojxycDIM',
                  'accept' => 'application/json',
                ],
              ]);
        default:
            throw new Exception('Invalid type provided');
    }

    // Call the API to get a response
    return $response;
    return json_decode($response, true);
}


try {
    $movieData = fetchDetails('movie_details', '123');
    print_r($movieData);

    $personData = fetchDetails('person_details', '456');
    print_r($personData);

    $reviewData = fetchDetails('review_details', '789');
    print_r($reviewData);
}
catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}

//From Alen's moviePage.php
/*
    $response = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $movie_id . '?language=en-US', [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
          'accept' => 'application/json',
        ],
    ]);

    // Decode the JSON response
    $movie = json_decode($response->getBody(), true);

    // Fetch recommendations using movieID
    $recommendationResponse = $client->request('GET', 'https://api.themoviedb.org/3/movie/' . $movie_id . '/recommendations?language=en-US&page=1', [
        'headers' => [
          'Authorization' => 'Bearer eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJkYmZiYTg5YTMyMzE3MmRmZmE0Mjk5NjU3YTM3MTYzNyIsIm5iZiI6MTcyOTI4ODcyNS4xNTE3MSwic3ViIjoiNjcxMTFhOGJjZjhkZTg3N2I0OWZjYmUzIiwic2NvcGVzIjpbImFwaV9yZWFkIl0sInZlcnNpb24iOjF9.vo9zln6wlz5XoDloD8bubYw3ZRgp-xlBL873eZ68fgQ',
          'accept' => 'application/json',
        ],
    ]);
    $recommendations = json_decode($recommendationResponse->getBody(), true)['results'];

*/

/*
// look at Alen's send.php file
function HelperQueueResponse ($type, $messageBody){
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
    
    $channel->close();
    $connection->close();
}
*/
// From Petar's testRabbitMQServer.php
/*

function sendMessageToDMZQueue($messageBody) {
    global $host, $port, $user, $passwordRMQ, $vhost;
    $connection = new AMQPStreamConnection($host, $port, $user, $passwordRMQ, $vhost);
    $channel = $connection->channel();
    $exchange = 'directExchange';
    $queue = 'dmz';

    $message = new AMQPMessage($messageBody);
    $channel->basic_publish($message, $exchange, $queue);
    echo "Sent message to DMZ queue: $messageBody\n";

    $channel->close();
    $connection->close();
}
    */

?>
?>