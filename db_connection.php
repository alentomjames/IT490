<?php

$getenv = parse_ini_file('db_config.env');

function getDbConnection()
{
	global $getenv;
	$host = $getenv['DB_HOST'];
	$username = $getenv['DB_USER'];
	$password = $getenv['DB_PASSWORD'];
	$database = $getenv['DB_NAME'];

	$mydb = new mysqli($host, $username, $password, $database);

	// if ($mydb->connect_errno) {
	// 	//echo "Failed to connect to database: " . $mydb->connect_error . PHP_EOL;
	// 	exit(0);
	// } else {
	// 	//echo "Successfully connected to database" . PHP_EOL;
	// }

	return $mydb;
}

// $query = "select * from users;";

// $response = $mydb->query($query);
// if ($mydb->errno != 0)
// {
// 	echo "failed to execute query:".PHP_EOL;
// 	echo __FILE__.':'.__LINE__.":error: ".$mydb->error.PHP_EOL;
// 	exit(0);
// }
