#!/usr/bin/php
<?php
    $url = 'http://172.29.244.200/HSB/okay.php';
    
    while (true) {
        // Send a curl request to check the response from url 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response == "OK") {
            echo "PROD Server is still active.\n";
        } else {
            echo "PROD Server is down.\n";
            exec('php /git/HSB/hotstandby.php');
            break;
        }

        sleep(5);
    }

?> 