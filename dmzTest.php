<?php
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

// Logging that the test started
error_log("Starting DMZ Test from a single file...");

// Set up your RabbitMQ connection details
$rabbitHost = '172.29.4.30';
$rabbitPort = 5672;
$rabbitUser = 'admin';
$rabbitPass = 'admin';
$rabbitVhost = 'IT490_Host';

// Define the cluster and request details
$cluster = 'DEV'; // or 'QA', 'PROD' as appropriate
$type = 'movie_details';
$parameter = 550; // Fight Club
$queue = 'frontendForDMZ'; // The queue to send the request to

// 1. Connect to RabbitMQ for publishing the request
$connection = new AMQPStreamConnection($rabbitHost, $rabbitPort, $rabbitUser, $rabbitPass, $rabbitVhost);
$channel = $connection->channel();

// Declare the exchange and queues, and bind them
$channel->exchange_declare('directExchange', 'direct', false, true, false);
$channel->queue_declare('frontendForDMZ', false, true, false, false);
$channel->queue_declare('dmzForFrontend', false, true, false, false);
$channel->queue_bind('dmzForFrontend', 'directExchange', 'dmzForFrontend');

// Prepare the request message
$data = json_encode([
    'type' => $type,
    'parameter' => $parameter
]);
$msg = new AMQPMessage($data, ['delivery_mode' => 2]);

// Publish the request to the DMZ queue via the directExchange
$channel->basic_publish($msg, 'directExchange', $queue);
error_log("Sent request for movie ID: $parameter to DMZ");


// 2. Set up a new connection to consume the response from DMZ
error_log("Setting up consumer for response...");

$finalResponse = null;

// Define the callback for when a message arrives
$callback = function($msg) use (&$finalResponse) {
    error_log("Received raw message: ".$msg->body);
    $response = json_decode($msg->body, true);
    if ($response === null) {
        error_log("JSON decode failed. Message body: ".$msg->body);
        return;
    }

    error_log("Decoded response: ".print_r($response, true));

    if (isset($response['type']) && $response['type'] === 'success') {
        $finalResponse = $response;
    } else {
        error_log("Error: Failed to retrieve data or invalid response format received from DMZ.");
    }
};

// Start consuming from dmzForFrontend
$channelConsume->basic_consume('dmzForFrontend', '', false, true, false, false, $callback);

// Wait up to 30 seconds for a response
$start = time();
while ($channelConsume->is_consuming()) {
    $channelConsume->wait(null, false, 5); // wait with a small timeout so we can check elapsed time
    if ($finalResponse !== null) {
        break;
    }
    if ((time() - $start) > 30) {
        error_log("Timed out waiting for DMZ response");
        break;
    }
}

// Close the consuming connection
$channelConsume->close();
$connectionConsume->close();

// Output the results
if ($finalResponse) {
    echo "<h1>DMZ Test Results</h1>";
    echo "<pre>";
    print_r($finalResponse);
    echo "</pre>";
} else {
    echo "<h1>DMZ Test Failed</h1>";
    echo "<p>No response received from DMZ.</p>";
}

error_log("Finished DMZ Test.");
