<?php

namespace Routing;

use App\Controllers\LoginController;
use App\Controllers\HomeController;
use App\Controllers\NotFoundController;

/**
 * Classe Router
 * Gère le routage des requêtes HTTP vers les contrôleurs appropriés.
 */
class Router {
    /**
     * Détermine la route actuelle et instancie le contrôleur correspondant.
     * Si aucune route n'est spécifiée, utilise l'URI de la requête.
     * Démarre la session si elle n'est pas déjà active.
     *
     * @param string|null $route La route à traiter (par exemple, '/', '/login'). Si null, utilise $_SERVER['REQUEST_URI'].
     * @return void
     */
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