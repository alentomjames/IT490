#!/usr/bin/php
<?php
require_once '../rabbitmq_connection.php';
require_once '../vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$ip = '172.29.244.201/16';
$networkInterface = 'ztosimf46d';
$switchIP = "sudo ip addr add $ip dev $networkInterface";
$restartMySQL = "sudo systemctl restart mysql.service";
$promoteDB = "sudo mysql -u root -e \"STOP SLAVE; SET GLOBAL read_only = OFF;\"";

echo "Starting Hot Standby Listener\n";

list($connection, $channel) = getDeployRabbit();

$channel->queue_declare('prodForBeHSB', false, true, false, false);

$callback = function ($msg) use ($switchIP, $restartMySQL, $networkInterface, $channel, $promoteDB) {
    $data = json_decode($msg->body, true);
    echo "Received message: " . $data['status'] . "\n";
    if ($data['status'] === 'down') {
        echo "PROD Server is down. Initiating failover...\n";

        // Switch IP to the PROD Server's IP
        exec($switchIP, $output, $return_var);
        if ($return_var == 0) {
            echo "Switched to Hot Standby Server.\n";
            // Verify IP was added
            exec("ip addr show $networkInterface", $ipOutput, $ipReturnVar);
            if (strpos(implode("\n", $ipOutput), "172.29.244.201") !== false) {
                $ipLine = trim($ipOutput[4]);
                echo "Verified: VIP $ipLine was successfully added\n";
            } else {
                echo "Error: VIP was not added successfully\n";
                exit(1);
            }
        } else {
            echo "Failed to switch IP.\n";
            exit(1);
        }

        // Restart MySQL
        echo "Restarting MySQL\n";
        exec($restartMySQL, $output, $return_var);
        if ($return_var == 0) {
            echo "MySQL restarted successfully.\n";
        } else {
            echo "Failed to restart MySQL.\n";
            exit(1);
        }
        // Promote HSB to Master
        echo "Promoting HSB to Master Database\n";
        exec($promoteDB, $output, $return_var);
        if ($return_var == 0) {
            echo "HSB promoted to Master successfully.\n";
        } else {
            echo "Failed to promote HSB to Master.\n";
            exit(1);
        }

        echo "Restaring RabbitMQ\n";
        exec('sudo systemctl restart rabbitmq-server');
        if ($return_var == 0) {
            echo "RabbitMQ restarted successfully.\n";
        } else {
            echo "Failed to restart RabbitMQ.\n";
            exit(1);
        }


        // Stop consuming messages
        $channel->basic_cancel($msg->delivery_info['consumer_tag']);
    } else {
        echo "PROD Server is up. No action required.\n";
    }
};

$channel->basic_consume('prodForBeHSB', '', false, true, false, false, $callback);

while ($channel->is_consuming()) {
    $channel->wait();
}

closeRabbit($connection, $channel);
?>