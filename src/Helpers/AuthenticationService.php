<?php

namespace App\Helpers;

use Routing\Router;

/**
 * Classe AuthenticationService
 * 
 * Service de vérification et de gestion de l'authentification des utilisateurs.
 * Vérifie la présence des variables de session nécessaires et redirige les
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
     * Initialise le service d'authentification en créant l'instance du router.
     * Le router est nécessaire pour rediriger les utilisateurs non authentifiés.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Vérifie l'authentification de l'utilisateur actuel.
     * 
     * Vérifie la présence des variables de session requises pour l'authentification.
     * Redirige automatiquement vers la page de connexion si l'utilisateur n'est pas
     * authentifié. Cette méthode doit être appelée au début de chaque page protégée.
     *
     * @return void
     */
    public function verifyAuthentication(): void
    {
        // Si l'utilisateur n'est pas connecté, redirection immédiate vers la page de connexion
        if (!$this->isUserLoggedIn()) {
            $this->router->getRoute('/login');
            exit;
        }
    }

    /**
     * Détermine si l'utilisateur est actuellement authentifié.
     * 
     * Un utilisateur est considéré comme authentifié s'il possède à la fois
     * un email et un rôle dans la session, et que ces valeurs ne sont pas vides.
     * Cette vérification empêche les sessions partiellement corrompues.
     *
     * @return bool True si l'utilisateur est connecté, false sinon.
     */
    public function isUserLoggedIn(): bool
    {
        // Vérification de la présence ET de la non-vacuité des variables de session essentielles
        return isset($_SESSION['user_email']) && 
               isset($_SESSION['user_role']) && 
               !empty($_SESSION['user_email']) && 
               !empty($_SESSION['user_role']);
    }
}
