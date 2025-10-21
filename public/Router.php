<?php

namespace Routing;

use App\Controllers\LoginController;
use App\Controllers\HomeController;
use App\Controllers\NotFoundController;

class Router {
    public function getRoute(?string $route = null): void {
        if ($route === null) {
            $route = $_SERVER['REQUEST_URI'] ?? '/';
        }

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $controller = match ($route) {
            '/' => new LoginController(),
            '/login' => new LoginController(),
            '/home' => new HomeController(),
            default => new NotFoundController(),
        };

        $controller->index();
    }
}