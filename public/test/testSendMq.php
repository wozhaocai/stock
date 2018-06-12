<?php
/**
 * test RabbitMQ
 */

require_once "test.inc.php";

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

define('AMQP_DEBUG', true);

$connection = new AMQPStreamConnection('39.106.49.74', 15670, 'admin', 'admin');
$channel = $connection->channel();
$channel->queue_declare('keguan_api_channel', false, false, false, false);
$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'keguan_api_channel');
echo " [x] Sent 'Hello World!'\n";
$channel->close();
$connection->close();




