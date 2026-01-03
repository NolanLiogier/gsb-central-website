<?php

namespace App\Controllers;

use App\Repositories\CompaniesRepository;
use App\Repositories\UserRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;
use App\Helpers\PermissionVerificationService;
use App\Helpers\UserService;
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
     * Repository pour l'accès aux données des utilisateurs.
     * 
     * @var UserRepository
     */
    private UserRepository $userRepository;
    
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
     * Service de vérification des permissions.
     * 
     * @var PermissionVerificationService
     */
    private PermissionVerificationService $permissionVerificationService;
    
    /**
     * Service utilisateur pour la récupération des données utilisateur.
     * 
     * @var UserService
     */
    private UserService $userService;
    
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
        $this->userRepository = new UserRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
        $this->statusMessageService = new StatusMessageService();
        $this->permissionVerificationService = new PermissionVerificationService();
        $this->userService = new UserService();
        $this->router = new Router();
    }

    /**
     * Récupère les informations complètes de l'utilisateur connecté depuis la base de données.
     * Utilise le UserService pour récupérer les informations utilisateur de manière sécurisée.
     *
     * @return array Informations utilisateur (user_id, email, fk_company_id, fk_function_id, etc.)
     */
    private function getCurrentUser(): array {
        $user = $this->userService->getCurrentUser();
        
        if (empty($user)) {
            $this->router->redirect('/Login');
            exit;
        }
        
        return $user;
    }

    /**
     * Point d'entrée principal du contrôleur des entreprises.
     * 
     * Vérifie l'authentification, route les requêtes POST vers les méthodes appropriées
     * (affichage du formulaire de modification ou mise à jour), et affiche la liste
     * des entreprises pour les requêtes GET. Gère les différences d'accès selon le rôle :
     * - Clients : voient uniquement leur propre entreprise (pas d'accès à la liste)
     * - Commerciaux : voient les entreprises qui leur sont assignées
     *
     * @return void
     */
    public function index(): void {
        // Vérification obligatoire de l'authentification avant tout traitement
        $this->authenticationService->verifyAuthentication();

        // Gestion des actions POST spécifiques (affichage formulaire ou mise à jour)
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

            // Action pour afficher le formulaire d'ajout d'une nouvelle entreprise
            if (isset($_POST['newCompany']) && isset($_POST['renderAddCompany'])) {
                $this->renderAddCompany();
                exit;
            }

            // Action pour créer une nouvelle entreprise
            if (isset($_POST['newCompany']) && isset($_POST['createCompany'])) {
                $this->createCompany($_POST);
                exit;
            }

            // Action pour supprimer une entreprise
            if (isset($_POST['companyId']) && isset($_POST['deleteCompany'])) {
                $this->deleteCompany((int)$_POST['companyId']);
                exit;
            }

        }

        // Récupération des données des entreprises selon le rôle de l'utilisateur
        $datas = [];
        $user = $this->getCurrentUser();
        $userFunctionId = $user['fk_function_id'] ?? null;
        $userCompanyId = $user['fk_company_id'] ?? null;
        $userId = $user['user_id'] ?? null;

        if (!empty($userId) && $userFunctionId == 1) {
            $datas = $this->companiesRepository->getCompaniesBySalesman($userId);
            $this->renderService->displayTemplates("Companies", $datas);
        } 
        elseif (!empty($userCompanyId) && $userFunctionId == 2) {
            $datas = $this->companiesRepository->getCompanyById($userCompanyId);
            // Ajout des secteurs et commerciaux pour les listes déroulantes du formulaire
            $datas['sectors'] = $this->companiesRepository->getSectors();
            $datas['salesmen'] = $this->companiesRepository->getSalesmen();
            // Ajout du function_id de l'utilisateur pour le template
            $datas['user_function_id'] = $user['fk_function_id'] ?? null;
            $this->renderService->displayTemplates("ModifyCompany", $datas);
        }

        if (empty($datas)) {
            $this->statusMessageService->setMessage('Aucune entreprise trouvée.', 'error');
            $this->router->redirect('/Companies');
            exit;
        }

        // Affichage du template avec les données récupérées
        $this->renderService->displayTemplates("Companies", $datas);
        exit;
    }

    /**
     * Prépare et affiche le formulaire de modification d'une entreprise.
     * 
     * Récupère les données de l'entreprise, les secteurs et les commerciaux
     * pour peupler les listes déroulantes du formulaire. Vérifie que l'utilisateur
     * a le droit d'accéder à cette entreprise selon son rôle.
     *
     * @param int $companyId ID de l'entreprise à modifier.
     * @return void
     */
    public function renderModifyCompany(int $companyId): void {
        // Récupération des informations utilisateur depuis la base de données
        $user = $this->getCurrentUser();

        // Vérification des permissions avec le service de vérification
        if (!$this->permissionVerificationService->canAccessCompany($user, ['companyId' => $companyId])) {
            $this->statusMessageService->setMessage('Accès non autorisé à cette entreprise.', 'error');
            $this->router->redirect('/Companies');
            exit;
        }

        // Récupération des données de l'entreprise
        $datas = $this->companiesRepository->getCompanyById($companyId);
        
        // Vérification que l'entreprise existe
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Entreprise introuvable.', 'error');
            $this->router->redirect('/Companies');
            exit;
        }
        
        // Ajout des secteurs et commerciaux pour les listes déroulantes du formulaire
        $datas['sectors'] = $this->companiesRepository->getSectors();
        $datas['salesmen'] = $this->companiesRepository->getSalesmen();
        
        // Ajout du function_id de l'utilisateur pour le template (nécessaire pour masquer les boutons pour les clients)
        $datas['user_function_id'] = $user['fk_function_id'] ?? null;
        
        $this->renderService->displayTemplates("ModifyCompany", $datas, "Modifier l'entreprise");
        exit;
    }

    /**
     * Prépare et affiche le formulaire d'ajout d'une nouvelle entreprise.
     * 
     * Récupère les secteurs et les commerciaux pour peupler les listes déroulantes
     * du formulaire. N'affiche pas de données d'entreprise car c'est une création.
     *
     * @return void
     */
    public function renderAddCompany(): void {
        // Récupération des informations utilisateur
        $user = $this->getCurrentUser();
        
        // Récupération des secteurs et commerciaux pour les listes déroulantes
        $datas = [];
        $datas['sectors'] = $this->companiesRepository->getSectors();
        $datas['salesmen'] = $this->companiesRepository->getSalesmen();
        // Ajout du function_id de l'utilisateur pour le template
        $datas['user_function_id'] = $user['fk_function_id'] ?? null;
        
        $this->renderService->displayTemplates("ModifyCompany", $datas, "Ajouter une entreprise");
        exit;
    }

    /**
     * Traite la soumission du formulaire de création d'entreprise.
     * 
     * Nettoie les données du formulaire, tente la création en base,
     * affiche un message d'erreur en cas d'échec, ou redirige vers la liste
     * des entreprises avec un message de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function createCompany(array $datas): void {
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        $companyData = [
            'company_name' => trim($datas['companyName'] ?? ''),
            'siret' => trim($datas['siret'] ?? ''),
            'siren' => trim($datas['siren'] ?? ''),
            'delivery_address' => trim($datas['deliveryAddress'] ?? ''),
            'sector' => $datas['sector'] ?? '',
            'salesman' => $datas['salesman'] ?? ''
        ];

        // Tentative de création dans la base de données
        $createStatus = $this->companiesRepository->addCompany($companyData);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$createStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la création de l\'entreprise.', 'error');
            $this->router->redirect('/Companies');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des entreprises
        $this->statusMessageService->setMessage('L\'entreprise a été créée avec succès.', 'success');
        $this->router->redirect('/Companies');
        exit;
    }

    /**
     * Traite la soumission du formulaire de modification d'entreprise.
     * 
     * Nettoie les données du formulaire, vérifie les permissions d'accès,
     * tente la mise à jour en base, affiche un message d'erreur en cas d'échec,
     * ou redirige vers la liste des entreprises avec un message de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function updateCompany(array $datas): void {
        // Récupération des informations utilisateur depuis la base de données
        $user = $this->getCurrentUser();
        
        $companyId = $datas['companyId'] ?? '';
        
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        // Évite les erreurs de données manquantes et normalise les chaînes de caractères
        // Pour les clients (function_id = 2), on ne traite pas le champ salesman (non envoyé dans le POST)
        $userFunctionId = $user['fk_function_id'] ?? null;
        $isClient = $userFunctionId == 2;
        
        $companyData = [
            'company_id' => $companyId,
            'company_name' => trim($datas['companyName'] ?? ''),
            'siret' => trim($datas['siret'] ?? ''),
            'siren' => trim($datas['siren'] ?? ''),
            'delivery_address' => trim($datas['deliveryAddress'] ?? ''),
            'sector' => $datas['sector'] ?? ''
        ];
        
        // Ajout du commercial seulement si ce n'est pas un client (les clients ne peuvent pas modifier leur commercial)
        if (!$isClient) {
            $companyData['salesman'] = $datas['salesman'] ?? '';
        }

        // Vérification des permissions avec le service de vérification
        if (!$this->permissionVerificationService->canAccessCompany($user, ['companyId' => $companyId])) {
            $this->statusMessageService->setMessage('Vous n\'êtes pas autorisé à modifier cette entreprise.', 'error');
            $this->router->redirect('/Companies');
            exit;
        }

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
        $this->router->redirect('/Companies');
        exit;
    }

    /**
     * Traite la suppression d'une entreprise.
     * 
     * Vérifie les permissions de l'utilisateur, tente la suppression en base,
     * affiche un message d'erreur en cas d'échec, ou redirige vers la liste
     * des entreprises avec un message de succès.
     *
     * @param int $companyId ID de l'entreprise à supprimer.
     * @return void
     */
    public function deleteCompany(int $companyId): void {
        // Récupération des informations utilisateur depuis la base de données
        $user = $this->getCurrentUser();

        // Vérification des permissions avec le service de vérification
        if (!$this->permissionVerificationService->canDeleteCompany($user, ['companyId' => $companyId])) {
            $this->statusMessageService->setMessage('Vous n\'êtes pas autorisé à supprimer cette entreprise.', 'error');
            $this->router->redirect('/Companies');
            exit;
        }

        // Tentative de suppression dans la base de données
        $deleteStatus = $this->companiesRepository->deleteCompany($companyId);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$deleteStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la suppression de l\'entreprise.', 'error');
            $this->router->redirect('/Companies');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des entreprises
        $this->statusMessageService->setMessage('L\'entreprise a été supprimée avec succès.', 'success');
        $this->router->redirect('/Companies');
        exit;
    }

}
