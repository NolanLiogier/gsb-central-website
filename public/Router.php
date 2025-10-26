<?php

namespace Routing;

use App\Controllers\UserController;
use App\Controllers\HomeController;
use App\Controllers\CompaniesController;
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
     * @param string|null $route La route à traiter (par exemple, '/', '/user'). Si null, utilise $_SERVER['REQUEST_URI'].
     * @param array|null $datas Données optionnelles à passer au contrôleur.
     * @return void
     */
    public function getRoute(?string $route = null, ?array $datas = null): void {
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

        $specialRoutes = ['modify-company'];
        foreach ($specialRoutes as $specialRoute) {
            if (str_contains($route, $specialRoute)) {
                $datas = $this->handleSpecialRoute($route, $datas);
            }
        }

        $controller = match ($route) {
            '/' => new UserController(),
            '/user' => new UserController(),
            '/home' => new HomeController(),
            '/companies' => new CompaniesController(),
            '/modify-company' => new CompaniesController(),
            //'/orders' => new OrdersController(),
            //'/stock' => new StockController(),
            default => new NotFoundController(),
        };

        $controller->index($datas);
    }

    public function handleSpecialRoute(string $route, ?array $datas): array {
        $datas = $datas ?? [];
        $explodedRoute = explode('/', $route);
        $additionalData = (int)end($explodedRoute);

        if (empty($additionalData) || !is_numeric($additionalData)) {
            $notFoundController = new NotFoundController();
            $notFoundController->index();
            exit;
        }

        if (str_contains($route, 'modify-company')) {
            $datas['companyId'] = $additionalData;
        }

        return $datas;
    }
}