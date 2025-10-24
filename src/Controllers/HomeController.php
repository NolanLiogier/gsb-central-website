<?php

namespace App\Controllers;

use App\Repositories\HomeRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;

/**
 * Classe HomeController
 * Gère l\'affichage de la page d\'accueil.
 */
class HomeController {
    private HomeRepository $homeRepository;
    private RenderService $renderService;
    private AuthenticationService $authenticationService;

    public function __construct()
    {
        $this->homeRepository = new HomeRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
    }
    /**
     * Affiche la page d\'accueil avec les données récupérées.
     * Vérifie d\'abord que l\'utilisateur est authentifié.
     *
     * @return void
     */
    public function index(): void {
        $this->authenticationService->verifyAuthentication();        
        $datas = $this->homeRepository->getDatas();
        $this->displayHome($datas);
        exit;
    }

    /**
     * Affiche la vue de la page d\'accueil.
     *
     * @param array $datas Données à passer à la vue.
     * @return void
     */
    public function displayHome($datas = []): void
    {
        $this->renderService->render("Home", $datas);
        exit();
    }
}
