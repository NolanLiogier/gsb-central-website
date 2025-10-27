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
    /**
     * Repository pour l'accès aux données des entreprises.
     * 
     * @var CompaniesRepository
     */
    private CompaniesRepository $companiesRepository;
    
    /**
     * Service de rendu des templates.
     * 
     * @var RenderService
     */
    private RenderService $renderService;
    
    /**
     * Service de vérification d'authentification.
     * Garantit que seuls les utilisateurs connectés peuvent accéder aux pages.
     * 
     * @var AuthenticationService
     */
    private AuthenticationService $authenticationService;
    
    /**
     * Service de gestion des messages de statut (success/error).
     * 
     * @var StatusMessageService
     */
    private StatusMessageService $statusMessageService;
    
    /**
     * Router pour les redirections après traitement des requêtes.
     * 
     * @var Router
     */
    private Router $router;

    /**
     * Initialise le contrôleur en créant toutes les dépendances nécessaires.
     * Les services sont instanciés ici pour faciliter l'injection de dépendances.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->companiesRepository = new CompaniesRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
        $this->statusMessageService = new StatusMessageService();
        $this->router = new Router();
    }

    /**
     * Point d'entrée principal du contrôleur des entreprises.
     * 
     * Vérifie l'authentification, route les requêtes POST vers les méthodes appropriées
     * (affichage du formulaire de modification ou mise à jour), et affiche la liste
     * des entreprises pour les requêtes GET.
     *
     * @return void
     */
    public function index(): void {
        // Vérification obligatoire de l'authentification avant tout traitement
        $this->authenticationService->verifyAuthentication();

        // Gestion des différentes actions POST avec routing basé sur les champs présents
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Action pour afficher le formulaire de modification d'une entreprise
            if (isset($_POST['companyId']) && isset($_POST['renderModifyCompany'])) {
                $this->renderModifyCompany((int)$_POST['companyId']);
                exit;
            }

            // Action pour mettre à jour les données d'une entreprise
            if (isset($_POST['companyId']) && isset($_POST['updateCompany'])) {
                $this->updateCompany($_POST);
                exit;
            }
        }
        
        // Requête GET : affichage de la liste des entreprises
        $datas = $this->companiesRepository->getCompanies();
        $this->renderService->displayTemplates("Companies", $datas);
        exit;
    }

    /**
     * Prépare et affiche le formulaire de modification d'une entreprise.
     * 
     * Récupère les données de l'entreprise, les secteurs et les commerciaux
     * pour peupler les listes déroulantes du formulaire.
     *
     * @param int $companyId ID de l'entreprise à modifier.
     * @return void
     */
    public function renderModifyCompany(int $companyId): void {
        // Récupération des données de l'entreprise
        $datas = $this->companiesRepository->getCompanyData($companyId);
        
        // Ajout des secteurs et commerciaux pour les listes déroulantes du formulaire
        $datas['sectors'] = $this->companiesRepository->getSectors();
        $datas['salesmen'] = $this->companiesRepository->getSalesmen();
        
        $this->renderService->displayTemplates("ModifyCompany", $datas, "Modifier l'entreprise");
        exit;
    }

    /**
     * Traite la soumission du formulaire de modification d'entreprise.
     * 
     * Nettoie les données du formulaire, tente la mise à jour en base,
     * affiche un message d'erreur en cas d'échec, ou redirige vers la liste
     * des entreprises avec un message de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function updateCompany(array $datas): void {
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        // Évite les erreurs de données manquantes et normalise les chaînes de caractères
        $companyData = [
            'company_id' => $datas['companyId'] ?? '',
            'company_name' => trim($datas['companyName'] ?? ''),
            'siret' => trim($datas['siret'] ?? ''),
            'siren' => trim($datas['siren'] ?? ''),
            'sector' => $datas['sector'] ?? '',
            'salesman' => $datas['salesman'] ?? ''
        ];

        // Tentative de mise à jour dans la base de données
        $updateStatus = $this->companiesRepository->updateCompany($companyData);
        
        // Gestion de l'échec : affichage d'un message d'erreur et du formulaire
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la mise à jour.', 'error');
            $this->renderService->displayTemplates("Companies", $companyData, "Entreprises");
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des entreprises
        $this->statusMessageService->setMessage('Les informations de l\'entreprise ont été mises à jour avec succès.', 'success');
        $this->router->getRoute('/companies');
        exit;
    }
}
