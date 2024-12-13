#!/usr/bin/php
<?php
    $url = 'http://172.29.244.200/HSB/okay.php';
    
    while (true) {
        // Send a curl request to check the response from url 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response == "OK\n") {
            echo "PROD Server is still active.\n";
        } else {
            echo "PROD Server is down.\n";
            exec('php ~/git/IT490/HSB/hotstandby.php', $output, $return_var);
            echo "Output: " . implode("\n", $output) . "\n";
            echo "Return Value: $return_var\n";

            break;
        }

        sleep(5);
    }

?> 