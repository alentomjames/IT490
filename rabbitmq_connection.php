<?php

require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function getRabbit()
{
    // Connect to RABBITMQ HERE and add better error handling
    $connection = new AMQPStreamConnection('172.29.4.30', 5672, 'admin', 'admin', 'IT490_Host');
    $channel = $connection->channel();

    return [$connection, $channel];
}

function closeRabbit($connection, $channel)
{
    if ($channel) {
        $channel->close();
    }
    if ($connection) {
        $connection->close();
    }
}

function sendRequest($type, $parameter, $queue)
{
    list($connection, $channel) = getRabbit();
    // Declaring the channel its being sent on
    $channel->queue_declare('frontendForDMZ', false, true, false, false);
    $channel->queue_declare('frontendForDB', false, true, false, false);
    $data = json_encode([
        'type'     => $type,
        'parameter' => $parameter
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', $queue);
    closeRabbit($connection, $channel);
}

function recieveDMZ()
{
    list($connection, $channel) = getRabbit();
    $data = null;
    // Declare the response channel
    $channel->queue_declare('dmzForFrontend', false, true, false, false);

    // Function waiting for the response from RabbitMQ
    $callback = function ($msg) use (&$data) {
        $response = json_decode($msg->body, true);
        // Check if the response type is 'success' and data is present
        if (isset($response['type']) && $response['type'] === 'success') {
            $data = $response['data'];
            error_log("Successfully parsed DMZ response data");
        } else {
            echo 'Error: Failed to retrieve data or invalid response format received from DMZ.';
        }
    };

    $channel->basic_consume('dmzForFrontend', '', false, true, false, false, $callback);

    // Wait for the response
    while ($channel->is_consuming()) {
        $channel->wait();
        if ($data !== null) {
            break;
        }
    }

    // Close the channel and connection
    closeRabbit($connection, $channel);

    return $data;
}
function recieveDB()
{

    if (ob_get_length()) {
        ob_clean();
    }
    list($connection, $channel) = getRabbit();
    $data = null;
    // Declare the response channel
    $channel->queue_declare('databaseForFrontend', false, true, false, false);

    // Function waiting for the response from RabbitMQ
    $callback = function ($msg) use (&$data) {
        $response = json_decode($msg->body, true);
        // Check if the response type is 'success' and data is present
        if (isset($response['type']) && $response['type'] === 'success') {
            $data = $response['data'];
            error_log("Successfully parsed DB response data");
        } else {
            echo 'Error: Failed to retrieve data or invalid response format received from DMZ.';
        }
    };

    $channel->basic_consume('databaseForFrontend', '', false, true, false, false, $callback);

    // Wait for the response
    while ($channel->is_consuming()) {
        $channel->wait();
        if ($data !== null) {
            break;
        }
    }
    // Close the channel and connection
    closeRabbit($connection, $channel);

    return $data;
}

function sendLog($logMessage)
{
    list($connection, $channel) = getRabbit();
    try {
        $channel->exchange_declare('fanoutExchange', 'fanout', false, true, false);
        $msg = new AMQPMessage($logMessage, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'fanoutExchange');
        echo "Log message sent to fanoutExchange\n";
    } catch (Exception $e) {
        echo "Error publishing message to RabbitMQ: " . $e->getMessage() . "\n";
    } finally {
        closeRabbit($connection, $channel);
    }
}

function recieveLogs()
{
    list($connection, $channel) = getRabbit();
    $channel->queue_declare('toBeDev', false, true, false, false);

    echo "Waiting for logs. To exit press CTRL+C\n";

    $logPath = '/var/log/distributedLogs/distributedLogs.txt';

    $callback = function ($msg) use ($logPath) {
        file_put_contents($logPath, $msg->body . PHP_EOL, FILE_APPEND);
    };

    $channel->basic_consume('toBeDev', '', false, true, false, false, $callback);

    // Wait for the response
    while ($channel->is_consuming()) {
        $channel->wait();
        echo "Error recieved from Distrubted Logger\n";

    }
    // Close the channel and connection
    closeRabbit($connection, $channel);
}