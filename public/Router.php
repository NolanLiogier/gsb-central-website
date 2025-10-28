<?php

namespace Routing;

use App\Controllers\UserController;
use App\Controllers\HomeController;
use App\Controllers\CompaniesController;
use App\Controllers\StockController;
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
     * Vérifie si l'utilisateur actuel a accès à la route demandée selon son rôle.
     * 
     * Vérifie les permissions d'accès selon le rôle :
     * - Commercial (function_id = 1) : accès aux entreprises et commandes
     * - Client (function_id = 2) : accès aux entreprises et commandes
     * - Logisticien (function_id = 3) : accès aux commandes et stock
     *
     * @param string $route La route à vérifier.
     * @return bool True si l'utilisateur a accès, false sinon.
     */
    private function hasAccessToRoute(string $route): bool
    {
        // Routes accessibles à tous sans authentification
        if (in_array($route, ['/', '/login', '/logout'])) {
            return true;
        }

        // Si la session n'a pas de function_id, pas d'accès
        if (!isset($_SESSION['user_function_id'])) {
            return false;
        }

        $userFunctionId = (int)$_SESSION['user_function_id'];

        // Commercial ou Client : accès aux entreprises et commandes
        if ($userFunctionId == 1 || $userFunctionId == 2) {
            return in_array($route, ['/home', '/companies', '/modify-company', '/orders']);
        }

        // Logisticien : accès aux commandes et stock
        if ($userFunctionId == 3) {
            return in_array($route, ['/home', '/orders', '/stock', '/modify-stock']);
        }

        // Par défaut, refuser l'accès
        return false;
    }

    /**
     * Détermine la route actuelle et instancie le contrôleur correspondant.
     * 
     * Gère le routage en fonction de l'URI de la requête. Si une route externe est fournie,
     * effectue une redirection vers l'URL complète. Démarre automatiquement la session
     * si nécessaire, vérifie les permissions selon le rôle de l'utilisateur et instancie
     * le contrôleur approprié selon le chemin demandé. Les routes non définies sont traitées
     * par NotFoundController.
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

        // Vérification des permissions d'accès selon le rôle de l'utilisateur
        // Redirige vers la page de connexion si l'utilisateur n'a pas accès à cette route
        if (!$this->hasAccessToRoute($route)) {
            // Si l'utilisateur n'est pas connecté, redirige vers le login
            if (!isset($_SESSION['user_email'])) {
                header('Location: ' . $this->baseUrl . '/login');
                exit;
            }
            // Si l'utilisateur n'a pas les permissions, redirige vers le home
            header('Location: ' . $this->baseUrl . '/home');
            exit;
        }

        // Table de routage : associe chaque chemin à son contrôleur
        $controller = match ($route) {
            '/' => new UserController(),
            '/login' => new UserController(),
            '/logout' => new UserController(),
            '/home' => new HomeController(),
            '/companies' => new CompaniesController(),
            '/modify-company' => new CompaniesController(),
            '/stock' => new StockController(),
            '/modify-stock' => new StockController(),
            '/not-found' => new NotFoundController(),
            //'/orders' => new OrdersController(),
            default => new NotFoundController(),
        };

        // Exécute la méthode index() du contrôleur sélectionné
        $controller->index();
    }
}