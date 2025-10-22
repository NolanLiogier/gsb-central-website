<?php

namespace App\Controllers;

use App\Repositories\LoginRepository;
use App\Helpers\RenderService;
use Routing\Router;

/**
 * Classe LoginController
 * Gère l'authentification des utilisateurs.
 */
class LoginController
{
    /**
     * Gère la logique d'affichage et de traitement du formulaire de connexion.
     *
     * @return void
     */
    public function index(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['email']) {
            $this->login();
            exit;
        }
        else {
            $this->displayLogin();
            exit;
        }
    }

    /**
     * Traite la tentative de connexion de l'utilisateur.
     *
     * @return void
     */
    public function login(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $email = $_POST['email'] ?? '';
        $loginRepository = new LoginRepository();

        if (!$loginRepository->checkEmailExists(email: $email)) {
            $_SESSION['error_message']  = 'Utilisateur inconnu';
            $this->displayLogin();
            exit;
        }

        $_SESSION['succes_message']  = 'Utilisateur correct';
        $router = new Router();
        $router->getRoute("/home");
        exit;
    }

    /**
     * Affiche la page de connexion.
     *
     * @return void
     */
    public function displayLogin(): void
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $renderService = new RenderService();
        $renderService->render("Login");
        exit();
    }
}


