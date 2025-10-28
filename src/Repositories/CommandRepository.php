<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;

/**
 * Classe CommandRepository
 * 
 * Repository pour l'accès et la manipulation des données des commandes.
 * Fournit des méthodes pour récupérer les commandes des utilisateurs depuis la base de données.
 */
class CommandRepository {
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
     * Récupère tous les statuts disponibles.
     * 
     * Récupère tous les statuts depuis la table status pour l'affichage dans les formulaires.
     *
     * @return array Liste des statuts avec status_id et status_name.
     */
    public function getAllStatuses(): array {
        try {
            // Vérification de la connexion avant la requête
            if (!$this->connection) {
                return [];
            }

            $query = "SELECT status_id, status_name FROM status ORDER BY status_id";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère toutes les commandes d'un utilisateur spécifique.
     * 
     * Récupère toutes les informations des commandes d'un utilisateur,
     * triées par date de création décroissante (les plus récentes en premier).
     * Inclut également les détails des produits commandés.
     *
     * @param int $userId ID de l'utilisateur dont on souhaite récupérer les commandes.
     * @return array Liste des commandes avec leurs informations et produits.
     */
    public function getCommandsByUserId(int $userId): array {
        try {
            // Vérification de la connexion avant la requête
            if (!$this->connection) {
                return [];
            }

            // Récupération de toutes les commandes de l'utilisateur triées par date
            $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name
                      FROM commands c
                      LEFT JOIN status s ON c.fk_status_id = s.status_id
                      WHERE c.fk_user_id = :user_id
                      ORDER BY c.created_at DESC";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque commande, récupérer les détails des produits
            foreach ($commands as &$command) {
                $command['products'] = $this->getCommandProducts($command['command_id']);
            }
            
            return $commands;
            
        } catch (PDOException $e) {
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

    /**
     * Récupère les produits d'une commande spécifique.
     * 
     * @param int $commandId ID de la commande.
     * @return array Liste des produits avec leurs quantités.
     */
    private function getCommandProducts(int $commandId): array {
        try {
            $query = "SELECT st.product_id, st.product_name, st.price, COUNT(cd.details_id) as quantity
                      FROM command_details cd
                      JOIN stock st ON cd.fk_product_id = st.product_id
                      WHERE cd.fk_command_id = :command_id
                      GROUP BY st.product_id, st.product_name, st.price";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère les données d'une commande spécifique par son ID.
     * 
     * Récupère toutes les informations nécessaires pour l'édition d'une commande,
     * incluant les produits commandés.
     *
     * @param int $commandId ID de la commande à récupérer.
     * @return array Données de la commande avec command_id, delivery_date, created_at, fk_status_id, fk_user_id, products, ou tableau vide.
     */
    public function getCommandById(int $commandId): array {
        try {
            // Vérification de la connexion avant la requête
            if (!$this->connection) {
                return [];
            }

            $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, c.fk_user_id, s.status_name
                      FROM commands c
                      LEFT JOIN status s ON c.fk_status_id = s.status_id
                      WHERE c.command_id = :command_id";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            $stmt->execute();
            
            // fetch() retourne false si aucun résultat, convertir en tableau vide
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Ajouter les produits de la commande
                $result['products'] = $this->getCommandProducts($commandId);
            }
            
            return $result ?: [];
            
        } catch (PDOException $e) {
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

    /**
     * Met à jour les informations d'une commande dans la base de données.
     * 
     * Valide les données de la commande, vérifie la présence des champs requis,
     * et met à jour toutes les informations de la commande incluant les produits.
     *
     * @param array $commandData Données de la commande à mettre à jour (command_id, delivery_date, fk_status_id).
     * @param array $products Données des produits sélectionnés avec leurs quantités.
     * @return bool True si la mise à jour a réussi, false en cas d'erreur ou de données invalides.
     */
    public function updateCommand(array $commandData, array $products = []): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Validation de la présence des champs obligatoires
            if (empty($commandData['command_id']) || empty($commandData['delivery_date']) || 
                empty($commandData['fk_status_id'])) {
                return false;
            }

            // Début de la transaction pour assurer la cohérence des données
            $this->connection->beginTransaction();

            // Préparation de la requête UPDATE avec paramètres nommés pour éviter les injections SQL
            $query = "UPDATE commands 
                      SET delivery_date = :delivery_date, 
                          fk_status_id = :fk_status_id
                      WHERE command_id = :command_id";
            
            $stmt = $this->connection->prepare($query);
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':command_id', $commandData['command_id'], PDO::PARAM_INT);
            $stmt->bindParam(':delivery_date', $commandData['delivery_date'], PDO::PARAM_STR);
            $stmt->bindParam(':fk_status_id', $commandData['fk_status_id'], PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->connection->rollBack();
                return false;
            }

            // Mise à jour des détails des produits si fournis
            if (!empty($products)) {
                // Supprimer les anciens détails de la commande
                $deleteQuery = "DELETE FROM command_details WHERE fk_command_id = :command_id";
                $deleteStmt = $this->connection->prepare($deleteQuery);
                $deleteStmt->bindParam(':command_id', $commandData['command_id'], PDO::PARAM_INT);
                
                if (!$deleteStmt->execute()) {
                    $this->connection->rollBack();
                    return false;
                }

                // Insérer les nouveaux détails des produits
                $detailsQuery = "INSERT INTO command_details (fk_command_id, fk_product_id, created_at) 
                                VALUES (:command_id, :product_id, NOW())";
                $detailsStmt = $this->connection->prepare($detailsQuery);

                foreach ($products as $productId => $productData) {
                    $quantity = (int)($productData['quantity'] ?? 0);
                    
                    // Insérer une ligne pour chaque quantité du produit
                    for ($i = 0; $i < $quantity; $i++) {
                        $detailsStmt->bindParam(':command_id', $commandData['command_id'], PDO::PARAM_INT);
                        $detailsStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                        
                        if (!$detailsStmt->execute()) {
                            $this->connection->rollBack();
                            return false;
                        }
                    }
                }
            }

            // Validation de la transaction
            $this->connection->commit();
            return true;
            
        } catch (PDOException $e) {
            // En cas d'erreur, annulation de la transaction
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            return false;
        }
    }

    /**
     * Crée une nouvelle commande dans la base de données.
     * 
     * Valide les données de la commande, vérifie la présence des champs requis,
     * et insère une nouvelle commande avec la date de création automatique.
     * Insère également les détails des produits dans la table command_details.
     *
     * @param array $commandData Données de la commande à créer (user_id, delivery_date, fk_status_id).
     * @param array $products Données des produits sélectionnés avec leurs quantités.
     * @return bool True si la création a réussi, false en cas d'erreur ou de données invalides.
     */
    public function addCommand(array $commandData, array $products = []): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Validation de la présence des champs obligatoires
            if (empty($commandData['user_id']) || empty($commandData['delivery_date']) || 
                empty($commandData['fk_status_id'])) {
                return false;
            }

            // Début de la transaction pour assurer la cohérence des données
            $this->connection->beginTransaction();

            // Préparation de la requête INSERT avec paramètres nommés pour éviter les injections SQL
            // Ajout du champ created_at avec la date/heure actuelle
            $query = "INSERT INTO commands (fk_user_id, delivery_date, created_at, fk_status_id) 
                      VALUES (:user_id, :delivery_date, NOW(), :fk_status_id)";
            
            $stmt = $this->connection->prepare($query);
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':user_id', $commandData['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':delivery_date', $commandData['delivery_date'], PDO::PARAM_STR);
            $stmt->bindParam(':fk_status_id', $commandData['fk_status_id'], PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $this->connection->rollBack();
                return false;
            }

            // Récupération de l'ID de la commande créée
            $commandId = $this->connection->lastInsertId();

            // Insertion des détails des produits dans command_details
            if (!empty($products)) {
                $detailsQuery = "INSERT INTO command_details (fk_command_id, fk_product_id, created_at) 
                                VALUES (:command_id, :product_id, NOW())";
                $detailsStmt = $this->connection->prepare($detailsQuery);

                foreach ($products as $productId => $productData) {
                    $quantity = (int)($productData['quantity'] ?? 0);
                    
                    // Insérer une ligne pour chaque quantité du produit
                    for ($i = 0; $i < $quantity; $i++) {
                        $detailsStmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
                        $detailsStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                        
                        if (!$detailsStmt->execute()) {
                            $this->connection->rollBack();
                            return false;
                        }
                    }
                }
            }

            // Validation de la transaction
            $this->connection->commit();
            return true;
            
        } catch (PDOException $e) {
            // En cas d'erreur, annulation de la transaction
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }
            return false;
        }
    }

    /**
     * Supprime une commande de la base de données.
     * 
     * Supprime l'enregistrement de la commande spécifiée par son ID.
     * La suppression peut échouer si la commande a des relations avec d'autres tables
     * ou si la connexion à la base de données est perdue.
     *
     * @param int $commandId ID de la commande à supprimer.
     * @return bool True si la suppression a réussi, false en cas d'erreur.
     */
    public function deleteCommand(int $commandId): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Préparation de la requête DELETE avec paramètre nommé pour éviter les injections SQL
            $query = "DELETE FROM commands WHERE command_id = :command_id";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // En cas d'erreur (contrainte DB, connexion perdue, etc.), retourner false
            return false;
        }
    }
}

