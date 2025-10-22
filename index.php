<?php
session_start();
require_once 'vendor/autoload.php';

use Routing\Router;

$router = new Router();
$router->getRoute();
exit();