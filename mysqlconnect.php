#!/usr/bin/php
<?php

$getenv = parse_ini_file('db_config.env');

$host = $getenv['DB_HOST'];
$username = $getenv['DB_USER'];
$password = $getenv['DB_PASSWORD'];
$database = $getenv['DB_NAME'];

$mydb = new mysqli($host, $username, $password, $database);

if ($mydb->errno != 0)
{
	echo "failed to connect to database: ". $mydb->error . PHP_EOL;
	exit(0);
}

echo "successfully connected to database".PHP_EOL;

$query = "select * from users;";

$response = $mydb->query($query);
if ($mydb->errno != 0)
{
	echo "failed to execute query:".PHP_EOL;
	echo __FILE__.':'.__LINE__.":error: ".$mydb->error.PHP_EOL;
	exit(0);
}


?>
