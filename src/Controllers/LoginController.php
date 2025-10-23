<?php

namespace App\Controllers;

use App\Repositories\LoginRepository;
use Routing\Router;
use App\Repositories\TempTokenRepository;
use App\Helpers\RenderService;
use App\Helpers\StatusMessageService;

/**
 * Classe LoginController
 * Gère l'authentification des utilisateurs.
 */
class LoginController
{
    private LoginRepository $loginRepository;
    private Router $router;
    private TempTokenRepository $tempTokenRepository;
    private RenderService $renderService;
    private StatusMessageService $statusMessageService;

    /**
     * Constructeur du LoginController.
     * Initialise les repositories et services nécessaires.
     */
    public function __construct()
    {
        $this->loginRepository = new LoginRepository();
        $this->tempTokenRepository = new TempTokenRepository();
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

        $user = $this->loginRepository->getUserByEmail($email);
        if (empty($user)) {
            $this->handleResult('Utilisateur inconnu', 'error', 'Login');
            exit;
        }

        if (!password_verify($password, $user['password'])) {
            $this->handleResult('Mot de passe incorrect', 'error', 'Login');
            exit;
        }

        $tokenValue = bin2hex(random_bytes(32));
        $tokenId = $user['fk_token_id'];

        $tokenUpdate = $this->tempTokenRepository->updateTokenValue($tokenId, $tokenValue);
        if (!$tokenUpdate) {
            $this->handleResult('Erreur lors de la mise à jour du token', 'error', 'Login');
            exit;
        }

        $this->statusMessageService->setSuccessMessage('Connexion réussie');
        
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        
        $this->router->getRoute('/home');
        exit;
    }

    public function handleResult($message, $typeMessage, $route): void
    {
        match ($typeMessage) {
            'success' => $this->statusMessageService->setSuccessMessage($message),
            'error' => $this->statusMessageService->setErrorMessage($message),
            'warning' => $this->statusMessageService->setWarningMessage($message),
            default => throw new \Exception('Type de message invalide'),
        };
        $this->renderService->render($route);
        exit();
    }
}
