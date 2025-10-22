<?php

namespace App\Controllers;

use App\Repositories\LoginRepository;
use App\Repositories\TempTokenRepository;
use App\Helpers\RenderService;

/**
 * Classe LoginController
 * Gère l'authentification des utilisateurs.
 */
class LoginController
{
    private LoginRepository $loginRepository;
    private TempTokenRepository $tempTokenRepository;
    private RenderService $renderService;

    /**
     * Constructeur du LoginController.
     * Initialise les repositories et services nécessaires.
     */
    public function __construct()
    {
        $this->loginRepository = new LoginRepository();
        $this->tempTokenRepository = new TempTokenRepository();
        $this->renderService = new RenderService();
    }

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
        $email = $_POST['email'] ?? "";
        $password = $_POST['password'] ?? "";

        $user = $this->loginRepository->getUserByEmail($email);
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

        $tokenValue = bin2hex(random_bytes(32));
        $tokenId = $user['fk_token_id'];

        $tokenUpdate = $this->tempTokenRepository->updateTokenValue($tokenId, $tokenValue);
        if (!$tokenUpdate) {
            $_SESSION['notification'] = [
                'type' => 'danger',
                'message' => 'Erreur lors de la mise à jour du token',
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
        $this->renderService->render("Login");
        exit();
    }
}


