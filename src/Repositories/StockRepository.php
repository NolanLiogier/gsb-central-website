<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;

/**
 * Classe StockRepository
 * 
 * Repository pour l'accès et la manipulation des données du stock.
 * Fournit des méthodes pour récupérer, mettre à jour et gérer les produits
 * en stock depuis la base de données.
 */
class StockRepository {
    /**
     * Connexion PDO à la base de données.
     * Réutilisée pour toutes les opérations de ce repository.
     * 
     * @var PDO
     */
    private PDO $connection;

    /**
     * Initialise le repository en établissant la connexion à la base de données.
     * La connexion est établie via la classe Database qui centralise la configuration.
     * 
     * @return void
     */
    public function __construct() {
        $database = new Database();
        $this->connection = $database->getConnection();
    }

    /**
     * Récupère tous les produits en stock.
     * 
     * Récupère toutes les informations des produits disponibles en stock,
     * triés par nom de produit pour faciliter la lecture.
     *
     * @return array Liste des produits avec leurs informations (id_product, product_name, quantity, price).
     */
    public function getAllProducts(): array {
        try {
            // Vérification de la connexion avant la requête
            if (!$this->connection) {
                return [];
            }

            // Récupération de tous les produits triés par nom pour faciliter la navigation
            $query = "SELECT id_product, product_name, quantity, price 
                      FROM stock 
                      ORDER BY product_name";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

    /**
     * Récupère les données d'un produit spécifique par son ID.
     * 
     * Récupère toutes les informations nécessaires pour l'édition d'un produit.
     *
     * @param int $productId ID du produit à récupérer.
     * @return array Données du produit avec id_product, product_name, quantity, price, ou tableau vide.
     */
    public function getProductById(int $productId): array {
        try {
            // Vérification de la connexion avant la requête
            if (!$this->connection) {
                return [];
            }

            $query = "SELECT id_product, product_name, quantity, price 
                      FROM stock 
                      WHERE id_product = :product_id";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            $stmt->execute();
            
            // fetch() retourne false si aucun résultat, convertir en tableau vide
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [];
            
        } catch (PDOException $e) {
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

    /**
     * Met à jour les informations d'un produit dans la base de données.
     * 
     * Valide les données du produit, vérifie la présence des champs requis,
     * et met à jour toutes les informations du produit. La quantité doit
     * être positive et le prix doit être valide.
     *
     * @param array $productData Données du produit à mettre à jour (id_product, product_name, quantity, price).
     * @return bool True si la mise à jour a réussi, false en cas d'erreur ou de données invalides.
     */
    public function updateProduct(array $productData): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Validation de la présence des champs obligatoires
            if (empty($productData['id_product']) || empty($productData['product_name']) || 
                empty($productData['quantity']) || empty($productData['price'])) {
                return false;
            }

            // Validation de la quantité : doit être positive ou nulle
            if (!is_numeric($productData['quantity']) || $productData['quantity'] < 0) {
                return false;
            }

            // Validation du prix : doit être positif
            if (!is_numeric($productData['price']) || $productData['price'] <= 0) {
                return false;
            }

            // Préparation de la requête UPDATE avec paramètres nommés pour éviter les injections SQL
            $query = "UPDATE stock 
                      SET product_name = :product_name, 
                          quantity = :quantity, 
                          price = :price
                      WHERE id_product = :product_id";
            
            $stmt = $this->connection->prepare($query);
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':product_id', $productData['id_product'], PDO::PARAM_INT);
            $stmt->bindParam(':product_name', $productData['product_name'], PDO::PARAM_STR);
            $stmt->bindParam(':quantity', $productData['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':price', $productData['price'], PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // En cas d'erreur (contrainte DB, connexion perdue, etc.), retourner false
            return false;
        }
    }

    /**
     * Crée un nouveau produit dans la base de données.
     * 
     * Valide les données du produit, vérifie la présence des champs requis,
     * et insère une nouvelle entrée dans la table stock.
     *
     * @param array $productData Données du produit à créer (product_name, quantity, price).
     * @return bool True si l'insertion a réussi, false en cas d'erreur ou de données invalides.
     */
    public function addProduct(array $productData): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Validation de la présence des champs obligatoires (sans id_product car c'est une création)
            if (empty($productData['product_name']) || empty($productData['quantity']) || 
                empty($productData['price'])) {
                return false;
            }

            // Validation de la quantité : doit être positive ou nulle
            if (!is_numeric($productData['quantity']) || $productData['quantity'] < 0) {
                return false;
            }

            // Validation du prix : doit être positif
            if (!is_numeric($productData['price']) || $productData['price'] <= 0) {
                return false;
            }

            // Préparation de la requête INSERT avec paramètres nommés pour éviter les injections SQL
            $query = "INSERT INTO stock (product_name, quantity, price) 
                      VALUES (:product_name, :quantity, :price)";
            
            $stmt = $this->connection->prepare($query);
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':product_name', $productData['product_name'], PDO::PARAM_STR);
            $stmt->bindParam(':quantity', $productData['quantity'], PDO::PARAM_INT);
            $stmt->bindParam(':price', $productData['price'], PDO::PARAM_STR);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // En cas d'erreur (contrainte DB, connexion perdue, etc.), retourner false
            return false;
        }
    }

    /**
     * Supprime un produit de la base de données.
     * 
     * Supprime l'enregistrement du produit spécifié par son ID.
     * La suppression peut échouer si le produit a des relations avec d'autres tables
     * ou si la connexion à la base de données est perdue.
     *
     * @param int $productId ID du produit à supprimer.
     * @return bool True si la suppression a réussi, false en cas d'erreur.
     */
    public function deleteProduct(int $productId): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Préparation de la requête DELETE avec paramètre nommé pour éviter les injections SQL
            $query = "DELETE FROM stock WHERE id_product = :product_id";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // En cas d'erreur (contrainte DB, connexion perdue, etc.), retourner false
            return false;
        }
    }
}

