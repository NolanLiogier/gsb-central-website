<?php

namespace Templates;

/**
 * Classe DashboardTemplate
 * 
 * Gère l'affichage du template du tableau de bord avec statistiques
 * adaptées au rôle de l'utilisateur (client, commercial, logisticien).
 */
class DashboardTemplate {
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
        $totalOrders = $datas['totalOrders'] ?? 0;
        $mostOrderedProducts = $datas['mostOrderedProducts'] ?? [];

        // Préparation des données pour le graphique
        $products = array_column($mostOrderedProducts, 'product_name');
        $quantities = array_column($mostOrderedProducts, 'total_ordered');
        
        $chartData = json_encode($quantities);
        $chartLabels = json_encode($products);

        return <<<HTML
            <div class="space-y-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Tableau de bord</h1>
                
                <!-- Carte du nombre de commandes -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700">Total de commandes</h2>
                            <p class="text-gray-500 mt-1">Nombre total de commandes passées</p>
                        </div>
                        <div class="text-5xl font-bold text-blue-600">{$totalOrders}</div>
                    </div>
                </div>

                <!-- Graphique des produits les plus commandés -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Produits les plus commandés</h2>
                    <canvas id="clientProductsChart" style="max-height: 300px;"></canvas>
                </div>
            </div>

            <script>
                const productsCtx = document.getElementById('clientProductsChart');
                new Chart(productsCtx, {
                    type: 'bar',
                    data: {
                        labels: {$chartLabels},
                        datasets: [{
                            label: 'Quantité commandée',
                            data: {$chartData},
                            backgroundColor: 'rgba(59, 130, 246, 0.5)',
                            borderColor: 'rgba(59, 130, 246, 1)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                display: false
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            </script>
        HTML;
    }

    /**
     * Génère le contenu HTML du tableau de bord pour un commercial.
     *
     * @param array $datas Données du commercial.
     * @return string HTML du tableau de bord commercial.
     */
    private function displayCommercialDashboard(array $datas): string {
        $bestClient = $datas['bestClient'] ?? [];
        $bestProduct = $datas['bestProduct'] ?? [];
        $monthlyObjective = $datas['monthlyObjective'] ?? [];

        $clientName = htmlspecialchars($bestClient['company_name'] ?? 'Aucun');
        $clientOrders = (int)($bestClient['total_orders'] ?? 0);
        $productName = htmlspecialchars($bestProduct['product_name'] ?? 'Aucun');
        $productSales = (int)($bestProduct['total_sold'] ?? 0);
        
        $currentObjective = (int)($monthlyObjective['current'] ?? 0);
        $objectiveTotal = (int)($monthlyObjective['objective'] ?? 0);
        $objectivePercentage = (float)($monthlyObjective['percentage'] ?? 0);

        return <<<HTML
            <div class="space-y-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Tableau de bord</h1>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Meilleur client -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Meilleur client</h2>
                        <div class="text-3xl font-bold text-green-600 mb-2">{$clientName}</div>
                        <p class="text-gray-600">{$clientOrders} commande(s) au total</p>
                    </div>

                    <!-- Meilleur produit -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h2 class="text-xl font-semibold text-gray-700 mb-4">Meilleur produit</h2>
                        <div class="text-3xl font-bold text-blue-600 mb-2">{$productName}</div>
                        <p class="text-gray-600">{$productSales} vente(s) au total</p>
                    </div>
                </div>

                <!-- Objectif mensuel -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-semibold text-gray-700 mb-4">Objectif mensuel</h2>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-600">Progression</span>
                        <span class="text-gray-900 font-semibold">{$objectivePercentage}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-4 mb-2">
                        <div class="bg-blue-600 h-4 rounded-full transition-all duration-500" style="width: {$objectivePercentage}%"></div>
                    </div>
                    <p class="text-gray-600">{$currentObjective} / {$objectiveTotal} commandes ce mois</p>
                </div>
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
        $sentOrders = $datas['sentOrders'] ?? 0;

        return <<<HTML
            <div class="space-y-6">
                <h1 class="text-3xl font-bold text-gray-800 mb-6">Tableau de bord</h1>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-700">Commandes envoyées</h2>
                            <p class="text-gray-500 mt-1">Nombre total de commandes expédiées</p>
                        </div>
                        <div class="text-5xl font-bold text-green-600">{$sentOrders}</div>
                    </div>
                </div>
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