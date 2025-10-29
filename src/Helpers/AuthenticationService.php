<?php

namespace App\Helpers;

use Routing\Router;

/**
 * Classe AuthenticationService
 * 
 * Service de vérification et de gestion de l'authentification des utilisateurs.
 * Utilise le UserService pour vérifier l'authentification et redirige les
 * utilisateurs non authentifiés vers la page de connexion.
 */
class AuthenticationService
{
    /**
     * Router pour effectuer les redirections vers la page de connexion.
     * 
     * @var Router
     */
    private Router $router;

    /**
     * Service utilisateur pour la vérification de l'authentification.
     * 
     * @var UserService
     */
    private UserService $userService;

    /**
     * Initialise le service d'authentification en créant les instances nécessaires.
     * Le router est nécessaire pour rediriger les utilisateurs non authentifiés,
     * et le UserService pour vérifier l'authentification de manière sécurisée.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->router = new Router();
        $this->userService = new UserService();
    }

    /**
     * Vérifie l'authentification de l'utilisateur actuel.
     * 
     * Utilise le UserService pour vérifier l'authentification de manière sécurisée.
     * Redirige automatiquement vers la page de connexion si l'utilisateur n'est pas
     * authentifié. Cette méthode doit être appelée au début de chaque page protégée.
     *
     * @return void
     */
    public function verifyAuthentication(): void
    {
        // Si l'utilisateur n'est pas connecté, redirection immédiate vers la page de connexion
        if (!$this->userService->isAuthenticated()) {
            $this->router->getRoute('/Login');
            exit;
        }
    }

    /**
     * Détermine si l'utilisateur est actuellement authentifié.
     * 
     * Utilise le UserService pour vérifier l'authentification de manière sécurisée.
     * Cette méthode vérifie la présence des données de session et l'intégrité
     * des données avec le hash de sécurité.
     *
     * @return bool True si l'utilisateur est connecté, false sinon.
     */
    public function isUserLoggedIn(): bool
    {
        return $this->userService->isAuthenticated();
    }
}
