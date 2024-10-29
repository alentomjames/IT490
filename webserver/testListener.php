<?php 
require_once 'webserver/rabbitmq_connection.php'; // RabbitMQ connection
require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

echo "Starting Listener...\n";

try {
    // Establish connection to RabbitMQ
    list($connection, $channel) = getRabbit();

    // Declare the queue
    $channel->queue_declare('dmzQueue', false, true, false, false);
    
    // Define the callback to process messages
    $callback = function($msg) {
        $data = json_decode($msg->body, true);
        
        // Print message data to console or log it
        echo "Received message: " . print_r($data, true) . "\n";

        // Check if data format is as expected
        if (isset($data['type']) && isset($data['data'])) {
            if ($data['type'] === 'success') {
                echo "Data received: " . print_r($data['data'], true) . "\n";
            } else {
                echo "Received error message: " . print_r($data, true) . "\n";
            }
        } else {
            echo "Invalid message format.\n";
        }
    };

    // Set up the consumer on `dmzQueue`
    $channel->basic_consume('dmzQueue', '', false, true, false, false, $callback);

    // Loop to keep the listener active
    while ($channel->is_consuming()) {
        $channel->wait();
    }

    // Close the channel and connection when done
    closeRabbit($connection, $channel);

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
