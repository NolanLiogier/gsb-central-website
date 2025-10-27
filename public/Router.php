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

    /**
     * URL de base de l'application pour les redirections.
     * Chargée depuis la variable d'environnement BASE_URL.
     * 
     * @var string
     */
    private string $baseUrl;

    /**
     * Initialise le router en chargeant les variables d'environnement
     * et en configurant l'URL de base depuis .env.
     * 
     * @return void
     */
    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();
        $this->baseUrl = $_ENV['BASE_URL'];
    }
    /**
     * Détermine la route actuelle et instancie le contrôleur correspondant.
     * 
     * Gère le routage en fonction de l'URI de la requête. Si une route externe est fournie,
     * effectue une redirection vers l'URL complète. Démarre automatiquement la session
     * si nécessaire et instancie le contrôleur approprié selon le chemin demandé.
     * Les routes non définies sont traitées par NotFoundController.
     *
     * @param string|null $route La route à traiter (par exemple, '/', '/login'). Si null, utilise $_SERVER['REQUEST_URI'].
     * @return void
     */
    public function getRoute(?string $route = null): void {
        // Utilise l'URI de la requête si aucune route n'est fournie
        if (!$route) {
            $route = $_SERVER['REQUEST_URI'] ?? '/';
        }

        // Si la route fournie diffère de l'URI actuel, redirige vers l'URL complète
        // Cela permet de gérer les appels externes au router avec redirection propre
        if ($route !== $_SERVER['REQUEST_URI']) {
            $baseUrl = $this->baseUrl;
            $cleanRoute = '/' . ltrim($route, '/');
            $redirectUrl = $baseUrl . $cleanRoute;
            
            header('Location: ' . $redirectUrl, true);
            exit;
        }

        // Démarre la session si elle n'est pas déjà active
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Table de routage : associe chaque chemin à son contrôleur
        $controller = match ($route) {
            '/' => new UserController(),
            '/login' => new UserController(),
            '/home' => new HomeController(),
            '/companies' => new CompaniesController(),
            '/modify-company' => new CompaniesController(),
            '/not-found' => new NotFoundController(),
            //'/orders' => new OrdersController(),
            //'/stock' => new StockController(),
            default => new NotFoundController(),
        };

        // Exécute la méthode index() du contrôleur sélectionné
        $controller->index();
    }
}