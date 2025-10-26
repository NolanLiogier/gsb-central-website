<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('error_log', 'php_error.log');

session_start();
require_once 'vendor/autoload.php';

use Routing\Router;

$router = new Router();
$router->getRoute();
exit();