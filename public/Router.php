<?php

namespace Routing;

use App\Controllers\LoginController;
use App\Controllers\HomeController;
use App\Controllers\NotFoundController;
use Dotenv\Dotenv;

/**
 * Classe Router
 * Gère le routage des requêtes HTTP vers les contrôleurs appropriés.
 */
class Router {

    private string $baseUrl;

    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        $this->baseUrl = $_ENV['BASE_URL'];
    }
    /**
     * Détermine la route actuelle et instancie le contrôleur correspondant.
     * Si aucune route n'est spécifiée, utilise l'URI de la requête.
     * Démarre la session si elle n'est pas déjà active.
     *
     * @param string|null $route La route à traiter (par exemple, '/', '/login'). Si null, utilise $_SERVER['REQUEST_URI'].
     * @return void
     */
    public function getRoute(?string $route = null): void {
        if (!$route) {
            $route = $_SERVER['REQUEST_URI'] ?? '/';
        }

        if ($route !== $_SERVER['REQUEST_URI']) {
            $baseUrl = $this->baseUrl;
            $cleanRoute = '/' . ltrim($route, '/');
            $redirectUrl = $baseUrl . $cleanRoute;
            
            header('Location: ' . $redirectUrl, true);
            exit;
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