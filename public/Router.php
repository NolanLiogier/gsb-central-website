<?php

namespace Routing;

use App\Controllers\UserController;
use App\Controllers\DashboardController;
use App\Controllers\CompaniesController;
use App\Controllers\StockController;
use App\Controllers\CommandController;
use App\Controllers\NotFoundController;
use App\Helpers\UserService;
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
     * Service utilisateur pour la vérification de l'authentification.
     * 
     * @var UserService
     */
    private UserService $userService;

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
        $this->userService = new UserService();
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
        if (in_array($route, ['/', '/Login', '/Logout'])) {
            return true;
        }

        // Vérification de l'authentification avec le UserService
        if (!$this->userService->isAuthenticated()) {
            return false;
        }

        $userRole = $this->userService->getCurrentUserRole();
        
        // Si l'utilisateur n'a pas de rôle défini, pas d'accès
        if ($userRole === null) {
            return false;
        }

        // Commercial ou Client : accès aux entreprises et commandes
        if ($userRole == 1 || $userRole == 2) {
            return in_array($route, ['/Dashboard', '/Companies', '/ModifyCompany', '/Commands', '/ModifyCommand']);
        }

        // Logisticien : accès aux commandes et stock
        if ($userRole == 3) {
            return in_array($route, ['/Dashboard', '/Commands', '/ModifyCommand', '/Stock', '/ModifyStock']);
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
     * @param string|null $route La route à traiter (par exemple, '/', '/Login'). Si null, utilise $_SERVER['REQUEST_URI'].
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
            if (!$this->userService->isAuthenticated()) {
                header('Location: ' . $this->baseUrl . '/Login');
                exit;
            }
            // Si l'utilisateur n'a pas les permissions, redirige vers le tableau de bord
            header('Location: ' . $this->baseUrl . '/Dashboard');
            exit;
        }

        // Table de routage : associe chaque chemin à son contrôleur
        $controller = match ($route) {
            '/' => new UserController(),
            '/Login' => new UserController(),
            '/Logout' => new UserController(),
            '/Dashboard' => new DashboardController(),
            '/Companies' => new CompaniesController(),
            '/ModifyCompany' => new CompaniesController(),
            '/Stock' => new StockController(),
            '/ModifyStock' => new StockController(),
            '/Commands' => new CommandController(),
            '/ModifyCommand' => new CommandController(),
            '/NotFound' => new NotFoundController(),
            default => new NotFoundController(),
        };

        // Exécute la méthode index() du contrôleur sélectionné
        $controller->index();
    }
}