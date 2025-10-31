<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
ini_set('error_log', 'php_error.log');

// Configuration sécurisée des cookies de session
// SameSite=Strict : protection CSRF, HttpOnly : protection XSS, Secure : uniquement HTTPS
$isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => $isSecure,
    'httponly' => true,
    'samesite' => 'Strict'
]);

session_start();
require_once 'vendor/autoload.php';

use Routing\Router;

$router = new Router();
$router->getRoute();
exit();