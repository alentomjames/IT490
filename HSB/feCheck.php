#/usr/bin/php
<?php
$ip = '172.29.244.200';
$url = 'https://172.29.244.200/HSB/okay.php';

$current = "PROD";
// $current = "HSB";

// Curls the okay.php page
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
curl_close($ch);

$response = trim($response);

if ($response == "HSB ACTIVE") {
    // This machine becomes the standby machine and starts the heartbeat.php function
    exec('php /home/alen/git/IT490/HSB/heartbeat.php', $output, $return_var);
    echo "$current is now the STANDBY machine\n";
} else {
    exec ('php /home/alen/git/IT490/HSB/hotstandby.php', $output, $return_var);
    echo "$current is now the ACTIVE machine\n";
    // Update the okay file 
    file_put_contents('/var/www/it490/HSB/okay.php', "<?php echo \"$current ACTIVE\\n\"; ?>");
}
?>