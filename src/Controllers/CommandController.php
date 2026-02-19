<?php

namespace App\Controllers;

use App\Repositories\CommandRepository;
use App\Repositories\UserRepository;
use App\Repositories\StockRepository;
use App\Repositories\CompaniesRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;
use App\Helpers\StatusMessageService;
use App\Helpers\UserService;
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
        $this->commandRepository = new CommandRepository();
        $this->userRepository = new UserRepository();
        $this->stockRepository = new StockRepository();
        $this->companiesRepository = new CompaniesRepository();
        $this->renderService = new RenderService();
        $this->authenticationService = new AuthenticationService();
        $this->statusMessageService = new StatusMessageService();
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

            // Action pour afficher le formulaire d'ajout d'une nouvelle commande
            if (isset($_POST['newCommand']) && isset($_POST['renderAddCommand'])) {
                $this->renderAddCommand();
                exit;
            }

            // Action pour afficher le formulaire de modification d'une commande
            if (isset($_POST['commandId']) && isset($_POST['renderModifyCommand'])) {
                $this->renderModifyCommand((int)$_POST['commandId']);
                exit;
            }

            // Action pour passer de l'étape produits à l'étape livraison
            if (isset($_POST['goToDelivery'])) {
                $this->renderDeliveryStep($_POST);
                exit;
            }

            // Action pour revenir de l'étape livraison à l'étape produits
            if (isset($_POST['backToProducts'])) {
                $this->renderProductStep($_POST);
                exit;
            }

            // Action pour la pagination uniquement (sans changer d'étape)
            if (isset($_POST['paginationOnly']) && isset($_POST['paginationPage'])) {
                $this->renderProductStep($_POST);
                exit;
            }

            // Action pour créer une nouvelle commande
            if (isset($_POST['newCommand']) && isset($_POST['createCommand'])) {
                $this->createCommand($_POST);
                exit;
            }

            // Action pour mettre à jour les données d'une commande
            if (isset($_POST['commandId']) && isset($_POST['updateCommand'])) {
                $this->updateCommand($_POST);
                exit;
            }

            // Action pour supprimer une commande
            if (isset($_POST['commandId']) && isset($_POST['deleteCommand'])) {
                $this->deleteCommand((int)$_POST['commandId']);
                exit;
            }

            // Action pour valider une commande (Commercial)
            if (isset($_POST['commandId']) && isset($_POST['validateCommand'])) {
                $this->validateCommand((int)$_POST['commandId']);
                exit;
            }

            // Action pour envoyer une commande (Logisticien)
            if (isset($_POST['commandId']) && isset($_POST['sendCommand'])) {
                $this->sendCommand((int)$_POST['commandId']);
                exit;
            }

        }

        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        $userId = $user['user_id'] ?? 0;

        // Récupération des commandes selon le rôle de l'utilisateur
        $datas = $this->commandRepository->getCommandsByUserRole($user);
        
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Aucune commande trouvée.', 'info');
        }

        // Ajout des informations utilisateur pour les templates
        $datas['currentUser'] = $user;

        // Affichage du template avec les données récupérées
        $this->renderService->displayTemplates("Commands", $datas);
        exit;
    }

    /**
     * Prépare et affiche le formulaire de modification d'une commande.
     * 
     * Récupère les données de la commande pour le formulaire de modification.
     * Vérifie les permissions avant d'autoriser la modification.
     *
     * @param int $commandId ID de la commande à modifier.
     * @return void
     */
    public function renderModifyCommand(int $commandId): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        // Vérification des permissions pour modifier la commande
        if (!$this->commandRepository->canUserPerformAction($user, $commandId, 'modify')) {
            $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour modifier cette commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }
        
        // Récupération des données de la commande
        $datas = $this->commandRepository->getCommandById($commandId);
        
        // Vérification que la commande existe
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Commande introuvable.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }
        
        // Récupération des statuts disponibles
        $statusList = $this->commandRepository->getAllStatuses();
        $datas['statusList'] = $statusList;
        
        // Récupération des produits en stock pour la sélection
        $allProducts = $this->stockRepository->getAllProducts();
        
        // Récupération des produits existants de la commande
        $existingCommandProducts = $datas['products'] ?? [];
        
        // Fusion des produits : ajouter les quantités commandées aux produits en stock
        $mergedProducts = [];
        foreach ($allProducts as $product) {
            $productId = $product['product_id'];
            $mergedProduct = $product;
            
            // Chercher si ce produit existe déjà dans la commande
            foreach ($existingCommandProducts as $existingProduct) {
                if ($existingProduct['product_id'] == $productId) {
                    // Ajouter la quantité commandée aux données du produit
                    $mergedProduct['ordered_quantity'] = $existingProduct['quantity'];
                    break;
                }
            }
            
            // Si le produit n'était pas dans la commande, quantité commandée = 0
            if (!isset($mergedProduct['ordered_quantity'])) {
                $mergedProduct['ordered_quantity'] = 0;
            }
            
            $mergedProducts[] = $mergedProduct;
        }
        
        $datas['products'] = $mergedProducts;
        $datas['currentUser'] = $user;
        $datas['step'] = 'products';
        
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
        
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        // Récupération de l'adresse de livraison de l'entreprise pour préremplir le champ
        $userCompanyId = $user['fk_company_id'] ?? null;
        if ($userCompanyId) {
            $company = $this->companiesRepository->getCompanyById($userCompanyId);
            if (!empty($company) && isset($company['delivery_address'])) {
                $datas['company_delivery_address'] = $company['delivery_address'];
            }
        }
        
        // Récupération des statuts disponibles pour les listes déroulantes
        $statusList = $this->commandRepository->getAllStatuses();
        $datas['statusList'] = $statusList ?? [];
        
        // Récupération des produits en stock pour la sélection
        $products = $this->stockRepository->getAllProducts();
        $datas['products'] = $products ?? [];
        
        $datas['currentUser'] = $user;
        $datas['step'] = 'products';
        
        $this->renderService->displayTemplates("ModifyCommands", $datas, "Créer une commande");
        exit;
    }

    /**
     * Prépare et affiche l'étape de sélection des produits.
     * 
     * Affiche la page de sélection des produits pour une nouvelle commande
     * ou pour modifier une commande existante.
     *
     * @param array $postData Données POST optionnelles (pour revenir en arrière).
     * @return void
     */
    public function renderProductStep(array $postData = []): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        $datas = [];
        
        // Si on est en mode modification, récupérer les données de la commande
        $commandId = $postData['commandId'] ?? null;
        if ($commandId) {
            $commandId = (int)$commandId;
            
            // Vérification des permissions
            if (!$this->commandRepository->canUserPerformAction($user, $commandId, 'modify')) {
                $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour modifier cette commande.', 'error');
                $this->router->redirect('/Commands');
                exit;
            }
            
            // Récupération des données de la commande
            $commandData = $this->commandRepository->getCommandById($commandId);
            if (empty($commandData)) {
                $this->statusMessageService->setMessage('Commande introuvable.', 'error');
                $this->router->redirect('/Commands');
                exit;
            }
            
            $datas = array_merge($datas, $commandData);
        }
        
        // Récupération de l'adresse de livraison de l'entreprise pour préremplir le champ (si nouvelle commande)
        if (!$commandId) {
            $userCompanyId = $user['fk_company_id'] ?? null;
            if ($userCompanyId) {
                $company = $this->companiesRepository->getCompanyById($userCompanyId);
                if (!empty($company) && isset($company['delivery_address'])) {
                    $datas['company_delivery_address'] = $company['delivery_address'];
                }
            }
        }
        
        // Récupération des produits en stock
        $allProducts = $this->stockRepository->getAllProducts();
        
        // Fusion avec les produits existants de la commande si en mode modification
        if ($commandId && isset($datas['products'])) {
            $existingCommandProducts = $datas['products'] ?? [];
            $mergedProducts = [];
            foreach ($allProducts as $product) {
                $productId = $product['product_id'];
                $mergedProduct = $product;
                
                foreach ($existingCommandProducts as $existingProduct) {
                    if ($existingProduct['product_id'] == $productId) {
                        $mergedProduct['ordered_quantity'] = $existingProduct['quantity'];
                        break;
                    }
                }
                
                if (!isset($mergedProduct['ordered_quantity'])) {
                    $mergedProduct['ordered_quantity'] = 0;
                }
                
                $mergedProducts[] = $mergedProduct;
            }
            
            $datas['products'] = $mergedProducts;
        } else {
            // Préparer les produits avec quantité 0 pour nouvelle commande
            foreach ($allProducts as &$product) {
                $product['ordered_quantity'] = 0;
            }
            $datas['products'] = $allProducts;
        }
        
        // Restaurer les quantités depuis POST si on revient en arrière
        if (isset($postData['products']) && is_array($postData['products'])) {
            foreach ($datas['products'] as &$product) {
                $productId = $product['product_id'];
                if (isset($postData['products'][$productId]['quantity'])) {
                    $product['ordered_quantity'] = (int)$postData['products'][$productId]['quantity'];
                }
            }
        }
        
        $datas['currentUser'] = $user;
        $datas['step'] = 'products';
        
        $pageTitle = $commandId ? "Modifier la commande" : "Créer une commande";
        $this->renderService->displayTemplates("ModifyCommands", $datas, $pageTitle);
        exit;
    }

    /**
     * Prépare et affiche l'étape de livraison avec récapitulatif.
     * 
     * Affiche la page de livraison avec le récapitulatif des produits sélectionnés
     * et les champs pour les informations de livraison.
     *
     * @param array $postData Données POST contenant les produits sélectionnés.
     * @return void
     */
    public function renderDeliveryStep(array $postData = []): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        $datas = [];
        
        // Récupération et traitement des produits sélectionnés
        $products = $postData['products'] ?? [];
        $selectedProducts = [];
        
        if (!empty($products) && is_array($products)) {
            foreach ($products as $productId => $productData) {
                $quantity = (int)($productData['quantity'] ?? 0);
                if ($quantity > 0) {
                    $selectedProducts[$productId] = ['quantity' => $quantity];
                }
            }
        }
        
        // Vérification qu'au moins un produit est sélectionné
        if (empty($selectedProducts)) {
            $this->statusMessageService->setMessage('Veuillez sélectionner au moins un produit.', 'error');
            $this->renderProductStep($postData);
            exit;
        }
        
        $datas['selectedProducts'] = $selectedProducts;
        
        // Si on est en mode modification, récupérer les données de la commande
        $commandId = $postData['commandId'] ?? null;
        if ($commandId) {
            $commandId = (int)$commandId;
            
            // Vérification des permissions
            if (!$this->commandRepository->canUserPerformAction($user, $commandId, 'modify')) {
                $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour modifier cette commande.', 'error');
                $this->router->redirect('/Commands');
                exit;
            }
            
            // Récupération des données de la commande
            $commandData = $this->commandRepository->getCommandById($commandId);
            if (empty($commandData)) {
                $this->statusMessageService->setMessage('Commande introuvable.', 'error');
                $this->router->redirect('/Commands');
                exit;
            }
            
            $datas = array_merge($datas, $commandData);
        }
        
        // Récupération de tous les produits avec leurs informations (prix, nom)
        $allProducts = $this->stockRepository->getAllProducts();
        $datas['products'] = $allProducts;
        
        // Récupération des statuts disponibles
        $statusList = $this->commandRepository->getAllStatuses();
        $datas['statusList'] = $statusList ?? [];
        
        $datas['currentUser'] = $user;
        $datas['step'] = 'delivery';
        
        $pageTitle = $commandId ? "Modifier la commande" : "Créer une commande";
        $this->renderService->displayTemplates("ModifyCommands", $datas, $pageTitle);
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
            'fk_status_id' => 3 // Par défaut, nouvelle commande = "en attente"
        ];

        // Récupération et validation de l'adresse de livraison
        $deliveryAddressData = $datas['deliveryAddress'] ?? [];
        if (!empty($deliveryAddressData) && is_array($deliveryAddressData)) {
            $commandData['delivery_address_data'] = [
                'street' => trim($deliveryAddressData['street'] ?? ''),
                'city' => trim($deliveryAddressData['city'] ?? ''),
                'postal_code' => trim($deliveryAddressData['postal_code'] ?? ''),
                'country' => trim($deliveryAddressData['country'] ?? 'France'),
                'additional_info' => trim($deliveryAddressData['additional_info'] ?? '')
            ];
            
            // Validation des champs obligatoires de l'adresse
            if (empty($commandData['delivery_address_data']['street']) || 
                empty($commandData['delivery_address_data']['city']) || 
                empty($commandData['delivery_address_data']['postal_code'])) {
                $this->statusMessageService->setMessage('Veuillez remplir tous les champs obligatoires de l\'adresse de livraison.', 'error');
                $this->renderAddCommand();
                exit;
            }
        }

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
            $this->router->redirect('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('La commande a été créée avec succès.', 'success');
        $this->router->redirect('/Commands');
        exit;
    }

    /**
     * Traite la soumission du formulaire de modification de commande.
     * 
     * Nettoie les données du formulaire, tente la mise à jour en base, affiche un message
     * d'erreur en cas d'échec, ou redirige vers la liste des commandes avec un message
     * de succès. Vérifie les permissions avant la mise à jour.
     * Les commerciaux ne peuvent pas modifier le statut de la commande.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function updateCommand(array $datas): void {
        $commandId = $datas['commandId'] ?? '';
        
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        $userFunctionId = $user['fk_function_id'] ?? null;
        
        // Vérification des permissions pour modifier la commande
        if (!$this->commandRepository->canUserPerformAction($user, (int)$commandId, 'modify')) {
            $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour modifier cette commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }
        
        // Récupération du statut : préserver le statut existant pour tous les utilisateurs
        // Les modifications de statut se font via la page globale des commandes
        $currentCommand = $this->commandRepository->getCommandById((int)$commandId);
        $statusId = 1; // Valeur par défaut
        if (!empty($currentCommand) && isset($currentCommand['fk_status_id'])) {
            $statusId = (int)$currentCommand['fk_status_id'];
        }
        
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        $commandData = [
            'command_id' => $commandId,
            'delivery_date' => trim($datas['deliveryDate'] ?? ''),
            'fk_status_id' => $statusId
        ];

        // Récupération et validation de l'adresse de livraison
        $deliveryAddressData = $datas['deliveryAddress'] ?? [];
        if (!empty($deliveryAddressData) && is_array($deliveryAddressData)) {
            $commandData['delivery_address_data'] = [
                'street' => trim($deliveryAddressData['street'] ?? ''),
                'city' => trim($deliveryAddressData['city'] ?? ''),
                'postal_code' => trim($deliveryAddressData['postal_code'] ?? ''),
                'country' => trim($deliveryAddressData['country'] ?? 'France'),
                'additional_info' => trim($deliveryAddressData['additional_info'] ?? '')
            ];
            
            // Validation des champs obligatoires de l'adresse
            if (empty($commandData['delivery_address_data']['street']) || 
                empty($commandData['delivery_address_data']['city']) || 
                empty($commandData['delivery_address_data']['postal_code'])) {
                $this->statusMessageService->setMessage('Veuillez remplir tous les champs obligatoires de l\'adresse de livraison.', 'error');
                $this->router->redirect('/Commands');
                exit;
            }
        }

        // Récupération et traitement des données des produits
        $products = $datas['products'] ?? [];
        
        // Filtrer les produits avec une quantité > 0
        $filteredProducts = [];
        foreach ($products as $productId => $productData) {
            $quantity = (int)($productData['quantity'] ?? 0);
            if ($quantity > 0) {
                $filteredProducts[$productId] = ['quantity' => $quantity];
            }
        }

        // Tentative de mise à jour dans la base de données
        $updateStatus = $this->commandRepository->updateCommand($commandData, $filteredProducts);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la mise à jour.', 'error');
            $this->router->redirect('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('Les informations de la commande ont été mises à jour avec succès.', 'success');
        $this->router->redirect('/Commands');
        exit;
    }

    /**
     * Traite la suppression d'une commande.
     * 
     * Tente la suppression en base, affiche un message d'erreur en cas d'échec,
     * ou redirige vers la liste des commandes avec un message de succès.
     * Vérifie les permissions avant la suppression.
     *
     * @param int $commandId ID de la commande à supprimer.
     * @return void
     */
    public function deleteCommand(int $commandId): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        // Vérification des permissions pour supprimer la commande
        if (!$this->commandRepository->canUserPerformAction($user, $commandId, 'delete')) {
            $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour supprimer cette commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }
        
        // Tentative de suppression dans la base de données
        $deleteStatus = $this->commandRepository->deleteCommand($commandId);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$deleteStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la suppression de la commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('La commande a été supprimée avec succès.', 'success');
        $this->router->redirect('/Commands');
        exit;
    }

    /**
     * Traite la validation d'une commande par un commercial.
     * 
     * Change le statut de la commande de "en attente" (3) à "validé" (1).
     * Vérifie les permissions avant la validation.
     *
     * @param int $commandId ID de la commande à valider.
     * @return void
     */
    public function validateCommand(int $commandId): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        // Vérification des permissions pour valider la commande
        if (!$this->commandRepository->canUserPerformAction($user, $commandId, 'validate')) {
            $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour valider cette commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }
        
        // Tentative de mise à jour du statut dans la base de données
        $updateStatus = $this->commandRepository->updateCommandStatus($commandId, 'validate');
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la validation de la commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des commandes
        $this->statusMessageService->setMessage('La commande a été validée avec succès.', 'success');
        $this->router->redirect('/Commands');
        exit;
    }

    /**
     * Traite l'envoi d'une commande par un logisticien.
     * 
     * Change le statut de la commande de "validé" (1) à "envoyé" (2).
     * Vérifie les permissions avant l'envoi.
     *
     * @param int $commandId ID de la commande à envoyer.
     * @return void
     */
    public function sendCommand(int $commandId): void {
        // Récupération de l'utilisateur actuel
        $user = $this->getCurrentUser();
        
        if (!$this->commandRepository->canUserPerformAction($user, $commandId, 'send')) {
            $this->statusMessageService->setMessage('Vous n\'avez pas les permissions pour envoyer cette commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }

        $stockResult = $this->commandRepository->updateStockQty($commandId);
        if (!$stockResult['success']) {
            if (!empty($stockResult['insufficient_products'])) {
                $msg = 'Stock insuffisant pour : ' . implode(', ', $stockResult['insufficient_products']);
            } 
            else {
                $msg = 'Une erreur est survenue lors de la mise à jour du stock.';
            }

            $this->statusMessageService->setMessage($msg, 'error');
            $this->router->redirect('/Commands');
            exit;
        }

        $updateStatus = $this->commandRepository->updateCommandStatus($commandId, 'send');
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de l\'envoi de la commande.', 'error');
            $this->router->redirect('/Commands');
            exit;
        }

        $this->statusMessageService->setMessage('La commande a été envoyée avec succès.', 'success');
        $this->router->redirect('/Commands');
        exit;
    }

}

