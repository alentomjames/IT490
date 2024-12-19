#/usr/bin/php
<?php
$ip = '172.29.244.200';
$url = 'https://172.29.244.200/HSB/okay.php';

// Change this based on what machine you're on
$current = "PROD";
$standby = "HSB";

// Curls the okay.php page
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);

// Ignore SSL verification
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

$response = curl_exec($ch);
$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$response = trim($response);
echo "Response: $response\n"; 

if ($response == "$standby ACTIVE") {
    // This machine becomes the standby machine and starts the heartbeat.php function
    echo "$standby is ACTIVE, activating heartbeat on $current\n";
    exec('php /home/alen/git/IT490/HSB/heartbeat.php', $output, $return_var);
    echo "$current is now the STANDBY machine\n";
} else if (empty($response) || $responseCode != 200) {
    echo "No response from $standby\n, $current attempting to become the ACTIVE machine\n";
    exec ('php /home/alen/git/IT490/HSB/hotstandby.php', $output, $return_var);
    echo "$current is now the ACTIVE machine\n";
    // Update the okay file 
    file_put_contents('/var/www/it490/HSB/okay.php', "<?php echo \"$current ACTIVE\\n\"; ?>");
} 
?>