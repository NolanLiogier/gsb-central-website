<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use App\Helpers\UserService;

/**
 * Classe DashboardRepository
 * 
 * Repository pour la récupération des données du tableau de bord.
 * Centralise les appels de données selon le rôle de l'utilisateur.
 */
class DashboardRepository {
    /**
     * Connexion à la base de données.
     * 
     * @var PDO|null
     */
    private ?PDO $conn = null;

    /**
     * Service utilisateur pour la récupération des données utilisateur.
     * 
     * @var UserService
     */
    private UserService $userService;

    /**
     * Initialise la connexion à la base de données.
     * 
     * @return void
     */
    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
        $this->userService = new UserService();
    }

    /**
     * Récupère toutes les données nécessaires au tableau de bord selon le rôle.
     * 
     * Vérifie le rôle de l'utilisateur et retourne les statistiques appropriées :
     * - Client : nombre de commandes, produits les plus commandés
     * - Commercial : meilleur client, meilleur produit, objectif mensuel
     * - Logisticien : nombre de commandes envoyées
     *
     * @return array Les données à afficher sur le tableau de bord.
     */
    public function getDatas(): array {
        // Vérification de l'authentification avec le UserService
        if (!$this->userService->isAuthenticated()) {
            return ['error' => 'Utilisateur non authentifié'];
        }

        // Récupération des données utilisateur via le UserService
        $userRole = $this->userService->getCurrentUserRole();
        $userId = $this->userService->getCurrentUserId();
        $userCompanyId = $this->userService->getCurrentUserCompanyId();

        // Si l'utilisateur n'a pas de rôle défini, erreur
        if ($userRole === null || $userId === null) {
            return ['error' => 'Données utilisateur incomplètes'];
        }

        // Détermination des données selon le rôle
        return match ($userRole) {
            2 => $this->getClientDatas($userId, $userCompanyId), // Client
            1 => $this->getCommercialDatas($userId), // Commercial
            3 => $this->getLogisticianDatas(), // Logisticien
            default => ['error' => 'Rôle non reconnu'],
        };
    }

    /**
     * Récupère les données pour un client.
     * 
     * Retourne le nombre de commandes et les produits les plus commandés.
     *
     * @param int $userId ID de l'utilisateur.
     * @param int|null $companyId ID de l'entreprise du client.
     * @return array Données du client (nombre de commandes, produits les plus commandés).
     */
    private function getClientDatas(int $userId, ?int $companyId): array {
        $totalOrders = $this->getClientTotalOrders($userId);
        $mostOrderedProducts = $this->getClientMostOrderedProducts($userId);

        return [
            'userRole' => 'client',
            'totalOrders' => $totalOrders,
            'mostOrderedProducts' => $mostOrderedProducts,
        ];
    }

    /**
     * Récupère les données pour un commercial.
     * 
     * Retourne le meilleur client, le meilleur produit, et l'objectif mensuel.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données du commercial.
     */
    private function getCommercialDatas(int $salesmanId): array {
        $bestClient = $this->getBestClient($salesmanId);
        $bestProduct = $this->getBestProduct($salesmanId);
        $monthlyObjective = $this->getMonthlyObjective($salesmanId);

        return [
            'userRole' => 'commercial',
            'bestClient' => $bestClient,
            'bestProduct' => $bestProduct,
            'monthlyObjective' => $monthlyObjective,
        ];
    }

    /**
     * Récupère les données pour un logisticien.
     * 
     * Retourne le nombre de commandes envoyées.
     *
     * @return array Données du logisticien.
     */
    private function getLogisticianDatas(): array {
        $sentOrders = $this->getSentOrdersCount();

        return [
            'userRole' => 'logisticien',
            'sentOrders' => $sentOrders,
        ];
    }

    /**
     * Récupère le nombre total de commandes d'un client.
     *
     * @param int $userId ID de l'utilisateur client.
     * @return int Nombre de commandes.
     */
    private function getClientTotalOrders(int $userId): int {
        $query = "SELECT COUNT(*) as total FROM commands WHERE fk_user_id = :userId";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }

    /**
     * Récupère les produits les plus commandés par un client.
     *
     * @param int $userId ID de l'utilisateur client.
     * @return array Liste des produits avec leur quantité commandée.
     */
    private function getClientMostOrderedProducts(int $userId): array {
        $query = "SELECT 
                    s.product_name,
                    SUM(1) as total_ordered
                  FROM commands c
                  INNER JOIN command_details cd ON c.command_id = cd.fk_command_id
                  INNER JOIN stock s ON cd.fk_product_id = s.product_id
                  WHERE c.fk_user_id = :userId
                  GROUP BY s.product_id, s.product_name
                  ORDER BY total_ordered DESC
                  LIMIT 5";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupère le meilleur client d'un commercial.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données du meilleur client.
     */
    private function getBestClient(int $salesmanId): array {
        $query = "SELECT 
                    c.company_name,
                    COUNT(cm.command_id) as total_orders
                  FROM companies c
                  INNER JOIN users u ON u.fk_company_id = c.company_id
                  LEFT JOIN commands cm ON cm.fk_user_id = u.user_id
                  WHERE c.fk_salesman_id = :salesmanId
                  GROUP BY c.company_id, c.company_name
                  ORDER BY total_orders DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupère le meilleur produit d'un commercial.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données du meilleur produit.
     */
    private function getBestProduct(int $salesmanId): array {
        $query = "SELECT 
                    s.product_name,
                    COUNT(cd.details_id) as total_sold
                  FROM commands cm
                  INNER JOIN command_details cd ON cm.command_id = cd.fk_command_id
                  INNER JOIN stock s ON cd.fk_product_id = s.product_id
                  INNER JOIN users u ON cm.fk_user_id = u.user_id
                  INNER JOIN companies c ON u.fk_company_id = c.company_id
                  WHERE c.fk_salesman_id = :salesmanId
                  GROUP BY s.product_id, s.product_name
                  ORDER BY total_sold DESC
                  LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupère l'objectif mensuel d'un commercial.
     * 
     * Calcule le nombre de commandes du mois en cours pour déterminer l'avancement.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données de l'objectif mensuel (actuel et objectif).
     */
    private function getMonthlyObjective(int $salesmanId): array {
        $query = "SELECT COUNT(cm.command_id) as current_month_orders
                  FROM commands cm
                  INNER JOIN users u ON cm.fk_user_id = u.user_id
                  INNER JOIN companies c ON u.fk_company_id = c.company_id
                  WHERE c.fk_salesman_id = :salesmanId
                  AND MONTH(cm.created_at) = MONTH(CURRENT_DATE())
                  AND YEAR(cm.created_at) = YEAR(CURRENT_DATE())";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $current = (int)($result['current_month_orders'] ?? 0);
        $objective = 10; // Objectif fixe de 10 commandes par mois

        return [
            'current' => $current,
            'objective' => $objective,
            'percentage' => round(($current / $objective) * 100, 1)
        ];
    }

    /**
     * Récupère le nombre de commandes envoyées.
     * 
     * Compte les commandes avec le statut "envoyé" (status_id = 2).
     *
     * @return int Nombre de commandes envoyées.
     */
    private function getSentOrdersCount(): int {
        $query = "SELECT COUNT(*) as total FROM commands WHERE fk_status_id = 2";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
}