<?php

namespace App\Controllers;

use App\Repositories\LoginRepository;
use App\Helpers\RenderService;

/**
 * Classe LoginController
 * Gère l\'authentification des utilisateurs.
 */
class LoginController
{
    /**
     * Gère la logique d\'affichage et de traitement du formulaire de connexion.
     *
     * @return void
     */
    public function index(): void
    {
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
     * Traite la tentative de connexion de l\'utilisateur.
     *
     * @return void
     */
    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $loginRepository = new LoginRepository();

        if (!$loginRepository->checkEmailExists(email: $email)) {
            $_SESSION['message'] = 'Utilisateur inconnu';
            $this->displayLogin();
            exit;
        }

        $_SESSION['logged_in'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['message'] = 'Vous avez bien connecté';
        header('Location: /home');
        exit;
    }

    /**
     * Affiche la page de connexion.
     *
     * @param array $datas Données à passer à la vue.
     * @return void
     */
    public function displayLogin($datas = []): void
    {
        $renderService = new RenderService();
        $renderService->render("Login", $datas);
        exit();
    }
}

