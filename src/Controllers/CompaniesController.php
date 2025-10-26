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
     * @param array|null $datas Données optionnelles à passer au contrôleur.
     * @return void
     */
    public function index(?array $datas = null): void {
        $this->authenticationService->verifyAuthentication();

        
        
        $datas = $this->companiesRepository->getCompanies();
        $this->renderService->displayTemplates("Companies", $datas);
        exit;
    }

    /**
     * Affiche la page de modification d'une entreprise.
     *
     * @param int $companyId ID de l'entreprise à modifier.
     * @return void
     */
    public function renderModifyCompany(int $companyId): void {
        $datas = $this->companiesRepository->getCompanyData($companyId);
        $datas['sectors'] = $this->companiesRepository->getSectors();
        $datas['salesmen'] = $this->companiesRepository->getSalesmen();
        $this->renderService->displayTemplates("ModifyCompany", $datas, "Modifier l'entreprise");
        exit;
    }

    /**
     * Traite la soumission du formulaire de modification d'entreprise.
     * Valide les données et met à jour l'entreprise dans la base de données.
     *
     * @return void
     */
    public function updateCompany(): void {
        $this->authenticationService->verifyAuthentication();
        
        // Vérification que la requête est en POST
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->renderService->displayTemplates("Companies", [], "Entreprises");
            return;
        }

        // Récupération et validation des données du formulaire
        $companyData = [
            'company_id' => $_POST['company_id'] ?? '',
            'company_name' => trim($_POST['companyName'] ?? ''),
            'siret' => trim($_POST['siret'] ?? ''),
            'siren' => trim($_POST['siren'] ?? ''),
            'sector' => $_POST['sector'] ?? '',
            'salesman' => $_POST['salesman'] ?? ''
        ];

        $updateStatus = $this->companiesRepository->updateCompany($companyData);
        if (!$updateStatus) {
            $this->renderService->displayTemplates("Companies", ['errors' => ['Une erreur est survenue lors de la mise à jour.']], "Entreprises");
            exit;
        } 
            
        $this->renderService->displayTemplates("Companies", ['success' => 'Les informations de l\'entreprise ont été mises à jour avec succès.'], "Entreprises");
        exit;
    }
}
