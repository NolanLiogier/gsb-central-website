<?php

namespace App\Controllers;

use App\Repositories\HomeRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;

/**
 * Classe HomeController
 * Gère l'affichage de la page d'accueil.
 */
class HomeController {
    /**
     * Repository pour l'accès aux données de la page d'accueil.
     * 
     * @var HomeRepository
     */
    private HomeRepository $homeRepository;
    
    /**
     * Service de rendu des templates.
     * 
     * @var RenderService
     */
    private RenderService $renderService;
    
    /**
     * Service de vérification d'authentification.
     * Garantit que seuls les utilisateurs connectés peuvent accéder à l'accueil.
     * 
     * @var AuthenticationService
     */
    private AuthenticationService $authenticationService;

    /**
     * Initialise le contrôleur en créant toutes les dépendances nécessaires.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->homeRepository = new HomeRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
    }
    
    /**
     * Affiche la page d'accueil avec les données récupérées.
     * 
     * Vérifie d'abord que l'utilisateur est authentifié, puis récupère
     * les données de la page d'accueil (statistiques, notifications, etc.)
     * et affiche le template correspondant.
     *
     * @return void
     */
    public function index(): void {
        // Protection de l'accès : vérification de l'authentification obligatoire
        $this->authenticationService->verifyAuthentication();
        
        // Récupération des données de la page d'accueil
        $datas = $this->homeRepository->getDatas();
        
        // Affichage du template avec les données récupérées
        $this->renderService->displayTemplates("Home", $datas, "Accueil");
        exit;
    }
}
