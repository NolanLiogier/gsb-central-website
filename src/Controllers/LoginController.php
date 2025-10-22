<?php

namespace App\Controllers;

use App\Repositories\LoginRepository;
use App\Helpers\RenderService;

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

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
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

        $email = $_POST['email'] ?? "";
        $password = $_POST['password'] ?? "";
        $loginRepository = new LoginRepository();

        $user = $loginRepository->getUserByEmail($email);
        if (empty($user)) {
            $_SESSION['notification'] = [
                'type' => 'danger',
                'message' => 'Utilisateur inconnu',
                'duration' => 5000
            ];
            $this->displayLogin();
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            $_SESSION['notification'] = [
                'type' => 'danger',
                'message' => 'Mot de passe incorrect',
                'duration' => 5000
            ];
            $this->displayLogin();
            exit;
        }

        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => 'Connexion réussie',
            'duration' => 3000
        ];
        
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        
        header('Location: /home', true, 302);
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


