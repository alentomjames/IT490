<?php

// Creates api_handler error logs that tell of any issues that occurred when the api_handler was running.

require_once 'webserver/rabbitmq_connection.php';
require_once 'webserver/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use GuzzleHttp\Exception\RequestException;

?>