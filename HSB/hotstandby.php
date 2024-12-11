#!/usr/bin/php
<?php
$ip = '172.29.244.200/24';
$networkInterface = 'ztosimf46d';
$switchIP = "sudo ip addr add $ip dev $networkInterface";
$restartApache = "sudo systemctl restart apache2";

// Switch IP to the PROD Server's IP
exec($switchIP, $output, $return_var);
if ($return_var == 0) {
    echo "Switched to Hot Standby Server.\n";
    } else {
        echo "Failed to restart Apache.\n";
        exit(1);
};

// Restart Apache2
exec($restartApache, $output, $return_var);
if ($return_var == 0) {
    echo "Apache restarted successfully.\n";
    } else {
        echo "Failed to restart Apache.\n";
        exit(1);
};





?>