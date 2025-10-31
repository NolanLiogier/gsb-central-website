<?php

namespace Templates;

use App\Helpers\DashboardService;

/**
 * Classe DashboardTemplate
 * 
 * Gère l'affichage du template du tableau de bord avec statistiques
 * adaptées au rôle de l'utilisateur (client, commercial, logisticien).
 */
class DashboardTemplate {
    /**
     * Service pour le formatage des données du dashboard.
     * 
     * @var DashboardService
     */
    private DashboardService $dashboardService;

    /**
     * Initialise le template avec le service de formatage.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->dashboardService = new DashboardService();
    }
    /**
     * Génère le contenu HTML du tableau de bord selon le rôle.
     * 
     * Affiche des statistiques et graphiques adaptés au rôle :
     * - Client : nombre de commandes et graphique des produits les plus commandés
     * - Commercial : meilleur client, meilleur produit et objectif mensuel
     * - Logisticien : nombre de commandes envoyées
     *
     * @param array $datas Données du tableau de bord selon le rôle.
     * @return string HTML complet du tableau de bord.
     */
    public function displayDashboard(array $datas = []): string {    
        $userRole = $datas['userRole'] ?? '';

        // Affichage selon le rôle
        return match ($userRole) {
            'client' => $this->displayClientDashboard($datas),
            'commercial' => $this->displayCommercialDashboard($datas),
            'logisticien' => $this->displayLogisticianDashboard($datas),
            default => $this->displayErrorDashboard(),
        };
    }

    /**
     * Génère le contenu HTML du tableau de bord pour un client.
     *
     * @param array $datas Données du client.
     * @return string HTML du tableau de bord client.
     */
    private function displayClientDashboard(array $datas): string {
        $formattedData = $this->dashboardService->prepareClientDashboardData($datas);
        
        $totalOrders = $formattedData['totalOrders'];
        $chartData = $formattedData['chartData'];
        $chartLabels = $formattedData['chartLabels'];
        $ordersByStatus = $formattedData['ordersByStatus'];
        $totalAmountFormatted = $formattedData['totalAmountFormatted'];
        $ordersHistory = $formattedData['ordersHistory'];
        
        $ordersInProgress = $ordersByStatus['inProgress'] ?? 0;
        $ordersShipped = $ordersByStatus['shipped'] ?? 0;
        $ordersDelivered = $ordersByStatus['delivered'] ?? 0;

        return <<<HTML
            <div class="space-y-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Tableau de bord</h1>
                
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Total de commandes -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Total de commandes</p>
                                <p class="text-3xl font-bold text-blue-600">{$totalOrders}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Montant total dépensé -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Montant total dépensé</p>
                                <p class="text-3xl font-bold text-green-600">{$totalAmountFormatted}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Graphique des produits les plus commandés -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Produits les plus commandés</h2>
                        {$this->renderClientProductsChart($chartLabels, $chartData)}
                    </div>

                    <!-- Statut des commandes -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Vue d'ensemble des commandes</h2>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between p-4 bg-orange-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-700">En cours</p>
                                    <p class="text-sm text-gray-500">En attente ou validées</p>
                                </div>
                                <div class="text-2xl font-bold text-orange-600">{$ordersInProgress}</div>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-blue-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-700">Expédiées</p>
                                    <p class="text-sm text-gray-500">En transit</p>
                                </div>
                                <div class="text-2xl font-bold text-blue-600">{$ordersShipped}</div>
                            </div>
                            <div class="flex items-center justify-between p-4 bg-green-50 rounded-lg">
                                <div>
                                    <p class="font-medium text-gray-700">Livrées</p>
                                    <p class="text-sm text-gray-500">Commandes terminées</p>
                                </div>
                                <div class="text-2xl font-bold text-green-600">{$ordersDelivered}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique des commandes -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-cyan-500">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Historique de vos commandes</h2>
                    {$this->renderClientOrdersHistoryTable($ordersHistory)}
                </div>
            </div>

            <script src="/public/assets/js/dashboard.js"></script>
        HTML;
    }

