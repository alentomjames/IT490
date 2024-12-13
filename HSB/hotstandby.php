#!/usr/bin/php
<?php
$ip = '172.29.244.200/16';
$networkInterface = 'ztosimf46d';
$switchIP = "sudo ip addr add $ip dev $networkInterface";
$restartApache = "sudo systemctl restart apache2";

echo "Starting IP switch\n";


// Switch IP to the PROD Server's IP
exec($switchIP, $output, $return_var);
if ($return_var == 0) {
    echo "Switched to Hot Standby Server.\n";
    // Verify IP was added
    exec("ip addr show $networkInterface", $ipOutput, $ipReturnVar);
    if (strpos(implode("\n", $ipOutput), "172.29.244.200") !== false) {
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

// Restart Apache2
echo "Restarting Apache\n";
exec($restartApache, $output, $return_var);
if ($return_var == 0) {
    echo "Apache restarted successfully.\n";
    // Test if we can curl the okay.php page 
    $url = 'http://172.29.244.200/HSB/okay.php';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response == "OK\n") {
        echo "Verified: Successfully curled okay.php and got the response: $response\n";
    } else {
        echo "Error: Could not curl okay.php successfully\n";
        exit(1);
    }
    } else {
        echo "Failed to restart Apache.\n";
        exit(1);
};


?>