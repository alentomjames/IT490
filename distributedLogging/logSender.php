<?php
    require_once 'rabbitmq_connection.php';

    //Adjust this path variable to what where your guys error logs are
    $logFile = '/var/log/apache2/error.log'; 
    $machineName = 'Webserver';

    $logs = [];
    
    $file = fopen($logFile, 'r');
    fseek($file, 0, SEEK_END);

    echo "Starting log monitoring for $file\n";

    while(true) {
        $line = fgets($file);
        echo 'LINE VARIABLE: $line\n';

        if ($line !== false ){
            echo "Read line from Apache log: $line\n";  
            $timestamp = date("F j, Y, g:i a");
            $logEntry = trim($line);
            echo "Error recieved from Apache Server\n";

            if (isset($logs[$logEntry])) {
                // Counts the amount of times the error showed up
                $logs[$logEntry]['count']++;
            } else {
                $logs[$logEntry] = [
                    'message' => $logEntry,
                    'count' => 1,
                    'timestamp' => $timestamp
                ];
            } 
        

        $logJSON = json_encode([
            'machine' => $machineName,
            'timestamp' => $logs[$logEntry]['timestamp'],
            'message' => $logs[$logEntry]['message'],
            'count' => $logs[$logEntry]['count']
        ]);
        echo "Error sent from Apache Server to Distrubted Logger: $logJSON\n";

        sendLog($logJSON);
        sleep(1);
        clearstatcache();
        fseek($file, $position ?? 0);
        }
    } 

?>