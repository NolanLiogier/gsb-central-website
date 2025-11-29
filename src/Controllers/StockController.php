<?php

namespace App\Controllers;

use App\Repositories\StockRepository;
use App\Repositories\UserRepository;
use App\Helpers\RenderService;
use App\Helpers\AuthenticationService;
use App\Helpers\StatusMessageService;
use App\Helpers\UserService;
use Routing\Router;

/**
 * Classe StockController
 * Gère l'affichage et la gestion de la page du stock.
 */
class StockController {
    /**
     * Repository pour l'accès aux données du stock.
     * 
     * @var StockRepository
     */
    private StockRepository $stockRepository;
    
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
        $this->stockRepository = new StockRepository();
        $this->userRepository = new UserRepository();
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
     * Point d'entrée principal du contrôleur du stock.
     * 
     * Vérifie l'authentification, route les requêtes POST vers les méthodes appropriées
     * (affichage du formulaire de modification ou mise à jour), et affiche la liste
     * des produits en stock pour les requêtes GET.
     *
     * @return void
     */
    public function index(): void {
        // Vérification obligatoire de l'authentification avant tout traitement
        $this->authenticationService->verifyAuthentication();

        // Gestion des actions POST spécifiques (affichage formulaire ou mise à jour)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Action pour afficher le formulaire de modification d'un produit
            if (isset($_POST['productId']) && isset($_POST['renderModifyProduct'])) {
                $this->renderModifyProduct((int)$_POST['productId']);
                exit;
            }

            // Action pour mettre à jour les données d'un produit
            if (isset($_POST['productId']) && isset($_POST['updateProduct'])) {
                $this->updateProduct($_POST);
                exit;
            }

            // Action pour afficher le formulaire d'ajout d'un nouveau produit
            if (isset($_POST['newProduct']) && isset($_POST['renderAddProduct'])) {
                $this->renderAddProduct();
                exit;
            }

            // Action pour créer un nouveau produit
            if (isset($_POST['newProduct']) && isset($_POST['createProduct'])) {
                $this->createProduct($_POST);
                exit;
            }

            // Action pour supprimer un produit
            if (isset($_POST['productId']) && isset($_POST['deleteProduct'])) {
                $this->deleteProduct((int)$_POST['productId']);
                exit;
            }
        }

        // Récupération des produits en stock pour l'affichage de la liste
        $datas = $this->stockRepository->getAllProducts();
        
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Aucun produit en stock.', 'info');
        }

        // Affichage du template avec les données récupérées
        $this->renderService->displayTemplates("Stock", $datas);
        exit;
    }

    /**
     * Prépare et affiche le formulaire de modification d'un produit.
     * 
     * Récupère les données du produit pour le formulaire de modification.
     *
     * @param int $productId ID du produit à modifier.
     * @return void
     */
    public function renderModifyProduct(int $productId): void {
        // Récupération des données du produit
        $datas = $this->stockRepository->getProductById($productId);
        
        // Vérification que le produit existe
        if (empty($datas)) {
            $this->statusMessageService->setMessage('Produit introuvable.', 'error');
            $this->router->redirect('/Stock');
            exit;
        }
        
        $this->renderService->displayTemplates("ModifyStock", $datas, "Modifier le produit");
        exit;
    }

    /**
     * Prépare et affiche le formulaire d'ajout d'un nouveau produit.
     * 
     * N'affiche pas de données car c'est une création.
     *
     * @return void
     */
    public function renderAddProduct(): void {
        // Pas de données à récupérer, c'est une création
        $datas = [];
        
        $this->renderService->displayTemplates("ModifyStock", $datas, "Ajouter un produit");
        exit;
    }

    /**
     * Traite la soumission du formulaire de création de produit.
     * 
     * Nettoie les données du formulaire, tente la création en base,
     * affiche un message d'erreur en cas d'échec, ou redirige vers la liste
     * des produits avec un message de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function createProduct(array $datas): void {
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        $productData = [
            'product_name' => trim($datas['productName'] ?? ''),
            'quantity' => (int)($datas['quantity'] ?? 0),
            'price' => (float)($datas['price'] ?? 0)
        ];

        // Tentative de création dans la base de données
        $createStatus = $this->stockRepository->addProduct($productData);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$createStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la création du produit.', 'error');
            $this->router->redirect('/Stock');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des produits
        $this->statusMessageService->setMessage('Le produit a été créé avec succès.', 'success');
        $this->router->redirect('/Stock');
        exit;
    }

    /**
     * Traite la soumission du formulaire de modification de produit.
     * 
     * Nettoie les données du formulaire, tente la mise à jour en base, affiche un message
     * d'erreur en cas d'échec, ou redirige vers la liste des produits avec un message
     * de succès.
     *
     * @param array $datas Données brutes du formulaire POST.
     * @return void
     */
    public function updateProduct(array $datas): void {
        $productId = $datas['productId'] ?? '';
        
        // Normalisation des données : trim pour supprimer les espaces et gestion des valeurs par défaut
        $productData = [
            'product_id' => $productId,
            'product_name' => trim($datas['productName'] ?? ''),
            'quantity' => (int)($datas['quantity'] ?? 0),
            'price' => (float)($datas['price'] ?? 0)
        ];

        // Tentative de mise à jour dans la base de données
        $updateStatus = $this->stockRepository->updateProduct($productData);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$updateStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la mise à jour.', 'error');
            $this->renderService->displayTemplates("Stock", $productData, "Stock");
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des produits
        $this->statusMessageService->setMessage('Les informations du produit ont été mises à jour avec succès.', 'success');
        $this->router->redirect('/Stock');
        exit;
    }

    /**
     * Traite la suppression d'un produit.
     * 
     * Tente la suppression en base, affiche un message d'erreur en cas d'échec,
     * ou redirige vers la liste des produits avec un message de succès.
     *
     * @param int $productId ID du produit à supprimer.
     * @return void
     */
    public function deleteProduct(int $productId): void {
        // Tentative de suppression dans la base de données
        $deleteStatus = $this->stockRepository->deleteProduct($productId);
        
        // Gestion de l'échec : affichage d'un message d'erreur
        if (!$deleteStatus) {
            $this->statusMessageService->setMessage('Une erreur est survenue lors de la suppression du produit.', 'error');
            $this->router->redirect('/Stock');
            exit;
        } 

        // Succès : message de confirmation et redirection vers la liste des produits
        $this->statusMessageService->setMessage('Le produit a été supprimé avec succès.', 'success');
        $this->router->redirect('/Stock');
        exit;
    }
}

