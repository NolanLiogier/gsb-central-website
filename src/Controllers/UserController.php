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
            $this->renderService->displayTemplates("Login", [], "Connexion");
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
            $this->statusMessageService->setMessage('Utilisateur inconnu', 'error');
            $this->router->getRoute('/user');
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            $this->statusMessageService->setMessage('Mot de passe incorrect', 'error');
            $this->router->getRoute('/user');
            exit;
        }

        $this->statusMessageService->setMessage('Connexion réussie', 'success');
        
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['function_name'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        
        $this->router->getRoute('/home');
        exit;
    }

}
