<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

$envTest = dirname(__DIR__) . '/.env.test';
if (file_exists($envTest)) {
    $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__), '.env.test');
    $dotenv->load();
}
