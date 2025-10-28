<?php

namespace App\Controllers;

use App\Repositories\CommandRepository;
use App\Repositories\UserRepository;
use App\Repositories\StockRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;
use App\Helpers\StatusMessageService;
use Routing\Router;

/**
 * Classe CommandController
 * Gère l'affichage et la gestion de la page des commandes.
 */
class CommandController {
    /**
     * Repository pour l'accès aux données des commandes.
     * 
     * @var CommandRepository
     */
    private CommandRepository $commandRepository;
    
    /**
     * Repository pour l'accès aux données des utilisateurs.
     * 
     * @var UserRepository
     */
    private UserRepository $userRepository;
    
    /**
     * Repository pour l'accès aux données du stock.
     * 
     * @var StockRepository
     */
    private StockRepository $stockRepository;
    
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
        $this->commandRepository = new CommandRepository();
        $this->userRepository = new UserRepository();
        $this->stockRepository = new StockRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
        $this->statusMessageService = new StatusMessageService();
        $this->router = new Router();
    }

    /**
     * Récupère les informations complètes de l'utilisateur connecté depuis la base de données.
     * Utilise l'email de la session pour récupérer les informations utilisateur.
     *
     * @return array Informations utilisateur (user_id, email, fk_company_id, fk_function_id, etc.)
     */
    private function getCurrentUser(): array {
        $userEmail = $_SESSION['user_email'] ?? '';
        
        if (empty($userEmail)) {
            $this->router->getRoute('/Login');
            exit;
        }
        
        return $this->userRepository->getUserByEmail($userEmail);
    }

    /**
     * Point d'entrée principal du contrôleur des commandes.
     * 
     * Vérifie l'authentification, route les requêtes POST vers les méthodes appropriées
     * (affichage du formulaire de modification ou mise à jour), et affiche la liste
     * des commandes pour les requêtes GET.
     *
     * @return void
     */
    public function index(): void {
        // Vérification obligatoire de l'authentification avant tout traitement
        $this->authenticationService->verifyAuthentication();

        // Gestion des actions POST spécifiques (affichage formulaire ou mise à jour)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Action pour afficher le formulaire de modification d'une commande
            if (isset($_POST['commandId']) && isset($_POST['renderModifyCommand'])) {
                $this->renderModifyCommand((int)$_POST['commandId']);
                exit;
            }

            // Action pour mettre à jour les données d'une commande
            if (isset($_POST['commandId']) && isset($_POST['updateCommand'])) {
                $this->updateCommand($_POST);
                exit;
            }

            // Action pour afficher le formulaire d'ajout d'une nouvelle commande
            if (isset($_POST['newCommand']) && isset($_POST['renderAddCommand'])) {
                $this->renderAddCommand();
                exit;
            }

            // Action pour créer une nouvelle commande
            if (isset($_POST['newCommand']) && isset($_POST['createCommand'])) {
                $this->createCommand($_POST);
                exit;
            }

            // Action pour supprimer une commande
            if (isset($_POST['commandId']) && isset($_POST['deleteCommand'])) {
                $this->deleteCommand((int)$_POST['commandId']);
                exit;
            }
        }

        // Gestion des routes GET spécifiques
        $currentRoute = $_SERVER['REQUEST_URI'] ?? '/';
        if ($currentRoute === '/ModifyCommand') {
            $this->renderAddCommand();
            exit;
        }

        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        $userId = $user['user_id'] ?? 0;

        // Récupération des commandes de l'utilisateur
        $datas = $this->commandRepository->getCommandsByUserId($userId);
        
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Aucune commande trouvée.', 'info');
        }

        // Affichage du template avec les données récupérées
        $this->renderService->displayTemplates("Commands", $datas);
        exit;
    }

    /**
     * Prépare et affiche le formulaire de modification d'une commande.
     * 
     * Récupère les données de la commande pour le formulaire de modification.
     *
     * @param int $commandId ID de la commande à modifier.
     * @return void
     */
    public function renderModifyCommand(int $commandId): void {
        // Récupération des données de la commande
        $datas = $this->commandRepository->getCommandById($commandId);
        
        // Vérification que la commande existe
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Commande introuvable.', 'error');
            $this->router->getRoute('/Commands');
            exit;
        }
        
        // Récupération des statuts disponibles
        $statusList = $this->commandRepository->getAllStatuses();
        $datas['statusList'] = $statusList;
        
        // Récupération des produits en stock pour la sélection
        $products = $this->stockRepository->getAllProducts();
        $datas['products'] = $products;
        
        $this->renderService->displayTemplates("ModifyCommands", $datas, "Modifier la commande");
        exit;
    }

    /**
     * Prépare et affiche le formulaire d'ajout d'une nouvelle commande.
     * 
     * Récupère les statuts disponibles pour peupler les listes déroulantes
     * du formulaire. N'affiche pas de données de commande car c'est une création.
     *
     * @return void
     */
    public function renderAddCommand(): void {
        $datas = [];
        
        // Récupération des statuts disponibles pour les listes déroulantes
        $statusList = $this->commandRepository->getAllStatuses();
        $datas['statusList'] = $statusList ?? [];
        
        // Récupération des produits en stock pour la sélection
        $products = $this->stockRepository->getAllProducts();
        $datas['products'] = $products ?? [];
        
        $this->renderService->displayTemplates("ModifyCommands", $datas, "Créer une commande");
        exit;
    }

    /**
     * Traite la soumission du formulaire de création de commande.
     * 
     * Nettoie les données du formulaire, tente la création en base,
     * affiche un message d'erreur en cas d'échec, ou redirige vers la liste
     * des commandes avec un message de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function createCommand(array $datas): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        $userId = $user['user_id'] ?? 0;
        
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        $commandData = [
            'user_id' => $userId,
            'delivery_date' => trim($datas['deliveryDate'] ?? ''),
            'fk_status_id' => (int)($datas['statusId'] ?? 1)
        ];

        // Vérification qu'au moins un produit est sélectionné
        $products = $datas['products'] ?? [];
        
        // Parse les produits sélectionnés - format: products[productId][quantity]
        $selectedProducts = [];
        if (!empty($products) && is_array($products)) {
            foreach ($products as $productId => $productData) {
                if (isset($productData['quantity']) && !empty($productData['quantity']) && (int)$productData['quantity'] > 0) {
                    $selectedProducts[$productId] = [
                        'quantity' => (int)$productData['quantity']
                    ];
                }
            }
        }
        
        // Vérification qu'au moins un produit est sélectionné
        if (empty($selectedProducts)) {
            $this->statusMessageService->setMessage('Veuillez sélectionner au moins un produit.', 'error');
            $this->renderAddCommand();
            exit;
        }
        
        // Tentative de création dans la base de données
        $createStatus = $this->commandRepository->addCommand($commandData, $selectedProducts);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$createStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la création de la commande.', 'error');
            $this->router->getRoute('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('La commande a été créée avec succès.', 'success');
        $this->router->getRoute('/Commands');
        exit;
    }

    /**
     * Traite la soumission du formulaire de modification de commande.
     * 
     * Nettoie les données du formulaire, tente la mise à jour en base, affiche un message
     * d'erreur en cas d'échec, ou redirige vers la liste des commandes avec un message
     * de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function updateCommand(array $datas): void {
        $commandId = $datas['commandId'] ?? '';
        
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        $commandData = [
            'command_id' => $commandId,
            'delivery_date' => trim($datas['deliveryDate'] ?? ''),
            'fk_status_id' => (int)($datas['statusId'] ?? 1)
        ];

        // Tentative de mise à jour dans la base de données
        $updateStatus = $this->commandRepository->updateCommand($commandData);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la mise à jour.', 'error');
            $this->router->getRoute('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('Les informations de la commande ont été mises à jour avec succès.', 'success');
        $this->router->getRoute('/Commands');
        exit;
    }

    /**
     * Traite la suppression d'une commande.
     * 
     * Tente la suppression en base, affiche un message d'erreur en cas d'échec,
     * ou redirige vers la liste des commandes avec un message de succès.
     *
     * @param int $commandId ID de la commande à supprimer.
     * @return void
     */
    public function deleteCommand(int $commandId): void {
        // Tentative de suppression dans la base de données
        $deleteStatus = $this->commandRepository->deleteCommand($commandId);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$deleteStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la suppression de la commande.', 'error');
            $this->router->getRoute('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('La commande a été supprimée avec succès.', 'success');
        $this->router->getRoute('/Commands');
        exit;
    }
}

