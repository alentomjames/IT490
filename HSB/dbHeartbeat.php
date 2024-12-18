<?php
#!/usr/bin/php
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

function isDatabaseUp() {
    $response = exec('php dbCheck.php');
    return trim($response) === "OK";
}

function isRabbitMQUp() {
    try {
        list($connection, $channel) = getProdRabbit();
        $channel->close();
        $connection->close();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

list($connection, $channel) = getDeployRabbit();
$channel->queue_declare('prodForHSB', false, true, false, false);

while (true) {
    $dbStatus = isDatabaseUp();
    $rabbitStatus = isRabbitMQUp();

    if ($dbStatus && $rabbitStatus) {
        echo "PROD Server is still active.\n";
        $data = json_encode([
            'type' => 'hotstandby',
            'status' => 'up'
        ]);
        $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'directExchange', 'prodForHSB');
    } else {
        echo "PROD Server is down.\n";
        $data = json_encode([
            'type' => 'hotstandby',
            'status' => 'down'
        ]);
        $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'directExchange', 'prodForHSB');

        // Stop the other service if one is down
        if (!$dbStatus) {
            echo "Database is down. Stopping RabbitMQ.\n";
            exec('sudo systemctl stop rabbitmq-server');
        } else {
            echo "RabbitMQ is down. Stopping Database.\n";
            exec('sudo systemctl stop mysql');
        }

        closeRabbit($connection, $channel);
        break;
    }

    sleep(5);
}

?>