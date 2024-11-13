@ -1,51 +1,47 @@
<?php
    require_once 'rabbitmq_connection.php';

    //Adjust this path variable to what where your guys error logs are
    $logFile = '/var/log/rabbitmq/rabbit@IT490.log';
    $machineName = 'Database';

    $file = fopen($logFile, 'r');
    fseek($file, 0, SEEK_END);

    echo "Starting log monitoring for $logFile\n";

    $sentLogs = [];
    // Keeping a record of the past 100 logs that were being sent
    $logRetention = 100;

    while(true) {
        $line = fgets($file);
        if ($line !== false ){

            echo "Read line from RabbitMQ log: $line\n";
            $logEntry = trim($line);
            // Creating a unique hash for each log entry
            $logHash = md5($logEntry);

            echo "Error recieved from RabbitMQ Server\n";

            if (isset($sentLogs[$logHash])) {
                echo "Error message already sent to Log Distributer";
            } else {
                $timestamp = date("F j, Y, g:i a");
                $logJSON = json_encode([
                    'machine' => $machineName,
                    'timestamp' => $timestamp,
                    'message' => $logEntry,
                    'count' => 1
                ]);
                echo "Message sent from RabbitMQ to Distrubted Logger: $logJSON\n";
                sendLog($logJSON);

                $sentLogs[$logHash] = true;
                if (count($sentLogs) > $logRetention) {
                    array_shift($sentLogs); // Remove the oldest entry
                }
            }
            $position = ftell($file);
    } else {
        sleep(1);
        clearstatcache(); // Clear cached information about the file
        fseek($file, $position ?? 0);
    }
}

?>