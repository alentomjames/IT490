<?php

require_once './webserver/vendor/autoload.php';
require_once './db_connection.php'; // file has db connection
require_once './webserver/rabbitmq_connection.php'; // how I connect to RabbitMQ

$dbConnection = getDbConnection();

// deploy_update --> retrieve, store, manage deployment packages. 
//Keeps track of version numbers and stores in current and also archives previous version.

// rollback_update --> grabs previous version, sends to target machine with SCP.

// status_update --> based on info received from VM, we update status to pass or fail of a bundle
