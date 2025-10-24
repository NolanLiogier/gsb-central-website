<?php

namespace App\Helpers;

use Routing\Router;

/**
 * Classe AuthenticationService
 * Gère la vérification de l'authentification des utilisateurs.
 */
class AuthenticationService
{
    private Router $router;

    /**
     * Constructeur du AuthenticationService.
     * Initialise le router pour les redirections.
     */
    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Vérifie si l'utilisateur est connecté en vérifiant la présence des variables de session requises.
     * Redirige vers la page de connexion si l'utilisateur n'est pas authentifié.
     *
     * @return void
     */
    public function verifyAuthentication(): void
    {
        if (!$this->isUserLoggedIn()) {
            $this->router->getRoute('/login');
            exit;
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté.
     * Un utilisateur est considéré comme connecté s'il a un email et un rôle dans la session.
     *
     * @return bool True si l'utilisateur est connecté, false sinon.
     */
    public function isUserLoggedIn(): bool
    {
        return isset($_SESSION['user_email']) && 
               isset($_SESSION['user_role']) && 
               !empty($_SESSION['user_email']) && 
               !empty($_SESSION['user_role']);
    }
}
