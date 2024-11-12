<?php
    require_once 'rabbitmq_connection.php';

    //Adjust this path variable to what where your guys error logs are
    $logFile = '/var/log/apache2/error.log'; 
    $machineName = 'Webserver';

    $sentLogs = [];
    
    $file = fopen($logFile, 'r');
    fseek($file, 0, SEEK_END);

    echo "Starting log monitoring for $file\n";

    while(true) {
        $line = fgets($file);
        if ($line !== false) {
        // Clean the line and create a unique hash for the log entry
        $logEntry = trim($line);
        $logHash = md5($logEntry);

        // Check if this log entry has already been sent
        if (isset($sentLogs[$logHash])) {
            echo "Duplicate log detected, skipping: $logEntry\n";
        } else {

            echo "Read line from Apache log: $line\n";  
            $timestamp = date("F j, Y, g:i a");
            $logEntry = trim($line);
            echo "Error recieved from Apache Server\n";
            $logs[$logEntry] = [
                'message' => $logEntry,
                'count' => 1,
                'timestamp' => $timestamp
            ];
            } 
            $sentLogs[$logHash] = true;
            // Track the current position after reading the line
            $position = ftell($file);

        } else {
            sleep(1);
            clearstatcache();
            fseek($file, $position ?? 0);
        }

        

        $logJSON = json_encode([
            'machine' => $machineName,
            'timestamp' => $logs[$logEntry]['timestamp'],
            'message' => $logs[$logEntry]['message'],
            'count' => $logs[$logEntry]['count']
        ]);
        echo "Error sent from Apache Server to Distrubted Logger: $logJSON\n";

        sendLog($logJSON);
    } 


?>