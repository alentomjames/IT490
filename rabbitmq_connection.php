<?php

require_once 'vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function getRabbit()
{
        // Connect to RABBITMQ HERE and add better error handling
        $retries = 5;
        while ($retries > 0) {
            error_log("Attempting to connect to RabbitMQ: $retries attempts remaining");
            try {
                $connection = new AMQPStreamConnection('172.29.4.30', 5672, 'admin', 'admin', 'IT490_Host');
                error_log("Connected to RabbitMQ");
                break; // Exit loop if connection is successful
            } catch (Exception $e) {
                $retries--;
                if ($retries == 0) {
                    throw new Exception("Failed to connect to RabbitMQ after multiple attempts: " . $e->getMessage());
                }
                sleep(2); // Wait for 2 seconds before retrying
            }
        }
        $channel = $connection->channel();
        error_log("Created RabbitMQ channel: $channel");
        return [$connection, $channel];
}
function getDeployRabbit()
{
    // Connect to RABBITMQ HERE and add better error handling
    $retries = 5;
    while ($retries > 0) {
        try {
            $connection = new AMQPStreamConnection('172.29.82.171', 5672, 'dm692', 'password', 'it490');
            break; // Exit loop if connection is successful
        } catch (Exception $e) {
            $retries--;
            if ($retries == 0) {
                throw new Exception("Failed to connect to RabbitMQ after multiple attempts: " . $e->getMessage());
            }
            sleep(2); // Wait for 2 seconds before retrying
        }
    }
    $channel = $connection->channel();

    return [$connection, $channel];
}
function getQARabbit()
{
    // Connect to RABBITMQ HERE and add better error handling
    $connection = new AMQPStreamConnection('172.29.87.41', 5672, 'admin', 'admin', 'IT490_Host');
    $channel = $connection->channel();

    return [$connection, $channel];
}
function getProdRabbit()
{
    // Connect to RABBITMQ HERE and add better error handling
    $connection = new AMQPStreamConnection('172.29.123.139', 5672, 'admin', 'admin', 'IT490_Host');
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

function sendRequest($type, $parameter, $queue, $cluster)
{
    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }

    // Declaring the channel its being sent on
    $channel->queue_declare('frontendForDMZ', false, true, false, false);
    $channel->queue_declare('frontendForDB', false, true, false, false);
    $data = json_encode([
        'type' => $type,
        'parameter' => $parameter
    ]);

    $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
    $channel->basic_publish($msg, 'directExchange', $queue);
    closeRabbit($connection, $channel);
}

function recieveDMZ($cluster)
{
    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
        error_log("Getting Rabbit for DEV DMZ");
    }
    $data = null;
    // Declare the exchange
    $channel->exchange_declare('directExchange', 'direct', false, true, false);
    error_log("Declared DMZ exchange");
    // Declare the response channel
    $channel->queue_declare('dmzForFrontend', false, true, false, false);
    error_log("Declared DMZ response channel");
    $channel->queue_bind('dmzForFrontend', 'directExchange', 'dmzForFrontend');
    error_log("Bound DMZ response channel");
    // Function waiting for the response from RabbitMQ
    $callback = function ($msg) use (&$data) {
        error_log("Received raw message: " . $msg->body);
        $response = json_decode($msg->body, true);
        if ($response === null) {
            error_log("JSON decode failed");
        } else {
            error_log("Decoded response: " . print_r($response, true));
        }
        // Check if the response type is 'success' and data is present
        error_log("RESPONSE FROM RMQ_CONNECT: $response");
        error_log("MSG FROM RMQ_CONNECT: $msg");
        error_log("DATA FROM RMQ_CONNECT: $data");
        if (isset($response['type']) && $response['type'] === 'success') {
            $data = $response['data'];
            echo "Data received from DMZ: {$data}";
            error_log("Successfully parsed DMZ response data: {$data}");
            error_log("Successfully parsed DMZ response data");
        } else {
            echo 'Error: Failed to retrieve data or invalid response format received from DMZ.';
        }
    };

    $channel->basic_consume('dmzForFrontend', '', false, true, false, false, $callback);
    error_log("Consuming DMZ response channel and called callback");
    // Wait for the response
    while ($channel->is_consuming()) {
        $channel->wait(null, false, 30); // timeout
        if ($data !== null) {
            break;
        } else {
            error_log("Data is null");
        }
    }

    // Close the channel and connection
    closeRabbit($connection, $channel);
    error_log("Closed Rabbit connection");
    return $data;
}
function recieveDB($cluster)
{

    if (ob_get_length()) {
        ob_clean();
    }
    if ($cluster == 'QA') {
        list($connection, $channel) = getQARabbit();
    } else if ($cluster == 'PROD') {
        list($connection, $channel) = getProdRabbit();
    } else {
        list($connection, $channel) = getRabbit();
    }
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
    list($connection, $channel) = getDeployRabbit();
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
    list($connection, $channel) = getDeployRabbit();
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