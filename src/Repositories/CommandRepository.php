<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;
use App\Repositories\DeliveryAddressRepository;

/**
 * Classe CommandRepository
 * 
 * Repository pour l'accès et la manipulation des données des commandes.
 * Fournit des méthodes pour récupérer les commandes des utilisateurs depuis la base de données.
 */
class CommandRepository {

    /**
     * Récupère tous les statuts disponibles.
     * 
     * Récupère tous les statuts depuis la table status pour l'affichage dans les formulaires.
     *
     * @return array Liste des statuts avec status_id et status_name.
     */
    public function getAllStatuses(): array {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return [];
            }

            $query = "SELECT status_id, status_name FROM status ORDER BY status_id";
            
            $stmt = $conn->prepare($query);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return [];
        }
    }

    /**
     * Récupère toutes les commandes selon le rôle et les permissions de l'utilisateur.
     * 
     * Récupère les commandes selon les règles métier :
     * - Client (function_id = 2) : toutes les commandes de son entreprise
     * - Commercial (function_id = 1) : commandes des entreprises qui lui sont assignées
     * - Logisticien (function_id = 3) : toutes les commandes avec statut "validé" (1)
     *
     * @param array $user Informations de l'utilisateur (user_id, fk_company_id, fk_function_id).
     * @return array Liste des commandes avec leurs informations et produits.
     */
    public function getCommandsByUserRole(array $user): array {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return [];
            }

            $userFunctionId = $user['fk_function_id'] ?? null;
            $userCompanyId = $user['fk_company_id'] ?? null;
            $userId = $user['user_id'] ?? null;

            $query = "";
            $params = [];

            // Construction de la requête selon le rôle
            if ($userFunctionId == 2) { // Client
                // Client : toutes les commandes de son entreprise
                $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                                 u.firstname, u.lastname, u.email,
                                 cd.fk_delivery_address
                          FROM commands c
                          LEFT JOIN status s ON c.fk_status_id = s.status_id
                          LEFT JOIN users u ON c.fk_user_id = u.user_id
                          LEFT JOIN command_details cd ON c.command_id = cd.fk_command_id
                          WHERE u.fk_company_id = :company_id
                          GROUP BY c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                                   u.firstname, u.lastname, u.email, cd.fk_delivery_address
                          ORDER BY c.created_at DESC";
                $params[':company_id'] = $userCompanyId;
                
            } elseif ($userFunctionId == 1) { // Commercial
                // Commercial : commandes des entreprises qui lui sont assignées
                $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                                 u.firstname, u.lastname, u.email, comp.company_name,
                                 cd.fk_delivery_address
                          FROM commands c
                          LEFT JOIN status s ON c.fk_status_id = s.status_id
                          LEFT JOIN users u ON c.fk_user_id = u.user_id
                          LEFT JOIN companies comp ON u.fk_company_id = comp.company_id
                          LEFT JOIN command_details cd ON c.command_id = cd.fk_command_id
                          WHERE comp.fk_salesman_id = :salesman_id
                          GROUP BY c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                                   u.firstname, u.lastname, u.email, comp.company_name, cd.fk_delivery_address
                          ORDER BY c.created_at DESC";
                $params[':salesman_id'] = $userId;
                
            } elseif ($userFunctionId == 3) { // Logisticien
                // Logisticien : toutes les commandes avec statut "validé" (1)
                $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                                 u.firstname, u.lastname, u.email, comp.company_name,
                                 cd.fk_delivery_address
                          FROM commands c
                          LEFT JOIN status s ON c.fk_status_id = s.status_id
                          LEFT JOIN users u ON c.fk_user_id = u.user_id
                          LEFT JOIN companies comp ON u.fk_company_id = comp.company_id
                          LEFT JOIN command_details cd ON c.command_id = cd.fk_command_id
                          WHERE c.fk_status_id = 1
                          GROUP BY c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                                   u.firstname, u.lastname, u.email, comp.company_name, cd.fk_delivery_address
                          ORDER BY c.created_at DESC";
            } else {
                // Rôle non reconnu
                return [];
            }
            
            $stmt = $conn->prepare($query);
            
            // Bind des paramètres
            foreach ($params as $param => $value) {
                $stmt->bindValue($param, $value, PDO::PARAM_INT);
            }
            
            $stmt->execute();
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque commande, récupérer les détails des produits et l'adresse de livraison
            $deliveryAddressRepository = new DeliveryAddressRepository();
            foreach ($commands as &$command) {
                $command['products'] = $this->getCommandProducts($command['command_id']);
                
                // Récupérer l'adresse de livraison si elle existe
                if (!empty($command['fk_delivery_address']) && $command['fk_delivery_address'] !== null) {
                    $deliveryAddress = $deliveryAddressRepository->getAddressById((int)$command['fk_delivery_address']);
                    if ($deliveryAddress) {
                        // Construire une chaîne d'adresse complète pour l'affichage
                        $addressParts = [];
                        if (!empty($deliveryAddress['street'])) {
                            $addressParts[] = $deliveryAddress['street'];
                        }
                        if (!empty($deliveryAddress['postal_code']) || !empty($deliveryAddress['city'])) {
                            $addressParts[] = trim(($deliveryAddress['postal_code'] ?? '') . ' ' . ($deliveryAddress['city'] ?? ''));
                        }
                        if (!empty($deliveryAddress['country'])) {
                            $addressParts[] = $deliveryAddress['country'];
                        }
                        if (!empty($deliveryAddress['additional_info'])) {
                            $addressParts[] = $deliveryAddress['additional_info'];
                        }
                        $command['delivery_address'] = implode(', ', $addressParts);
                        $command['delivery_address_data'] = $deliveryAddress;
                    }
                }
            }
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $commands;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
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
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return [];
            }

            // Récupération de toutes les commandes de l'utilisateur triées par date
            $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name,
                             cd.fk_delivery_address
                      FROM commands c
                      LEFT JOIN status s ON c.fk_status_id = s.status_id
                      LEFT JOIN command_details cd ON c.command_id = cd.fk_command_id
                      WHERE c.fk_user_id = :user_id
                      GROUP BY c.command_id, c.delivery_date, c.created_at, c.fk_status_id, s.status_name, cd.fk_delivery_address
                      ORDER BY c.created_at DESC";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $commands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Pour chaque commande, récupérer les détails des produits et l'adresse de livraison
            $deliveryAddressRepository = new DeliveryAddressRepository();
            foreach ($commands as &$command) {
                $command['products'] = $this->getCommandProducts($command['command_id']);
                
                // Récupérer l'adresse de livraison si elle existe
                if (!empty($command['fk_delivery_address']) && $command['fk_delivery_address'] !== null) {
                    $deliveryAddress = $deliveryAddressRepository->getAddressById((int)$command['fk_delivery_address']);
                    if ($deliveryAddress) {
                        // Construire une chaîne d'adresse complète pour l'affichage
                        $addressParts = [];
                        if (!empty($deliveryAddress['street'])) {
                            $addressParts[] = $deliveryAddress['street'];
                        }
                        if (!empty($deliveryAddress['postal_code']) || !empty($deliveryAddress['city'])) {
                            $addressParts[] = trim(($deliveryAddress['postal_code'] ?? '') . ' ' . ($deliveryAddress['city'] ?? ''));
                        }
                        if (!empty($deliveryAddress['country'])) {
                            $addressParts[] = $deliveryAddress['country'];
                        }
                        if (!empty($deliveryAddress['additional_info'])) {
                            $addressParts[] = $deliveryAddress['additional_info'];
                        }
                        $command['delivery_address'] = implode(', ', $addressParts);
                        $command['delivery_address_data'] = $deliveryAddress;
                    }
                }
            }
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $commands;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

    /**
     * Récupère les produits d'une commande spécifique.
     * 
     * @param int $commandId ID de la commande.
     * @return array Liste des produits avec leurs quantités et adresse de livraison.
     */
    private function getCommandProducts(int $commandId): array {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            if (!$conn) {
                return [];
            }
            
            $query = "SELECT st.product_id, st.product_name, st.price, COUNT(cd.details_id) as quantity,
                             cd.fk_delivery_address
                      FROM command_details cd
                      JOIN stock st ON cd.fk_product_id = st.product_id
                      WHERE cd.fk_command_id = :command_id
                      GROUP BY st.product_id, st.product_name, st.price, cd.fk_delivery_address";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return [];
        }
    }

    /**
     * Récupère les données d'une commande spécifique par son ID.
     * 
     * Récupère toutes les informations nécessaires pour l'édition d'une commande,
     * incluant les produits commandés et l'adresse de livraison.
     *
     * @param int $commandId ID de la commande à récupérer.
     * @return array Données de la commande avec command_id, delivery_date, created_at, fk_status_id, fk_user_id, products, delivery_address, ou tableau vide.
     */
    public function getCommandById(int $commandId): array {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return [];
            }

            $query = "SELECT c.command_id, c.delivery_date, c.created_at, c.fk_status_id, c.fk_user_id, s.status_name
                      FROM commands c
                      LEFT JOIN status s ON c.fk_status_id = s.status_id
                      WHERE c.command_id = :command_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            $stmt->execute();
            
            // fetch() retourne false si aucun résultat, convertir en tableau vide
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                // Ajouter les produits de la commande
                $products = $this->getCommandProducts($commandId);
                $result['products'] = $products;
                
                // Récupérer l'adresse de livraison depuis le premier produit (tous les produits d'une commande partagent la même adresse)
                if (!empty($products) && isset($products[0]['fk_delivery_address']) && $products[0]['fk_delivery_address'] !== null) {
                    $deliveryAddressRepository = new DeliveryAddressRepository();
                    $deliveryAddress = $deliveryAddressRepository->getAddressById((int)$products[0]['fk_delivery_address']);
                    if ($deliveryAddress) {
                        // Construire une chaîne d'adresse complète pour l'affichage
                        $addressParts = [];
                        if (!empty($deliveryAddress['street'])) {
                            $addressParts[] = $deliveryAddress['street'];
                        }
                        if (!empty($deliveryAddress['postal_code']) || !empty($deliveryAddress['city'])) {
                            $addressParts[] = trim(($deliveryAddress['postal_code'] ?? '') . ' ' . ($deliveryAddress['city'] ?? ''));
                        }
                        if (!empty($deliveryAddress['country'])) {
                            $addressParts[] = $deliveryAddress['country'];
                        }
                        if (!empty($deliveryAddress['additional_info'])) {
                            $addressParts[] = $deliveryAddress['additional_info'];
                        }
                        $result['delivery_address'] = implode(', ', $addressParts);
                        $result['delivery_address_data'] = $deliveryAddress;
                    }
                }
            }
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result ?: [];
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
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
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant toute opération
            if (!$conn) {
                return false;
            }

            // Validation de la présence des champs obligatoires
            if (empty($commandData['command_id']) || empty($commandData['delivery_date']) || 
                empty($commandData['fk_status_id'])) {
                return false;
            }

            // Début de la transaction pour assurer la cohérence des données
            $conn->beginTransaction();

            // Préparation de la requête UPDATE avec paramètres nommés pour éviter les injections SQL
            $query = "UPDATE commands 
                      SET delivery_date = :delivery_date, 
                          fk_status_id = :fk_status_id
                      WHERE command_id = :command_id";
            
            $stmt = $conn->prepare($query);
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':command_id', $commandData['command_id'], PDO::PARAM_INT);
            $stmt->bindParam(':delivery_date', $commandData['delivery_date'], PDO::PARAM_STR);
            $stmt->bindParam(':fk_status_id', $commandData['fk_status_id'], PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $conn->rollBack();
                $conn = null;
                $database = null;
                return false;
            }

            // Mise à jour des détails des produits si fournis
            if (!empty($products)) {
                // Supprimer les anciens détails de la commande
                $deleteQuery = "DELETE FROM command_details WHERE fk_command_id = :command_id";
                $deleteStmt = $conn->prepare($deleteQuery);
                $deleteStmt->bindParam(':command_id', $commandData['command_id'], PDO::PARAM_INT);
                
                if (!$deleteStmt->execute()) {
                    $conn->rollBack();
                    $conn = null;
                    $database = null;
                    return false;
                }

                // Gestion de l'adresse de livraison : trouver ou créer l'adresse
                $deliveryAddressId = null;
                if (!empty($commandData['delivery_address_data'])) {
                    $deliveryAddressRepository = new DeliveryAddressRepository();
                    $deliveryAddressId = $deliveryAddressRepository->findOrCreateAddress($commandData['delivery_address_data']);
                    if ($deliveryAddressId === null) {
                        $conn->rollBack();
                        $conn = null;
                        $database = null;
                        return false;
                    }
                }

                // Insérer les nouveaux détails des produits avec l'adresse de livraison
                $detailsQuery = "INSERT INTO command_details (fk_command_id, fk_product_id, fk_delivery_address, created_at) 
                                VALUES (:command_id, :product_id, :delivery_address_id, NOW())";
                $detailsStmt = $conn->prepare($detailsQuery);

                foreach ($products as $productId => $productData) {
                    $quantity = (int)($productData['quantity'] ?? 0);
                    
                    // Insérer une ligne pour chaque quantité du produit
                    for ($i = 0; $i < $quantity; $i++) {
                        $detailsStmt->bindParam(':command_id', $commandData['command_id'], PDO::PARAM_INT);
                        $detailsStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                        $detailsStmt->bindValue(':delivery_address_id', $deliveryAddressId, $deliveryAddressId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        
                        if (!$detailsStmt->execute()) {
                            $conn->rollBack();
                            $conn = null;
                            $database = null;
                            return false;
                        }
                    }
                }
            }

            // Validation de la transaction
            $conn->commit();
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return true;
            
        } catch (PDOException $e) {
            // En cas d'erreur, annulation de la transaction
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
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
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant toute opération
            if (!$conn) {
                return false;
            }

            // Validation de la présence des champs obligatoires
            if (empty($commandData['user_id']) || empty($commandData['delivery_date']) || 
                empty($commandData['fk_status_id'])) {
                return false;
            }

            // Début de la transaction pour assurer la cohérence des données
            $conn->beginTransaction();

            // Préparation de la requête INSERT avec paramètres nommés pour éviter les injections SQL
            // Ajout du champ created_at avec la date/heure actuelle
            $query = "INSERT INTO commands (fk_user_id, delivery_date, created_at, fk_status_id) 
                      VALUES (:user_id, :delivery_date, NOW(), :fk_status_id)";
            
            $stmt = $conn->prepare($query);
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':user_id', $commandData['user_id'], PDO::PARAM_INT);
            $stmt->bindParam(':delivery_date', $commandData['delivery_date'], PDO::PARAM_STR);
            $stmt->bindParam(':fk_status_id', $commandData['fk_status_id'], PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $conn->rollBack();
                $conn = null;
                $database = null;
                return false;
            }

            // Récupération de l'ID de la commande créée
            $commandId = $conn->lastInsertId();

            // Gestion de l'adresse de livraison : trouver ou créer l'adresse
            $deliveryAddressId = null;
            if (!empty($commandData['delivery_address_data'])) {
                $deliveryAddressRepository = new DeliveryAddressRepository();
                $deliveryAddressId = $deliveryAddressRepository->findOrCreateAddress($commandData['delivery_address_data']);
                if ($deliveryAddressId === null) {
                    $conn->rollBack();
                    $conn = null;
                    $database = null;
                    return false;
                }
            }

            // Insertion des détails des produits dans command_details avec l'adresse de livraison
            if (!empty($products)) {
                $detailsQuery = "INSERT INTO command_details (fk_command_id, fk_product_id, fk_delivery_address, created_at) 
                                VALUES (:command_id, :product_id, :delivery_address_id, NOW())";
                $detailsStmt = $conn->prepare($detailsQuery);

                foreach ($products as $productId => $productData) {
                    $quantity = (int)($productData['quantity'] ?? 0);
                    
                    // Insérer une ligne pour chaque quantité du produit
                    for ($i = 0; $i < $quantity; $i++) {
                        $detailsStmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
                        $detailsStmt->bindParam(':product_id', $productId, PDO::PARAM_INT);
                        $detailsStmt->bindValue(':delivery_address_id', $deliveryAddressId, $deliveryAddressId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
                        
                        if (!$detailsStmt->execute()) {
                            $conn->rollBack();
                            $conn = null;
                            $database = null;
                            return false;
                        }
                    }
                }
            }

            // Validation de la transaction
            $conn->commit();
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return true;
            
        } catch (PDOException $e) {
            // En cas d'erreur, annulation de la transaction
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return false;
        }
    }

    /**
     * Vérifie si un utilisateur peut modifier/supprimer une commande selon son statut et son rôle.
     * 
     * Règles métier :
     * - Client : peut modifier/supprimer UNIQUEMENT si statut = "en attente" (3)
     * - Commercial : peut modifier/supprimer et valider les commandes
     * - Logisticien : peut seulement envoyer les commandes validées
     *
     * @param array $user Informations de l'utilisateur (user_id, fk_function_id).
     * @param int $commandId ID de la commande à vérifier.
     * @param string $action Action à vérifier ('modify', 'delete', 'validate', 'send').
     * @return bool True si l'utilisateur peut effectuer l'action, false sinon.
     */
    public function canUserPerformAction(array $user, int $commandId, string $action): bool {
        try {
            // Récupération des informations de la commande
            $command = $this->getCommandById($commandId);
            if (empty($command)) {
                return false;
            }

            $userFunctionId = $user['fk_function_id'] ?? null;
            $commandStatusId = $command['fk_status_id'] ?? null;

            // Vérification selon le rôle et l'action
            switch ($action) {
                case 'modify':
                    if ($userFunctionId == 2) { // Client
                        // Client : seulement si statut = "en attente" (3)
                        return $commandStatusId == 3;
                    } elseif ($userFunctionId == 1) { // Commercial
                        // Commercial : peut modifier toutes les commandes de ses clients
                        return $this->isCommandFromSalesmanClient($user['user_id'], $commandId);
                    } elseif ($userFunctionId == 3) { // Logisticien
                        // Logisticien : peut voir toutes les commandes (lecture seule)
                        return true;
                    }
                    return false;

                case 'delete':
                    if ($commandStatusId == 2) {
                        return false;
                    }
                    if ($userFunctionId == 2) {
                        return $commandStatusId == 3;
                    } elseif ($userFunctionId == 1) {
                        return $this->isCommandFromSalesmanClient($user['user_id'], $commandId);
                    }
                    return false;

                case 'validate':
                    if ($userFunctionId == 1) { // Commercial
                        // Commercial : peut valider seulement les commandes "en attente" (3)
                        return $commandStatusId == 3 && $this->isCommandFromSalesmanClient($user['user_id'], $commandId);
                    }
                    return false;

                case 'send':
                    if ($userFunctionId == 3) { // Logisticien
                        // Logisticien : peut envoyer seulement les commandes "validé" (1)
                        return $commandStatusId == 1;
                    }
                    return false;

                default:
                    return false;
            }
            
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Vérifie si une commande appartient à un client d'un commercial spécifique.
     * 
     * @param int $salesmanId ID du commercial.
     * @param int $commandId ID de la commande.
     * @return bool True si la commande appartient à un client du commercial, false sinon.
     */
    private function isCommandFromSalesmanClient(int $salesmanId, int $commandId): bool {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            if (!$conn) {
                return false;
            }
            
            $query = "SELECT COUNT(*) as count
                      FROM commands c
                      JOIN users u ON c.fk_user_id = u.user_id
                      JOIN companies comp ON u.fk_company_id = comp.company_id
                      WHERE c.command_id = :command_id 
                      AND comp.fk_salesman_id = :salesman_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            $stmt->bindParam(':salesman_id', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
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
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant toute opération
            if (!$conn) {
                return false;
            }

            // Préparation de la requête DELETE avec paramètre nommé pour éviter les injections SQL
            $query = "DELETE FROM commands WHERE command_id = :command_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            // En cas d'erreur (contrainte DB, connexion perdue, etc.), retourner false
            return false;
        }
    }

    public function updateStockQty(int $commandId): array {
        $command = $this->getCommandById($commandId);
        if (empty($command) || empty($command['products'])) {
            return ['success' => false, 'insufficient_products' => []];
        }

        $productQuantities = [];
        foreach ($command['products'] as $p) {
            $id = (int)($p['product_id'] ?? 0);
            $qty = (int)($p['quantity'] ?? 0);
            if ($id > 0 && $qty > 0) {
                $productQuantities[$id] = ($productQuantities[$id] ?? 0) + $qty;
            }
        }

        if (empty($productQuantities)) {
            return ['success' => false, 'insufficient_products' => []];
        }

        $database = new Database();
        $conn = $database->getConnection();
        if (!$conn) {
            return ['success' => false, 'insufficient_products' => []];
        }

        try {
            $placeholders = implode(',', array_fill(0, count($productQuantities), '?'));
            $stmt = $conn->prepare("SELECT product_id, quantity, product_name FROM stock WHERE product_id IN ($placeholders)");
            $stmt->execute(array_keys($productQuantities));
            $stockRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $stockByProduct = [];
            foreach ($stockRows as $row) {
                $stockByProduct[(int)$row['product_id']] = [
                    'quantity' => (int)$row['quantity'],
                    'product_name' => $row['product_name'] ?? ''
                ];
            }

            $insufficient = [];
            foreach ($productQuantities as $productId => $required) {
                $current = $stockByProduct[$productId]['quantity'] ?? 0;
                if ($current < $required) {
                    $insufficient[] = $stockByProduct[$productId]['product_name'] ?? 'product_id_' . $productId;
                }
            }

            if (!empty($insufficient)) {
                $conn = null;
                $database = null;
                return ['success' => false, 'insufficient_products' => $insufficient];
            }

            $conn->beginTransaction();
            $stmt = $conn->prepare("UPDATE stock SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?");
            foreach ($productQuantities as $productId => $qty) {
                $stmt->execute([$qty, $productId, $qty]);
                if ($stmt->rowCount() === 0) {
                    $conn->rollBack();
                    $conn = null;
                    $database = null;
                    return ['success' => false, 'insufficient_products' => []];
                }
            }
            
            $conn->commit();
            $conn = null;
            $database = null;
            return ['success' => true, 'insufficient_products' => []];
        } 
        catch (PDOException $e) {
            if ($conn && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $conn = null;
            $database = null;
            return ['success' => false, 'insufficient_products' => []];
        }
    }

    /**
     * Met à jour le statut d'une commande selon l'action effectuée.
     * 
     * @param int $commandId ID de la commande.
     * @param string $action Action effectuée ('validate' ou 'send').
     * @return bool True si la mise à jour a réussi, false en cas d'erreur.
     */
    public function updateCommandStatus(int $commandId, string $action): bool {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            if (!$conn) {
                return false;
            }

            $newStatusId = null;
            switch ($action) {
                case 'validate':
                    $newStatusId = 1; // validé
                    break;
                case 'send':
                    $newStatusId = 2; // envoyé
                    break;
                default:
                    return false;
            }

            $query = "UPDATE commands SET fk_status_id = :status_id WHERE command_id = :command_id";
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':status_id', $newStatusId, PDO::PARAM_INT);
            $stmt->bindParam(':command_id', $commandId, PDO::PARAM_INT);
            
            $result = $stmt->execute();
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return false;
        }
    }
}

