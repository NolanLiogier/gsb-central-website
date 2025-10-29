<?php

namespace App\Controllers;

use App\Repositories\DashboardRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;

/**
 * Classe DashboardController
 * Gère l'affichage du tableau de bord avec statistiques selon le rôle.
 */
class DashboardController {
    /**
     * Repository pour l'accès aux données du tableau de bord.
     * 
     * @var DashboardRepository
     */
    private DashboardRepository $dashboardRepository;
    
    /**
     * Service de rendu des templates.
     * 
     * @var RenderService
     */
    private RenderService $renderService;
    
    /**
     * Service de vérification d'authentification.
     * Garantit que seuls les utilisateurs connectés peuvent accéder au tableau de bord.
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
        $this->dashboardRepository = new DashboardRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
    }
    
    /**
     * Affiche le tableau de bord avec les données spécifiques au rôle.
     * 
     * Vérifie d'abord que l'utilisateur est authentifié, puis récupère
     * les données du tableau de bord selon le rôle (client, commercial, logisticien)
     * et affiche le template correspondant.
     *
     * @return void
     */
    public function index(): void {
        // Protection de l'accès : vérification de l'authentification obligatoire
        $this->authenticationService->verifyAuthentication();
        
        // Récupération des données du tableau de bord selon le rôle
        $datas = $this->dashboardRepository->getDatas();
        
        // Affichage du template avec les données récupérées
        $this->renderService->displayTemplates("Dashboard", $datas, "Tableau de bord");
        exit;
    }
}
