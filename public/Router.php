<?php

namespace App\Public;

use App\Controllers\HomeController;
use App\Controllers\NotFoundController;

class Router {
    public function getRoute(?string $route = null): void {
        if ($route === null) {
            $route = $_SERVER['REQUEST_URI'] ?? '/';
        }

        $controller = match ($route) {
            '/' => new HomeController(),
            default => new NotFoundController(),
        };

        $controller->index();
    }
}