    /**
     * Génère le HTML pour le graphique des produits les plus commandés.
     * 
     * Affiche un message si aucune donnée n'est disponible.
     *
     * @param string $chartLabels JSON des labels du graphique.
     * @param string $chartData JSON des données du graphique.
     * @return string HTML du graphique ou message.
     */
    private function renderClientProductsChart(string $chartLabels, string $chartData): string {
        // Décoder pour vérifier si les données sont vides
        $labelsArray = json_decode($chartLabels, true) ?: [];
        $dataArray = json_decode($chartData, true) ?: [];
        
        if (empty($labelsArray) || empty($dataArray)) {
            return <<<HTML
                <div class="flex items-center justify-center h-64">
                    <p class="text-gray-500 text-center">
                        Aucun produit commandé pour le moment.<br>
                        <span class="text-sm">Vos produits les plus commandés apparaîtront ici après vos premières commandes.</span>
                    </p>
                </div>
            HTML;
        }
        
        return <<<HTML
            <canvas id="clientProductsChart" 
                    data-labels='{$chartLabels}' 
                    data-values='{$chartData}'
                    style="max-height: 300px;"></canvas>
        HTML;
    }

    /**
     * Génère le tableau HTML pour l'historique des commandes client.
     *
     * @param array $orders Liste des commandes.
     * @return string HTML du tableau.
     */
    private function renderClientOrdersHistoryTable(array $orders): string {
        if (empty($orders)) {
            return '<p class="text-gray-500 text-center py-4">Aucune commande passée</p>';
        }

        $rows = '';
        foreach ($orders as $index => $order) {
            $commandId = (int)($order['command_id'] ?? 0);
            $createdAt = htmlspecialchars($order['created_at'] ?? '');
            $deliveryDate = htmlspecialchars($order['delivery_date'] ?? '');
            $statusName = htmlspecialchars($order['status_name'] ?? 'N/A');
            $totalAmountFormatted = htmlspecialchars($order['total_amount_formatted'] ?? '0,00 €');
            $productsCount = (int)($order['products_count'] ?? 0);
            
            $dateFormatted = $this->dashboardService->formatDate($createdAt, true);
            $deliveryDateFormatted = $this->dashboardService->formatDate($deliveryDate, false);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-cyan-50';
            
            // Couleur selon le statut
            $statusClass = 'bg-blue-100 text-blue-800';
            $statusId = (int)($order['fk_status_id'] ?? 0);
            if ($statusId === 3) {
                $statusClass = 'bg-yellow-100 text-yellow-800'; // En attente
            } elseif ($statusId === 1) {
                $statusClass = 'bg-orange-100 text-orange-800'; // Validé
            } elseif ($statusId === 2) {
                $statusClass = 'bg-green-100 text-green-800'; // Envoyé/Livré
            }
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-cyan-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">#{$commandId}</td>
                    <td class="py-3 px-4 text-gray-600">{$dateFormatted}</td>
                    <td class="py-3 px-4 text-gray-600">{$deliveryDateFormatted}</td>
                    <td class="py-3 px-4 text-gray-600">{$productsCount} produit(s)</td>
                    <td class="py-3 px-4 text-green-600 font-semibold">{$totalAmountFormatted}</td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full {$statusClass}">{$statusName}</span>
                    </td>
                    <td class="py-3 px-4">
                        <a href="/Commands" class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline">Voir</a>
                    </td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">ID</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Date commande</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Date livraison</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Produits</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Montant</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Statut</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le contenu HTML du tableau de bord pour un commercial.
     *
     * @param array $datas Données du commercial.
     * @return string HTML du tableau de bord commercial.
     */
    private function displayCommercialDashboard(array $datas): string {
        $formattedData = $this->dashboardService->prepareCommercialDashboardData($datas);
        
        $monthlyOrders = $formattedData['monthlyOrders'];
        $monthlyRevenueFormatted = $formattedData['monthlyRevenueFormatted'];
        $revenueEvolution = $formattedData['revenueEvolution'];
        $evolutionClass = $formattedData['evolutionClass'];
        $evolutionIcon = $formattedData['evolutionIcon'];
        $evolutionBgClass = $formattedData['evolutionBgClass'];
        $evolutionMonthsJson = $formattedData['evolutionMonthsJson'];
        $evolutionValuesJson = $formattedData['evolutionValuesJson'];
        $objective = $formattedData['objective'];
        $topClients = $formattedData['topClients'];
        $topProducts = $formattedData['topProducts'];
        $pendingOrders = $formattedData['pendingOrders'];
        
        $currentObjective = $objective['current'];
        $objectiveTotal = $objective['total'];
        $objectivePercentage = $objective['percentage'];

        return <<<HTML
            <div class="space-y-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Tableau de bord commercial</h1>
                
                <!-- KPI Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Nombre de commandes ce mois -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Commandes ce mois</p>
                                <p class="text-3xl font-bold text-blue-600">{$monthlyOrders}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Chiffre d'affaires -->
                    <div class="bg-white rounded-lg shadow p-6 {$evolutionBgClass}">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">CA ce mois</p>
                                <p class="text-3xl font-bold text-gray-800">{$monthlyRevenueFormatted}</p>
                                <p class="text-sm mt-1 font-medium {$evolutionClass}">
                                    <span>{$evolutionIcon}</span> {$revenueEvolution}% vs mois précédent
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Objectif mensuel -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Objectif mensuel</p>
                                <p class="text-2xl font-bold text-purple-600">{$currentObjective} / {$objectiveTotal}</p>
                                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                                    <div class="bg-purple-600 h-2 rounded-full transition-all duration-500" style="width: {$objectivePercentage}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphique évolution CA -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Évolution du chiffre d'affaires (12 derniers mois)</h2>
                    <canvas id="revenueEvolutionChart" 
                            data-months='{$evolutionMonthsJson}' 
                            data-values='{$evolutionValuesJson}'
                            style="max-height: 300px;"></canvas>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top 5 clients par CA -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Top 5 clients par CA</h2>
                        {$this->renderTopClientsTable($topClients)}
                    </div>

                    <!-- Top 5 produits -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Top 5 produits les plus vendus</h2>
                        {$this->renderTopProductsTable($topProducts)}
                    </div>
                </div>

                <!-- Commandes en attente -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Commandes en attente de validation</h2>
                    {$this->renderPendingOrdersTable($pendingOrders)}
                </div>
            </div>

            <script src="/public/assets/js/dashboard.js"></script>
        HTML;
    }

    /**
     * Génère le tableau HTML pour les top clients.
     *
     * @param array $clients Liste des clients.
     * @return string HTML du tableau.
     */
    private function renderTopClientsTable(array $clients): string {
        if (empty($clients)) {
            return '<p class="text-gray-500 text-center py-4">Aucun client avec CA pour le moment</p>';
        }

        $rows = '';
        foreach ($clients as $index => $client) {
            $companyName = htmlspecialchars($client['company_name'] ?? 'N/A');
            $revenue = (float)($client['revenue'] ?? 0);
            $revenueFormatted = $this->dashboardService->formatAmountForTable($revenue);
            $totalOrders = (int)($client['total_orders'] ?? 0);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-green-50';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-green-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">{$companyName}</td>
                    <td class="py-3 px-4 text-green-600 font-semibold">{$revenueFormatted}</td>
                    <td class="py-3 px-4 text-gray-600">{$totalOrders} commande(s)</td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Client</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">CA total</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Commandes</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le tableau HTML pour les top produits.
     *
     * @param array $products Liste des produits.
     * @return string HTML du tableau.
     */
    private function renderTopProductsTable(array $products): string {
        if (empty($products)) {
            return '<p class="text-gray-500 text-center py-4">Aucun produit vendu pour le moment</p>';
        }

        $rows = '';
        foreach ($products as $index => $product) {
            $productName = htmlspecialchars($product['product_name'] ?? 'N/A');
            $totalSold = (int)($product['total_sold'] ?? 0);
            $totalRevenue = (float)($product['total_revenue'] ?? 0);
            $totalRevenueFormatted = $this->dashboardService->formatAmountForTable($totalRevenue);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-orange-50';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-orange-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">{$productName}</td>
                    <td class="py-3 px-4 text-orange-600 font-semibold">{$totalSold} unité(s)</td>
                    <td class="py-3 px-4 text-gray-600">{$totalRevenueFormatted}</td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Produit</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Quantité</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">CA généré</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le tableau HTML pour les commandes en attente.
     *
     * @param array $orders Liste des commandes.
     * @return string HTML du tableau.
     */
    private function renderPendingOrdersTable(array $orders): string {
        if (empty($orders)) {
            return '<p class="text-gray-500 text-center py-4">Aucune commande en attente de validation</p>';
        }

        $rows = '';
        foreach ($orders as $index => $order) {
            $commandId = (int)($order['command_id'] ?? 0);
            $companyName = htmlspecialchars($order['company_name'] ?? 'N/A');
            $firstName = htmlspecialchars($order['firstname'] ?? '');
            $lastName = htmlspecialchars($order['lastname'] ?? '');
            $createdAt = htmlspecialchars($order['created_at'] ?? '');
            $deliveryDate = htmlspecialchars($order['delivery_date'] ?? '');
            
            // Formatage des dates
            $createdDate = $this->dashboardService->formatDate($createdAt, true);
            $deliveryDateFormatted = $this->dashboardService->formatDate($deliveryDate, false);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-yellow-50';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-yellow-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">#{$commandId}</td>
                    <td class="py-3 px-4 text-gray-600">{$companyName}</td>
                    <td class="py-3 px-4 text-gray-600">{$firstName} {$lastName}</td>
                    <td class="py-3 px-4 text-gray-600">{$createdDate}</td>
                    <td class="py-3 px-4 text-gray-600">{$deliveryDateFormatted}</td>
                    <td class="py-3 px-4">
                        <a href="/Commands" class="text-blue-600 hover:text-blue-800 text-sm font-medium hover:underline">Voir la commande</a>
                    </td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">ID</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Entreprise</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Client</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Date création</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Date livraison</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le contenu HTML du tableau de bord pour un logisticien.
     *
     * @param array $datas Données du logisticien.
     * @return string HTML du tableau de bord logisticien.
     */
    private function displayLogisticianDashboard(array $datas): string {
        $formattedData = $this->dashboardService->prepareLogisticianDashboardData($datas);
        
        $ordersToPrepare = $formattedData['ordersToPrepare'];
        $ordersShipped = $formattedData['ordersShipped'];
        $averageProcessingTime = $formattedData['processingTimeFormatted'];
        
        $totalStockValueFormatted = $formattedData['totalStockValueFormatted'];
        $lowStockProducts = $formattedData['lowStockProducts'];
        $stockRotation = $formattedData['stockRotation'];
        $stockMovements = $formattedData['stockMovements'];
        $rotationLabelsJson = $formattedData['rotationLabelsJson'];
        $rotationCountsJson = $formattedData['rotationCountsJson'];

        return <<<HTML
            <div class="space-y-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Tableau de bord logistique</h1>
                
                <!-- KPI Cards - Commandes -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Commandes à préparer -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">À préparer</p>
                                <p class="text-3xl font-bold text-orange-600">{$ordersToPrepare}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Commandes expédiées -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Expédiées</p>
                                <p class="text-3xl font-bold text-blue-600">{$ordersShipped}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KPI Cards - Performance et Stocks -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Temps moyen de traitement -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-indigo-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Temps moyen de traitement</p>
                                <p class="text-3xl font-bold text-indigo-600">{$averageProcessingTime}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Valeur totale du stock -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <p class="text-sm text-gray-500 mb-1">Valeur totale du stock</p>
                                <p class="text-3xl font-bold text-purple-600">{$totalStockValueFormatted}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Top produits en rupture/faible stock -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Produits à faible stock</h2>
                        {$this->renderLowStockProductsTable($lowStockProducts)}
                    </div>

                    <!-- Rotation du stock -->
                    <div class="bg-white rounded-lg shadow p-6 border-l-4 border-teal-500">
                        <h2 class="text-xl font-semibold text-gray-800 mb-4">Rotation du stock (Top 5)</h2>
                        {$this->renderStockRotationTable($stockRotation)}
                    </div>
                </div>

                <!-- Graphique rotation -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-cyan-500">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Graphique de rotation des stocks</h2>
                    <canvas id="stockRotationChart" 
                            data-labels='{$rotationLabelsJson}' 
                            data-values='{$rotationCountsJson}'
                            style="max-height: 300px;"></canvas>
                </div>

                <!-- Historique des mouvements de stock -->
                <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Historique des mouvements (30 derniers jours)</h2>
                    {$this->renderStockMovementsTable($stockMovements)}
                </div>
            </div>

            <script src="/public/assets/js/dashboard.js"></script>
        HTML;
    }

    /**
     * Génère le tableau HTML pour les produits à faible stock.
     *
     * @param array $products Liste des produits.
     * @return string HTML du tableau.
     */
    private function renderLowStockProductsTable(array $products): string {
        if (empty($products)) {
            return '<p class="text-gray-500 text-center py-4">Aucun produit à faible stock</p>';
        }

        $rows = '';
        foreach ($products as $index => $product) {
            $productName = htmlspecialchars($product['product_name'] ?? 'N/A');
            $quantity = (int)($product['quantity'] ?? 0);
            $price = (float)($product['price'] ?? 0);
            $priceFormatted = $this->dashboardService->formatAmountForTable($price);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-red-50';
            
            // Déterminer la classe de couleur selon la quantité
            $quantityClass = $quantity <= 5 ? 'text-red-600 font-bold' : 'text-orange-600';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-red-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">{$productName}</td>
                    <td class="py-3 px-4 {$quantityClass} font-semibold">{$quantity} unité(s)</td>
                    <td class="py-3 px-4 text-gray-600">{$priceFormatted}</td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Produit</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Quantité</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Prix unitaire</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le tableau HTML pour la rotation du stock.
     *
     * @param array $products Liste des produits avec rotation.
     * @return string HTML du tableau.
     */
    private function renderStockRotationTable(array $products): string {
        if (empty($products)) {
            return '<p class="text-gray-500 text-center py-4">Aucune donnée de rotation disponible</p>';
        }

        $rows = '';
        foreach ($products as $index => $product) {
            $productName = htmlspecialchars($product['product_name'] ?? 'N/A');
            $exitCount = (int)($product['exit_count'] ?? 0);
            $currentStock = (int)($product['current_stock'] ?? 0);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-teal-50';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-teal-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">{$productName}</td>
                    <td class="py-3 px-4 text-teal-600 font-semibold">{$exitCount} sortie(s)</td>
                    <td class="py-3 px-4 text-gray-600">Stock : {$currentStock}</td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Produit</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Sorties</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Stock actuel</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le tableau HTML pour les mouvements de stock.
     *
     * @param array $movements Liste des mouvements.
     * @return string HTML du tableau.
     */
    private function renderStockMovementsTable(array $movements): string {
        if (empty($movements)) {
            return '<p class="text-gray-500 text-center py-4">Aucun mouvement de stock récent</p>';
        }

        $rows = '';
        foreach ($movements as $index => $movement) {
            $productName = htmlspecialchars($movement['product_name'] ?? 'N/A');
            $commandId = (int)($movement['command_id'] ?? 0);
            $createdAt = htmlspecialchars($movement['created_at'] ?? '');
            $statusName = htmlspecialchars($movement['status_name'] ?? '');
            $deliveryDate = htmlspecialchars($movement['delivery_date'] ?? '');
            
            $dateFormatted = $this->dashboardService->formatDate($createdAt, true);
            $deliveryDateFormatted = $this->dashboardService->formatDate($deliveryDate, false);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-yellow-50';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-yellow-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">{$productName}</td>
                    <td class="py-3 px-4 text-gray-600">Commande #{$commandId}</td>
                    <td class="py-3 px-4 text-gray-600">{$dateFormatted}</td>
                    <td class="py-3 px-4 text-gray-600">{$deliveryDateFormatted}</td>
                    <td class="py-3 px-4">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">{$statusName}</span>
                    </td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Produit</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Commande</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Date mouvement</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Date livraison</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère le tableau HTML pour les alertes de réapprovisionnement.
     *
     * @param array $alerts Liste des alertes.
     * @return string HTML du tableau.
     */
    private function renderReorderAlertsTable(array $alerts): string {
        if (empty($alerts)) {
            return '<p class="text-gray-500 text-center py-4">Aucune alerte de réapprovisionnement</p>';
        }

        $rows = '';
        foreach ($alerts as $index => $alert) {
            $productName = htmlspecialchars($alert['product_name'] ?? 'N/A');
            $quantity = (int)($alert['quantity'] ?? 0);
            $price = (float)($alert['price'] ?? 0);
            $stockValue = (float)($alert['stock_value'] ?? 0);
            $priceFormatted = $this->dashboardService->formatAmountForTable($price);
            $stockValueFormatted = $this->dashboardService->formatAmountForTable($stockValue);
            $rowBgClass = $index % 2 === 0 ? 'bg-white' : 'bg-red-50';
            
            // Déterminer le niveau d'alerte
            $alertClass = $quantity <= 5 ? 'bg-red-600 text-white' : 'bg-orange-500 text-white';
            $alertText = $quantity <= 5 ? 'Rupture imminente' : 'Stock faible';
            
            $rows .= <<<HTML
                <tr class="border-b border-gray-200 hover:bg-red-100 {$rowBgClass} transition-colors">
                    <td class="py-3 px-4 text-gray-800 font-medium">{$productName}</td>
                    <td class="py-3 px-4 text-red-600 font-bold">{$quantity} unité(s)</td>
                    <td class="py-3 px-4 text-gray-600">{$priceFormatted}</td>
                    <td class="py-3 px-4 text-gray-600">{$stockValueFormatted}</td>
                    <td class="py-3 px-4">
                        <span class="px-3 py-1 text-xs font-semibold rounded-full {$alertClass}">{$alertText}</span>
                    </td>
                </tr>
            HTML;
        }

        return <<<HTML
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="border-b border-gray-300">
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Produit</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Quantité</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Prix unitaire</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Valeur stock</th>
                            <th class="py-2 px-4 text-left text-sm font-semibold text-gray-700">Alerte</th>
                        </tr>
                    </thead>
                    <tbody>
                        {$rows}
                    </tbody>
                </table>
            </div>
        HTML;
    }

    /**
     * Génère un message d'erreur si le rôle n'est pas reconnu.
     *
     * @return string HTML du message d'erreur.
     */
    private function displayErrorDashboard(): string {
        return <<<HTML
            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h2 class="text-xl font-semibold text-red-700 mb-2">Erreur</h2>
                <p class="text-red-600">Rôle non reconnu ou données manquantes.</p>
            </div>
        HTML;
    }
}