#!/usr/bin/php
<?php
    $url = 'https://172.29.244.200/HSB/okay.php';
    
    while (true) {
        // Send a curl request to check the response from url 
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // Ignore SSL verification
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $response = curl_exec($ch);
        curl_close($ch);

        $response = trim($response); 

        if (strpos($response, "ACTIVE") !== false) {
            echo "ACTIVE Server is still active.\n";
        } else {
            echo "Active server is down or no response.\n";
            exec('php /home/alen/git/IT490/HSB/hotstandby.php', $output, $return_var);
            echo "Output: " . implode("\n", $output) . "\n";
            echo "Return Value: $return_var\n";

            break;
        }

        sleep(5);
    }

?> 