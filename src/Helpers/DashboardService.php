<?php

namespace App\Helpers;

/**
 * Classe DashboardService
 * 
 * Service pour la préparation et le formatage des données du tableau de bord.
 * Centralise toute la logique de formatage pour les différents types de dashboards
 * (client, commercial, logisticien) afin de garder les templates propres.
 */
class DashboardService {
    /**
     * Prépare les données formatées pour le dashboard commercial.
     * 
     * Formate les montants, dates, couleurs et prépare les données JSON pour les graphiques.
     *
     * @param array $datas Données brutes du commercial depuis le repository.
     * @return array Données formatées prêtes pour l'affichage.
     */
    public function prepareCommercialDashboardData(array $datas): array {
        // Extraction des données
        $monthlyOrders = (int)($datas['monthlyOrders'] ?? 0);
        $monthlyRevenue = (float)($datas['monthlyRevenue'] ?? 0);
        $revenueEvolution = (float)($datas['revenueEvolution'] ?? 0);
        $monthlyObjective = $datas['monthlyObjective'] ?? [];
        $revenueEvolutionData = $datas['revenueEvolutionData'] ?? [];
        
        // Formatage des montants
        $monthlyRevenueFormatted = number_format($monthlyRevenue, 2, ',', ' ') . ' €';
        
        // Calcul des classes CSS pour l'évolution
        $evolution = $this->getEvolutionStyles($revenueEvolution);
        
        // Préparation des données pour le graphique
        $evolutionMonths = array_column($revenueEvolutionData, 'month');
        $evolutionValues = array_column($revenueEvolutionData, 'revenue');
        $evolutionMonthsJson = json_encode(array_map(function($month) {
            return date('M Y', strtotime($month . '-01'));
        }, $evolutionMonths));
        $evolutionValuesJson = json_encode($evolutionValues);
        
        // Formatage de l'objectif
        $objective = [
            'current' => (int)($monthlyObjective['current'] ?? 0),
            'total' => (int)($monthlyObjective['objective'] ?? 0),
            'percentage' => (float)($monthlyObjective['percentage'] ?? 0),
        ];
        
        return [
            'monthlyOrders' => $monthlyOrders,
            'monthlyRevenueFormatted' => $monthlyRevenueFormatted,
            'revenueEvolution' => $revenueEvolution,
            'evolutionClass' => $evolution['textClass'],
            'evolutionIcon' => $evolution['icon'],
            'evolutionBgClass' => $evolution['bgClass'],
            'evolutionMonthsJson' => $evolutionMonthsJson,
            'evolutionValuesJson' => $evolutionValuesJson,
            'objective' => $objective,
            'topClients' => $datas['topClients'] ?? [],
            'topProducts' => $datas['topProducts'] ?? [],
        ];
    }

