<?php

require_once 'vendor/autoload.php';

define('SOFT_WORKSPACE', rtrim(realpath(dirname(__FILE__) ), '/'));

use N3m3s7s\Soft\SoftServer as Server;

$server = new Server();
$server->response();