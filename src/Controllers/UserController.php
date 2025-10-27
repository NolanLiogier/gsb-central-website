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
    /**
     * Repository pour l'accès aux données des utilisateurs.
     * 
     * @var UserRepository
     */
    private UserRepository $userRepository;
    
    /**
     * Router pour les redirections après authentification.
     * 
     * @var Router
     */
    private Router $router;
    
    /**
     * Service de rendu des templates.
     * 
     * @var RenderService
     */
    private RenderService $renderService;
    
    /**
     * Service de gestion des messages de statut (success/error).
     * 
     * @var StatusMessageService
     */
    private StatusMessageService $statusMessageService;

    /**
     * Initialise le contrôleur en créant toutes les dépendances nécessaires.
     * 
     * Les services sont instanciés ici pour faciliter la gestion des sessions
     * et des messages de retour d'authentification.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
        $this->renderService = new RenderService();
        $this->statusMessageService = new StatusMessageService();
        $this->router = new Router();
    }

    /**
     * Point d'entrée principal du contrôleur d'authentification.
     * 
     * Route les requêtes : affiche le formulaire de connexion pour les requêtes GET,
     * ou traite le formulaire soumis pour les requêtes POST avec email et password.
     *
     * @return void
     */
    public function index(): void
    {
        // Vérification de la présence des données POST pour le traitement de la connexion
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['password'])) {
            $this->login();
            exit;
        }
        else {
            // Requête GET : affichage du formulaire de connexion
            $this->renderService->displayTemplates("Login", [], "Connexion");
            exit;
        }
    }

    /**
     * Traite la tentative de connexion de l'utilisateur.
     * 
     * Vérifie l'existence de l'utilisateur par email, valide le mot de passe hashé,
     * crée la session avec les informations utilisateur en cas de succès, ou affiche
     * un message d'erreur en cas d'échec.
     *
     * @return void
     */
    public function login(): void
    {
        // Récupération sécurisée des données POST avec valeurs par défaut
        $email = $_POST['email'] ?? "";
        $password = $_POST['password'] ?? "";

        // Vérification de l'existence de l'utilisateur dans la base de données
        $user = $this->userRepository->getUserByEmail($email);
        if (empty($user)) {
            $this->statusMessageService->setMessage('Utilisateur inconnu', 'error');
            $this->router->getRoute('/login');
            exit;
        }

        // Vérification du mot de passe hashé avec password_verify (sécurité contre timing attacks)
        if (!password_verify($password, $user['password'])) {
            $this->statusMessageService->setMessage('Mot de passe incorrect', 'error');
            $this->router->getRoute('/login');
            exit;
        }

        // Succès : message de confirmation et création de la session utilisateur
        $this->statusMessageService->setMessage('Connexion réussie', 'success');
        
        // Stockage des informations utilisateur dans la session pour l'authentification ultérieure
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['function_name'];
        $_SESSION['user_function_id'] = $user['fk_function_id'];
        $_SESSION['user_firstname'] = $user['firstname'];
        $_SESSION['user_lastname'] = $user['lastname'];
        
        // Redirection vers la page d'accueil après authentification réussie
        $this->router->getRoute('/home');
        exit;
    }

}
