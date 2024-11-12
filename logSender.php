<?php
    require_once 'webserver/rabbitmq_connection.php';

    //Adjust this path variable to what where your guys error logs are
    $logFile = '/var/log/apache2/error.log'; 
    $machineName = 'DMZ';

    // Amount of seconds between checking for logs 
    $logInterval = 5; 

    $logs = [];
    
    $file = fopen($logFile, 'r');
    fseek($file, 0, SEEK_END);

    while(true){
        $line = fgets($file);
        if ($line !== false ){
            
            $timestamp = date("F j, Y, g:i a");
            $logEntry = trim($line);
            echo "Error recieved from Apache Server";

            if (isset($logs[$logEntry])) {
                // Counts the amount of times the error showed up
                $logs[$logEntry]['count']++;
            } else {
                $logs[$logEntry] = [
                    'message' => $logEntry,
                    'count' => 1,
                    'timestamp' => $timestamp
                ];
            };

        $logJSON = json_encode([
            'machine' => $machineName,
            'timestamp' => $logs[$logEntry]['timestamp'],
            'message' => $logs[$logEntry]['message'],
            'count' => $logs[$logEntry]['count']
        ]);

        sendLog($logJSON);
        echo "Error sent from Apache Server to Distrubted Logger";

    } 

    sleep($logInterval);
};

?>