    /**
     * Prépare les données formatées pour le dashboard client.
     * 
     * Formate les données pour les graphiques, statistiques et montants client.
     *
     * @param array $datas Données brutes du client depuis le repository.
     * @return array Données formatées prêtes pour l'affichage.
     */
    public function prepareClientDashboardData(array $datas): array {
        $totalOrders = (int)($datas['totalOrders'] ?? 0);
        $mostOrderedProducts = $datas['mostOrderedProducts'] ?? [];
        $ordersByStatus = $datas['ordersByStatus'] ?? [];
        $totalAmountSpent = (float)($datas['totalAmountSpent'] ?? 0);
        
        // Préparation des données pour le graphique
        $products = [];
        $quantities = [];
        
        if (!empty($mostOrderedProducts)) {
            $products = array_column($mostOrderedProducts, 'product_name');
            $quantities = array_column($mostOrderedProducts, 'total_ordered');
            // Convertir les quantités en entiers pour le graphique
            $quantities = array_map('intval', $quantities);
        }
        
        // Formatage du montant total
        $totalAmountFormatted = number_format($totalAmountSpent, 2, ',', ' ') . ' €';
        
        return [
            'totalOrders' => $totalOrders,
            'chartLabels' => json_encode($products, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            'chartData' => json_encode($quantities, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP),
            'ordersByStatus' => $ordersByStatus,
            'totalAmountSpent' => $totalAmountSpent,
            'totalAmountFormatted' => $totalAmountFormatted,
        ];
    }

    /**
     * Prépare les données formatées pour le dashboard logisticien.
     * 
     * Formate les données pour les statistiques logisticien : KPI commandes, stocks,
     * formatage des montants, dates et préparation des données pour graphiques.
     *
     * @param array $datas Données brutes du logisticien depuis le repository.
     * @return array Données formatées prêtes pour l'affichage.
     */
    public function prepareLogisticianDashboardData(array $datas): array {
        // KPI commandes
        $ordersToPrepare = (int)($datas['ordersToPrepare'] ?? 0);
        $ordersShipped = (int)($datas['ordersShipped'] ?? 0);
        $averageProcessingTime = (float)($datas['averageProcessingTime'] ?? 0);
        
        // Formatage du temps moyen (heures -> jours si > 24h)
        $processingTimeFormatted = $this->formatProcessingTime($averageProcessingTime);
        
        // KPI stocks
        $lowStockProducts = $datas['lowStockProducts'] ?? [];
        $totalStockValue = (float)($datas['totalStockValue'] ?? 0);
        $stockRotation = $datas['stockRotation'] ?? [];
        
        // Formatage de la valeur du stock
        $totalStockValueFormatted = number_format($totalStockValue, 2, ',', ' ') . ' €';
        
        // Préparation des données pour graphique de rotation (si nécessaire)
        $rotationLabels = array_column($stockRotation, 'product_name');
        $rotationCounts = array_column($stockRotation, 'exit_count');
        $rotationLabelsJson = json_encode($rotationLabels);
        $rotationCountsJson = json_encode($rotationCounts);
        
        return [
            // Commandes
            'ordersToPrepare' => $ordersToPrepare,
            'ordersShipped' => $ordersShipped,
            'averageProcessingTime' => $averageProcessingTime,
            'processingTimeFormatted' => $processingTimeFormatted,
            // Stocks
            'lowStockProducts' => $lowStockProducts,
            'totalStockValue' => $totalStockValue,
            'totalStockValueFormatted' => $totalStockValueFormatted,
            'stockRotation' => $stockRotation,
            // Graphiques
            'rotationLabelsJson' => $rotationLabelsJson,
            'rotationCountsJson' => $rotationCountsJson,
        ];
    }
    
    /**
     * Formate le temps de traitement pour l'affichage.
     * 
     * Convertit les heures en jours si > 24h, sinon affiche en heures.
     *
     * @param float $hours Nombre d'heures.
     * @return string Temps formaté (ex: "2.5 jours" ou "12 heures").
     */
    private function formatProcessingTime(float $hours): string {
        if ($hours >= 24) {
            $days = round($hours / 24, 1);
            return $days . ' jour' . ($days > 1 ? 's' : '');
        }
        return round($hours, 1) . ' heure' . ($hours > 1 ? 's' : '');
    }

    /**
     * Détermine les styles CSS pour l'affichage de l'évolution.
     * 
     * Retourne les classes de texte, icône et fond selon si l'évolution est positive ou négative.
     *
     * @param float $evolution Pourcentage d'évolution.
     * @return array Tableau avec 'textClass', 'icon' et 'bgClass'.
     */
    private function getEvolutionStyles(float $evolution): array {
        if ($evolution >= 0) {
            return [
                'textClass' => 'text-green-600',
                'icon' => '↑',
                'bgClass' => 'bg-white border-l-4 border-green-500',
            ];
        }
        
        return [
            'textClass' => 'text-red-600',
            'icon' => '↓',
            'bgClass' => 'bg-white border-l-4 border-red-500',
        ];
    }

    /**
     * Formate un montant pour l'affichage dans les tableaux.
     *
     * @param float $amount Montant à formater.
     * @return string Montant formaté.
     */
    public function formatAmountForTable(float $amount): string {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    /**
     * Formate une date pour l'affichage.
     *
     * @param string $date Date au format MySQL (YYYY-MM-DD HH:MM:SS).
     * @param bool $includeTime Inclure l'heure ou non.
     * @return string Date formatée (ex: "28/10/2025 15:30" ou "28/10/2025").
     */
    public function formatDate(string $date, bool $includeTime = false): string {
        if (empty($date)) {
            return 'N/A';
        }
        
        $format = $includeTime ? 'd/m/Y H:i' : 'd/m/Y';
        return date($format, strtotime($date));
    }
}

