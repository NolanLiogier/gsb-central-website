<?php

namespace App\Controllers;

use App\Repositories\CompaniesRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;
use Routing\Router;
use App\Helpers\StatusMessageService;

/**
 * Classe CompaniesController
 * Gère l'affichage de la page des entreprises.
 */
class CompaniesController {
    private CompaniesRepository $companiesRepository;
    private RenderService $renderService;
    private AuthenticationService $authenticationService;
    private StatusMessageService $statusMessageService;
    private Router $router;

    public function __construct()
    {
        $this->companiesRepository = new CompaniesRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
        $this->statusMessageService = new StatusMessageService();
        $this->router = new Router();
    }

    /**
     * Affiche la page des entreprises avec les données récupérées.
     * Vérifie d'abord que l'utilisateur est authentifié.
     *
     * @return void
     */
    public function index(): void {
        $this->authenticationService->verifyAuthentication();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (isset($_POST['companyId']) && isset($_POST['renderModifyCompany'])) {
                $this->renderModifyCompany((int)$_POST['companyId']);
                exit;
            }

            if (isset($_POST['companyId']) && isset($_POST['updateCompany'])) {
                $this->updateCompany($_POST);
                exit;
            }
        }
        
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
    public function updateCompany(array $datas): void {
        // Récupération et validation des données du formulaire
        $companyData = [
            'company_id' => $datas['companyId'] ?? '',
            'company_name' => trim($datas['companyName'] ?? ''),
            'siret' => trim($datas['siret'] ?? ''),
            'siren' => trim($datas['siren'] ?? ''),
            'sector' => $datas['sector'] ?? '',
            'salesman' => $datas['salesman'] ?? ''
        ];

        $updateStatus = $this->companiesRepository->updateCompany($companyData);
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la mise à jour.', 'error');
            $this->renderService->displayTemplates("Companies", $companyData, "Entreprises");
            exit;
        } 

        $this->statusMessageService->setMessage('Les informations de l\'entreprise ont été mises à jour avec succès.', 'success');
        $this->router->getRoute('/companies');
        exit;
    }
}
