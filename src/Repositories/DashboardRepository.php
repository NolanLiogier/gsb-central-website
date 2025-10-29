<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;
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
     * Retourne le nombre de commandes, les produits les plus commandés, l'historique,
     * le montant total dépensé et les commandes par statut.
     *
     * @param int $userId ID de l'utilisateur.
     * @param int|null $companyId ID de l'entreprise du client.
     * @return array Données du client (KPI, historique, montants).
     */
    private function getClientDatas(int $userId, ?int $companyId): array {
        $totalOrders = $this->getClientTotalOrders($userId);
        $mostOrderedProducts = $this->getClientMostOrderedProducts($userId);
        $ordersByStatus = $this->getClientOrdersByStatus($userId);
        $totalAmountSpent = $this->getClientTotalAmountSpent($userId);
        $ordersHistory = $this->getClientOrdersHistory($userId);

        return [
            'userRole' => 'client',
            'totalOrders' => $totalOrders,
            'mostOrderedProducts' => $mostOrderedProducts,
            'ordersByStatus' => $ordersByStatus,
            'totalAmountSpent' => $totalAmountSpent,
            'ordersHistory' => $ordersHistory,
        ];
    }

    /**
     * Récupère les données pour un commercial.
     * 
     * Retourne les KPI, top clients, top produits, évolution CA et commandes en attente.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données du commercial.
     */
    private function getCommercialDatas(int $salesmanId): array {
        // KPI principaux (mois actuel)
        $monthlyStats = $this->getMonthlyStats($salesmanId);
        $revenueStats = $this->getRevenueStats($salesmanId);
        
        // Top 5
        $topClients = $this->getTopClientsByRevenue($salesmanId);
        $topProducts = $this->getTopProductsSold($salesmanId);
        
        // Données pour graphiques
        $revenueEvolution = $this->getRevenueEvolution($salesmanId);
        
        // Commandes en attente
        $pendingOrders = $this->getPendingOrders($salesmanId);
        
        // Données existantes (conservées pour compatibilité)
        $bestClient = $this->getBestClient($salesmanId);
        $bestProduct = $this->getBestProduct($salesmanId);
        $monthlyObjective = $this->getMonthlyObjective($salesmanId);

        return [
            'userRole' => 'commercial',
            // KPI
            'monthlyOrders' => $monthlyStats['orders'],
            'monthlyRevenue' => $revenueStats['revenue'],
            'previousMonthRevenue' => $revenueStats['previousRevenue'],
            'revenueEvolution' => $revenueStats['evolution'],
            'averageBasket' => $revenueStats['averageBasket'],
            // Top 5
            'topClients' => $topClients,
            'topProducts' => $topProducts,
            // Graphiques
            'revenueEvolutionData' => $revenueEvolution,
            // Opérationnel
            'pendingOrders' => $pendingOrders,
            // Données existantes (pour compatibilité)
            'bestClient' => $bestClient,
            'bestProduct' => $bestProduct,
            'monthlyObjective' => $monthlyObjective,
        ];
    }

    /**
     * Récupère les données pour un logisticien.
     * 
     * Retourne les KPI logistiques : commandes par statut, performance expédition,
     * stocks, rotation et alertes.
     *
     * @return array Données du logisticien.
     */
    private function getLogisticianDatas(): array {
        // KPI commandes
        $ordersByStatus = $this->getOrdersByStatus();
        $shippingPerformance = $this->getShippingPerformance();
        $averageProcessingTime = $this->getAverageProcessingTime();
        
        // KPI stocks
        $lowStockProducts = $this->getLowStockProducts();
        $totalStockValue = $this->getTotalStockValue();
        $stockRotation = $this->getStockRotation();
        $stockMovements = $this->getStockMovements();
        $reorderAlerts = $this->getReorderAlerts();

        return [
            'userRole' => 'logisticien',
            // Commandes
            'ordersToPrepare' => $ordersByStatus['toPrepare'],
            'ordersShipped' => $ordersByStatus['shipped'],
            'ordersDelivered' => $ordersByStatus['delivered'],
            'onTimeShippingRate' => $shippingPerformance['onTimeRate'],
            'onTimeShippingCount' => $shippingPerformance['onTimeCount'],
            'totalShipped' => $shippingPerformance['totalShipped'],
            'averageProcessingTime' => $averageProcessingTime,
            // Stocks
            'lowStockProducts' => $lowStockProducts,
            'totalStockValue' => $totalStockValue,
            'stockRotation' => $stockRotation,
            'stockMovements' => $stockMovements,
            'reorderAlerts' => $reorderAlerts,
        ];
    }

    /**
     * Récupère le nombre total de commandes d'un client.
     *
     * @param int $userId ID de l'utilisateur client.
     * @return int Nombre de commandes.
     */
    private function getClientTotalOrders(int $userId): int {
        try {
            $query = "SELECT COUNT(*) as total FROM commands WHERE fk_user_id = :userId";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Récupère les produits les plus commandés par un client.
     * 
     * Compte le nombre de fois qu'un produit apparaît dans les command_details
     * pour les commandes de ce client.
     *
     * @param int $userId ID de l'utilisateur client.
     * @return array Liste des produits avec leur quantité commandée.
     */
    private function getClientMostOrderedProducts(int $userId): array {
        try {
            $query = "SELECT 
                        s.product_id,
                        s.product_name,
                        COUNT(cd.details_id) as total_ordered
                      FROM commands c
                      INNER JOIN command_details cd ON c.command_id = cd.fk_command_id
                      INNER JOIN stock s ON cd.fk_product_id = s.product_id
                      WHERE c.fk_user_id = :userId
                      GROUP BY s.product_id, s.product_name
                      HAVING total_ordered > 0
                      ORDER BY total_ordered DESC
                      LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère le nombre de commandes par statut pour un client.
     * 
     * Compte les commandes selon leur statut : en cours (en attente=3, validé=1), 
     * expédiées (envoyé=2), livrées (envoyé=2, considérées comme livrées).
     *
     * @param int $userId ID de l'utilisateur client.
     * @return array Tableau avec les compteurs par statut.
     */
    private function getClientOrdersByStatus(int $userId): array {
        try {
            // Commandes en cours (en attente = 3 ou validé = 1)
            $inProgressQuery = "SELECT COUNT(*) as total 
                               FROM commands 
                               WHERE fk_user_id = :userId 
                               AND fk_status_id IN (1, 3)";
            $stmt = $this->conn->prepare($inProgressQuery);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $inProgressResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Commandes expédiées (envoyé = 2)
            $shippedQuery = "SELECT COUNT(*) as total 
                            FROM commands 
                            WHERE fk_user_id = :userId 
                            AND fk_status_id = 2";
            $stmt = $this->conn->prepare($shippedQuery);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $shippedResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pour cet indicateur, on considère les commandes envoyées comme livrées
            // (la table n'a pas de statut "livré" séparé)
            
            return [
                'inProgress' => (int)($inProgressResult['total'] ?? 0),
                'shipped' => (int)($shippedResult['total'] ?? 0),
                'delivered' => (int)($shippedResult['total'] ?? 0), // Même valeur que shipped
            ];
        } catch (PDOException $e) {
            return [
                'inProgress' => 0,
                'shipped' => 0,
                'delivered' => 0,
            ];
        }
    }

    /**
     * Calcule le montant total dépensé par un client.
     * 
     * Calcule la somme de tous les prix des produits commandés par le client.
     *
     * @param int $userId ID de l'utilisateur client.
     * @return float Montant total dépensé.
     */
    private function getClientTotalAmountSpent(int $userId): float {
        try {
            $query = "SELECT COALESCE(SUM(s.price), 0) as total_amount
                      FROM commands c
                      INNER JOIN command_details cd ON c.command_id = cd.fk_command_id
                      INNER JOIN stock s ON cd.fk_product_id = s.product_id
                      WHERE c.fk_user_id = :userId";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round((float)($result['total_amount'] ?? 0), 2);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    /**
     * Récupère l'historique des commandes d'un client.
     * 
     * Récupère les dernières commandes avec leurs détails (statut, date, montant).
     * Limité aux 10 dernières commandes pour l'affichage.
     *
     * @param int $userId ID de l'utilisateur client.
     * @return array Liste des commandes avec leurs informations.
     */
    private function getClientOrdersHistory(int $userId): array {
        try {
            $query = "SELECT 
                        c.command_id,
                        c.created_at,
                        c.delivery_date,
                        c.fk_status_id,
                        s.status_name,
                        COALESCE(SUM(st.price), 0) as total_amount,
                        COUNT(cd.details_id) as products_count
                      FROM commands c
                      LEFT JOIN status s ON c.fk_status_id = s.status_id
                      LEFT JOIN command_details cd ON c.command_id = cd.fk_command_id
                      LEFT JOIN stock st ON cd.fk_product_id = st.product_id
                      WHERE c.fk_user_id = :userId
                      GROUP BY c.command_id, c.created_at, c.delivery_date, c.fk_status_id, s.status_name
                      ORDER BY c.created_at DESC
                      LIMIT 10";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':userId', $userId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère le meilleur client d'un commercial.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données du meilleur client.
     */
    private function getBestClient(int $salesmanId): array {
        try {
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
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère le meilleur produit d'un commercial.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données du meilleur produit.
     */
    private function getBestProduct(int $salesmanId): array {
        try {
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
        } catch (PDOException $e) {
            return [];
        }
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
        try {
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
        } catch (PDOException $e) {
            // En cas d'erreur, retourner des valeurs par défaut pour éviter les erreurs d'affichage
            return [
                'current' => 0,
                'objective' => 10,
                'percentage' => 0.0
            ];
        }
    }

    /**
     * Récupère le nombre de commandes envoyées.
     * 
     * Compte les commandes avec le statut "envoyé" (status_id = 2).
     *
     * @return int Nombre de commandes envoyées.
     */
    private function getSentOrdersCount(): int {
        try {
            $query = "SELECT COUNT(*) as total FROM commands WHERE fk_status_id = 2";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }

    /**
     * Récupère les statistiques mensuelles (nombre de commandes du mois actuel).
     *
     * @param int $salesmanId ID du commercial.
     * @return array Statistiques mensuelles (orders).
     */
    private function getMonthlyStats(int $salesmanId): array {
        try {
            $query = "SELECT COUNT(cm.command_id) as orders
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
            
            return [
                'orders' => (int)($result['orders'] ?? 0)
            ];
        } catch (PDOException $e) {
            return ['orders' => 0];
        }
    }

    /**
     * Récupère les statistiques de chiffre d'affaires.
     * 
     * Calcule le CA du mois actuel, du mois précédent, l'évolution en % et le panier moyen.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Statistiques de CA (revenue, previousRevenue, evolution, averageBasket).
     */
    private function getRevenueStats(int $salesmanId): array {
        try {
            // CA du mois actuel
            $currentMonthQuery = "SELECT 
                                    COALESCE(SUM(s.price), 0) as revenue,
                                    COUNT(DISTINCT cm.command_id) as orders
                                  FROM commands cm
                                  INNER JOIN command_details cd ON cm.command_id = cd.fk_command_id
                                  INNER JOIN stock s ON cd.fk_product_id = s.product_id
                                  INNER JOIN users u ON cm.fk_user_id = u.user_id
                                  INNER JOIN companies c ON u.fk_company_id = c.company_id
                                  WHERE c.fk_salesman_id = :salesmanId
                                  AND MONTH(cm.created_at) = MONTH(CURRENT_DATE())
                                  AND YEAR(cm.created_at) = YEAR(CURRENT_DATE())";
            
            $stmt = $this->conn->prepare($currentMonthQuery);
            $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $currentRevenue = (float)($current['revenue'] ?? 0);
            $ordersCount = (int)($current['orders'] ?? 0);
            
            // CA du mois précédent
            $previousMonthQuery = "SELECT COALESCE(SUM(s.price), 0) as revenue
                                   FROM commands cm
                                   INNER JOIN command_details cd ON cm.command_id = cd.fk_command_id
                                   INNER JOIN stock s ON cd.fk_product_id = s.product_id
                                   INNER JOIN users u ON cm.fk_user_id = u.user_id
                                   INNER JOIN companies c ON u.fk_company_id = c.company_id
                                   WHERE c.fk_salesman_id = :salesmanId
                                   AND MONTH(cm.created_at) = MONTH(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))
                                   AND YEAR(cm.created_at) = YEAR(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH))";
            
            $stmt = $this->conn->prepare($previousMonthQuery);
            $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            $previous = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $previousRevenue = (float)($previous['revenue'] ?? 0);
            
            // Calcul de l'évolution en %
            $evolution = 0;
            if ($previousRevenue > 0) {
                $evolution = round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 1);
            } elseif ($currentRevenue > 0) {
                $evolution = 100; // Nouveau CA
            }
            
            // Panier moyen
            $averageBasket = $ordersCount > 0 ? round($currentRevenue / $ordersCount, 2) : 0;
            
            return [
                'revenue' => $currentRevenue,
                'previousRevenue' => $previousRevenue,
                'evolution' => $evolution,
                'averageBasket' => $averageBasket
            ];
        } catch (PDOException $e) {
            return [
                'revenue' => 0,
                'previousRevenue' => 0,
                'evolution' => 0,
                'averageBasket' => 0
            ];
        }
    }

    /**
     * Récupère le top 5 des clients par chiffre d'affaires.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Liste des 5 meilleurs clients avec leur CA.
     */
    private function getTopClientsByRevenue(int $salesmanId): array {
        try {
            $query = "SELECT 
                        c.company_name,
                        COALESCE(SUM(s.price), 0) as revenue,
                        COUNT(DISTINCT cm.command_id) as total_orders
                      FROM companies c
                      INNER JOIN users u ON u.fk_company_id = c.company_id
                      LEFT JOIN commands cm ON cm.fk_user_id = u.user_id
                      LEFT JOIN command_details cd ON cm.command_id = cd.fk_command_id
                      LEFT JOIN stock s ON cd.fk_product_id = s.product_id
                      WHERE c.fk_salesman_id = :salesmanId
                      GROUP BY c.company_id, c.company_name
                      HAVING revenue > 0
                      ORDER BY revenue DESC
                      LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère le top 5 des produits les plus vendus (par quantité).
     *
     * @param int $salesmanId ID du commercial.
     * @return array Liste des 5 produits les plus vendus.
     */
    private function getTopProductsSold(int $salesmanId): array {
        try {
            $query = "SELECT 
                        s.product_name,
                        COUNT(cd.details_id) as total_sold,
                        COALESCE(SUM(s.price), 0) as total_revenue
                      FROM commands cm
                      INNER JOIN command_details cd ON cm.command_id = cd.fk_command_id
                      INNER JOIN stock s ON cd.fk_product_id = s.product_id
                      INNER JOIN users u ON cm.fk_user_id = u.user_id
                      INNER JOIN companies c ON u.fk_company_id = c.company_id
                      WHERE c.fk_salesman_id = :salesmanId
                      GROUP BY s.product_id, s.product_name
                      ORDER BY total_sold DESC
                      LIMIT 5";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère l'évolution du chiffre d'affaires par mois (12 derniers mois).
     * 
     * Calcule le CA mensuel pour afficher un graphique d'évolution.
     *
     * @param int $salesmanId ID du commercial.
     * @return array Données d'évolution avec mois et CA.
     */
    private function getRevenueEvolution(int $salesmanId): array {
        try {
            $query = "SELECT 
                        DATE_FORMAT(cm.created_at, '%Y-%m') as month,
                        COALESCE(SUM(s.price), 0) as revenue
                      FROM commands cm
                      INNER JOIN command_details cd ON cm.command_id = cd.fk_command_id
                      INNER JOIN stock s ON cd.fk_product_id = s.product_id
                      INNER JOIN users u ON cm.fk_user_id = u.user_id
                      INNER JOIN companies c ON u.fk_company_id = c.company_id
                      WHERE c.fk_salesman_id = :salesmanId
                      AND cm.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                      GROUP BY DATE_FORMAT(cm.created_at, '%Y-%m')
                      ORDER BY month ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère la liste des commandes en attente de validation.
     * 
     * Les commandes en attente sont celles avec le statut "en attente" (status_id = 3).
     *
     * @param int $salesmanId ID du commercial.
     * @return array Liste des commandes en attente avec leurs informations.
     */
    private function getPendingOrders(int $salesmanId): array {
        try {
            $query = "SELECT 
                        cm.command_id,
                        cm.created_at,
                        cm.delivery_date,
                        c.company_name,
                        u.firstname,
                        u.lastname
                      FROM commands cm
                      INNER JOIN users u ON cm.fk_user_id = u.user_id
                      INNER JOIN companies c ON u.fk_company_id = c.company_id
                      WHERE c.fk_salesman_id = :salesmanId
                      AND cm.fk_status_id = 3
                      ORDER BY cm.created_at ASC";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':salesmanId', $salesmanId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère le nombre de commandes par statut.
     * 
     * Compte les commandes selon leur statut : à préparer (validé=1), envoyées (envoyé=2), livrées (envoyé=2, considérées comme livrées).
     *
     * @return array Tableau avec les compteurs par statut.
     */
    private function getOrdersByStatus(): array {
        try {
            // Commandes à préparer (validé = 1)
            $toPrepareQuery = "SELECT COUNT(*) as total FROM commands WHERE fk_status_id = 1";
            $stmt = $this->conn->prepare($toPrepareQuery);
            $stmt->execute();
            $toPrepareResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Commandes envoyées (envoyé = 2)
            $shippedQuery = "SELECT COUNT(*) as total FROM commands WHERE fk_status_id = 2";
            $stmt = $this->conn->prepare($shippedQuery);
            $stmt->execute();
            $shippedResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Pour cet indicateur, on considère les commandes envoyées comme livrées
            // (la table n'a pas de statut "livré" séparé)
            
            return [
                'toPrepare' => (int)($toPrepareResult['total'] ?? 0),
                'shipped' => (int)($shippedResult['total'] ?? 0),
                'delivered' => (int)($shippedResult['total'] ?? 0), // Même valeur que shipped
            ];
        } catch (PDOException $e) {
            return [
                'toPrepare' => 0,
                'shipped' => 0,
                'delivered' => 0,
            ];
        }
    }

    /**
     * Calcule la performance d'expédition (taux d'expédition à temps).
     * 
     * Calcule le pourcentage de commandes expédiées (statut 2) avant leur date de livraison prévue.
     * Une commande est considérée à temps si sa date de création est antérieure ou égale à delivery_date.
     *
     * @return array Tableau avec le taux et les compteurs.
     */
    private function getShippingPerformance(): array {
        try {
            // Commandes expédiées à temps (created_at <= delivery_date pour statut envoyé)
            // On considère qu'une commande est expédiée à temps si elle passe au statut "envoyé" avant delivery_date
            // Pour simplifier, on compare created_at avec delivery_date pour les commandes envoyées
            $query = "SELECT 
                        COUNT(*) as total_shipped,
                        SUM(CASE WHEN c.created_at <= c.delivery_date THEN 1 ELSE 0 END) as on_time_count
                      FROM commands c
                      WHERE c.fk_status_id = 2";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $totalShipped = (int)($result['total_shipped'] ?? 0);
            $onTimeCount = (int)($result['on_time_count'] ?? 0);
            $onTimeRate = $totalShipped > 0 ? round(($onTimeCount / $totalShipped) * 100, 1) : 0;
            
            return [
                'totalShipped' => $totalShipped,
                'onTimeCount' => $onTimeCount,
                'onTimeRate' => $onTimeRate,
            ];
        } catch (PDOException $e) {
            return [
                'totalShipped' => 0,
                'onTimeCount' => 0,
                'onTimeRate' => 0,
            ];
        }
    }

    /**
     * Calcule le temps moyen de traitement d'une commande.
     * 
     * Calcule la moyenne du temps entre la création et le passage au statut "envoyé".
     * Pour simplifier, on utilise la différence entre created_at et delivery_date pour les commandes envoyées.
     *
     * @return float Temps moyen en heures (ou 0 si aucune donnée).
     */
    private function getAverageProcessingTime(): float {
        try {
            // On utilise la différence entre created_at et delivery_date pour les commandes envoyées
            // comme approximation du temps de traitement
            $query = "SELECT 
                        AVG(TIMESTAMPDIFF(HOUR, c.created_at, c.delivery_date)) as avg_hours
                      FROM commands c
                      WHERE c.fk_status_id = 2";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return round((float)($result['avg_hours'] ?? 0), 1);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    /**
     * Récupère les produits en rupture ou à faible stock.
     * 
     * Seuil par défaut : quantité <= 20. Retourne les 5 premiers produits concernés.
     *
     * @return array Liste des produits avec faible stock.
     */
    private function getLowStockProducts(): array {
        try {
            $threshold = 20; // Seuil de stock bas
            $query = "SELECT 
                        product_id,
                        product_name,
                        quantity,
                        price
                      FROM stock
                      WHERE quantity <= :threshold
                      ORDER BY quantity ASC
                      LIMIT 5";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Calcule la valeur totale du stock.
     * 
     * Calcule la somme de (quantity * price) pour tous les produits.
     *
     * @return float Valeur totale du stock.
     */
    private function getTotalStockValue(): float {
        try {
            $query = "SELECT SUM(quantity * price) as total_value FROM stock";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return round((float)($result['total_value'] ?? 0), 2);
        } catch (PDOException $e) {
            return 0.0;
        }
    }

    /**
     * Récupère la rotation des stocks (produits qui sortent le plus souvent).
     * 
     * Compte le nombre de fois qu'un produit a été commandé via command_details.
     * Retourne le top 5 des produits les plus vendus.
     *
     * @return array Liste des produits avec leur nombre de sorties.
     */
    private function getStockRotation(): array {
        try {
            $query = "SELECT 
                        s.product_id,
                        s.product_name,
                        COUNT(cd.details_id) as exit_count,
                        s.quantity as current_stock
                      FROM stock s
                      LEFT JOIN command_details cd ON s.product_id = cd.fk_product_id
                      GROUP BY s.product_id, s.product_name, s.quantity
                      ORDER BY exit_count DESC
                      LIMIT 5";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère l'historique des mouvements de stock (sorties).
     * 
     * Récupère les dernières sorties de stock via command_details pour les 30 derniers jours.
     * Limité aux 20 dernières sorties pour l'affichage.
     *
     * @return array Liste des mouvements de stock.
     */
    private function getStockMovements(): array {
        try {
            $query = "SELECT 
                        cd.details_id,
                        cd.fk_command_id,
                        cd.fk_product_id,
                        cd.created_at,
                        s.product_name,
                        c.command_id,
                        c.delivery_date,
                        st.status_name
                      FROM command_details cd
                      INNER JOIN stock s ON cd.fk_product_id = s.product_id
                      INNER JOIN commands c ON cd.fk_command_id = c.command_id
                      LEFT JOIN status st ON c.fk_status_id = st.status_id
                      WHERE cd.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                      ORDER BY cd.created_at DESC
                      LIMIT 20";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère les alertes de réapprovisionnement.
     * 
     * Produits dont la quantité est en dessous du seuil de réapprovisionnement (quantité <= 20).
     *
     * @return array Liste des produits nécessitant un réapprovisionnement.
     */
    private function getReorderAlerts(): array {
        try {
            $threshold = 20; // Seuil de réapprovisionnement
            $query = "SELECT 
                        product_id,
                        product_name,
                        quantity,
                        price,
                        (quantity * price) as stock_value
                      FROM stock
                      WHERE quantity <= :threshold
                      ORDER BY quantity ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':threshold', $threshold, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }
}