<?php

namespace App\Controllers;

use App\Repositories\CompaniesRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;

/**
 * Classe CompaniesController
 * Gère l'affichage de la page des entreprises.
 */
class CompaniesController {
    private CompaniesRepository $companiesRepository;
    private RenderService $renderService;
    private AuthenticationService $authenticationService;

    public function __construct()
    {
        $this->companiesRepository = new CompaniesRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
    }

    /**
     * Affiche la page des entreprises avec les données récupérées.
     * Vérifie d'abord que l'utilisateur est authentifié.
     *
     * @return void
     */
    public function index(): void {
        $this->authenticationService->verifyAuthentication();        
        $datas = $this->companiesRepository->getCompanies();
        $this->displayCompanies($datas);
        exit;
    }

    /**
     * Affiche la vue de la page des entreprises.
     *
     * @param array $datas Données à passer à la vue.
     * @return void
     */
    public function displayCompanies($datas = []): void
    {
        $this->renderService->render("Companies", $datas);
        exit();
    }
}
