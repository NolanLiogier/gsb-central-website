<?php

namespace App\Controllers;

use App\Repositories\UserRepository;
use Routing\Router;
use App\Helpers\RenderService;
use App\Helpers\StatusMessageService;

/**
 * Classe UserController
 * Gère l'authentification des utilisateurs.
 */
class UserController
{
    private UserRepository $userRepository;
    private Router $router;
    private RenderService $renderService;
    private StatusMessageService $statusMessageService;

    /**
     * Constructeur du UserController.
     * Initialise les repositories et services nécessaires.
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->renderService = new RenderService();
        $this->statusMessageService = new StatusMessageService();
        $this->router = new Router();
    }

    /**
     * Gère la logique d'affichage et de traitement du formulaire de connexion.
     *
     * @return void
     */
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
            $this->login();
            exit;
        }
        else {
            $this->renderService->render("Login");
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

        $user = $this->userRepository->getUserByEmail($email);
        if (empty($user)) {
            $this->statusMessageService->displayMessageAndRedirect('Utilisateur inconnu', 'error', 'Login', $this->renderService);
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            $this->statusMessageService->displayMessageAndRedirect('Mot de passe incorrect', 'error', 'Login', $this->renderService);
            exit;
        }

        $this->statusMessageService->setSuccessMessage('Connexion réussie');
        
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['function_name'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        
        $this->router->getRoute('/home');
        exit;
    }

}
