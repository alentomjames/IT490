#!/usr/bin/php
<?php
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
list($connection, $channel) = getProdRabbit();

$channel->queue_declare( 'prodForHSB', false, true, false, false);

while (true) {
    $response = exec('php dbCheck.php');
    echo "Response: $response\n";

    if ($response == "OK\n") {
        echo "PROD Server is still active.\n";
        $data = json_encode([
            'type' => 'hotstandby',
            'status' => 'up'
        ]);
    } else {
        echo "PROD Server is down.\n";
        $data = json_encode([
            'type' => 'hotstandby',
            'status' => 'down'
        ]);

        $msg = new AMQPMessage($data, ['delivery_mode' => 2]);
        $channel->basic_publish($msg, 'directExchange', 'prodForHSB');
        closeRabbit($connection, $channel);

        break;
    }

    sleep(5);
}

?>