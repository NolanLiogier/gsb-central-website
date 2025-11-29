<?php

namespace Templates;

use Templates\widgets\TableWidget;

/**
 * Classe StockTemplate
 * 
 * Gère l'affichage du template de la page de liste du stock.
 * Crée un tableau interactif affichant les produits avec leurs quantités
 * et prix. Gère le clic sur une ligne pour accéder à
 * la modification du produit.
 */
class StockTemplate {
    /**
     * Génère le contenu HTML de la page de liste du stock.
     * 
     * Crée un tableau avec tous les produits récupérés, affiche
     * leurs informations (nom, quantité, prix) et ajoute un formulaire
     * caché pour gérer les clics sur les lignes du tableau.
     *
     * @param array $datas Tableau contenant la liste des produits.
     * @return string HTML complet de la page de liste du stock.
     */
    public function displayStock(array $datas = []): string {
        $stockContent = <<<HTML
        <!-- Titre de la page et bouton d'ajout -->
        <div class="mb-8 flex justify-between items-center">
            <h1 class="text-4xl font-bold text-gray-800">Stock</h1>
            <form action="/ModifyStock" method="POST" class="inline-block">
                <input type="hidden" name="newProduct" value="true">
                <input type="hidden" name="renderAddProduct" value="true">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus text-white"></i>
                    <span>Ajouter un produit</span>
                </button>
            </form>
        </div>
        
        <!-- Champ de recherche -->
        <div class="mb-6">
            <div class="relative">
                <input type="text" 
                       id="product-search" 
                       placeholder="Rechercher par nom de produit..." 
                       class="w-full md:w-1/3 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
HTML;

        // Récupération sécurisée de la liste des produits (tableau vide si absente)
        $products = is_array($datas) ? $datas : [];

        // Génération des en-têtes
        $headers = ['PRODUIT', 'QUANTITÉ', 'PRIX UNITAIRE', 'VALEUR TOTALE'];

        // Utilisation du TableWidget pour générer le tableau
        $tableWidget = new TableWidget();
        $tableHtml = $tableWidget->render([
            'headers' => $headers,
            'rows' => $products,
            'itemsPerPage' => 10,
            'baseUrl' => '/Stock',
            'emptyMessage' => 'Aucun produit en stock',
            'emptyIcon' => 'fa-box',
            'rowCallback' => function($product) {
                return $this->generateProductRow($product);
            }
        ]);

        $stockContent .= $tableHtml;

        $stockContent .= <<<HTML

        <!-- Formulaire caché pour soumettre l'ID du produit lors du clic sur une ligne -->
        <!-- Permet de naviguer vers la page de modification sans formulaire visible -->
        <form action="/ModifyStock" method="POST" id="product-form">
            <input type="hidden" name="productId" id="productId" value="0" required>
            <input type="hidden" name="renderModifyProduct" id="renderModifyProduct" value="true" required>
        </form>

        <!-- Script JavaScript pour gérer le clic sur les lignes du tableau -->
        <!-- Insère l'ID du produit dans le formulaire et le soumet automatiquement -->
        <script src="/public/assets/js/table-search.js"></script>
        <script>
            function submitForm(productId) {
                document.getElementById('productId').value = productId;
                document.getElementById('product-form').submit();
            }
            
            // Initialisation de la recherche (recherche dans la première colonne : nom de produit)
            document.addEventListener('DOMContentLoaded', function() {
                initTableSearch('product-search', 'table', [0]);
            });
        </script>

        HTML;

        return $stockContent;
    }

    /**
     * Génère une ligne du tableau pour un produit.
     * 
     * Crée une ligne de tableau avec les informations du produit :
     * nom, quantité (avec badge de statut), prix unitaire et valeur totale.
     *
     * @param array $product Données du produit à afficher.
     * @return string HTML de la ligne de produit générée.
     */
    private function generateProductRow(array $product): string
    {
        // Calcul de la valeur totale pour chaque produit
        $totalValue = (float)($product['quantity'] ?? 0) * (float)($product['price'] ?? 0);
        
        // Échappement XSS de toutes les valeurs pour éviter les injections
        $productId = htmlspecialchars($product['product_id']);
        $productIdJson = json_encode($product['product_id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        // Utilisation de mb_strtoupper pour gérer correctement les caractères accentués (é → É, à → À, etc.)
        $productName = htmlspecialchars(mb_strtoupper($product['product_name'], 'UTF-8'));
        $quantity = htmlspecialchars($product['quantity']);
        $price = number_format((float)($product['price'] ?? 0), 2, ',', ' ') . ' €';
        $valueTotal = number_format($totalValue, 2, ',', ' ') . ' €';
        
        // Génération des classes et badges selon la quantité en stock
        $quantityData = $this->formatQuantityDisplay($quantity);
        
        return <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="submitForm({$productIdJson})">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {$productName}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm {$quantityData['class']}">
                                {$quantity}
                                {$quantityData['badge']}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$price}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">
                                {$valueTotal}
                            </div>
                        </td>
                    </tr>
HTML;
    }

    /**
     * Génère les classes CSS et badge selon la quantité en stock.
     * 
     * Détermine le style et le badge à afficher selon le niveau de stock :
     * - Faible (< 10) : rouge
     * - Moyen (< 50) : jaune
     * - Normal (>= 50) : gris
     *
     * @param int|string $quantity Quantité en stock.
     * @return array Tableau contenant ['class' => string, 'badge' => string].
     */
    private function formatQuantityDisplay($quantity): array
    {
        $quantityInt = (int)$quantity;
        $class = 'text-gray-900';
        $badge = '';
        
        if ($quantityInt < 10) {
            $class = 'text-red-600 font-semibold';
            $badge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">Faible</span>';
        } elseif ($quantityInt < 50) {
            $class = 'text-yellow-600 font-semibold';
            $badge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">Moyen</span>';
        }
        
        return [
            'class' => $class,
            'badge' => $badge
        ];
    }
}
