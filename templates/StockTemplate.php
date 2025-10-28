<?php

namespace Templates;

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
            <form action="/modify-stock" method="POST" class="inline-block">
                <input type="hidden" name="newProduct" value="true">
                <input type="hidden" name="renderAddProduct" value="true">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus text-white"></i>
                    <span>Ajouter un produit</span>
                </button>
            </form>
        </div>

        <!-- Tableau du stock -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            PRODUIT
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            QUANTITÉ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            PRIX UNITAIRE
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            VALEUR TOTALE
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
        HTML;

        // Récupération sécurisée de la liste des produits (tableau vide si absente)
        $products = is_array($datas) ? $datas : [];

        // Vérification si la liste des produits est vide
        if (empty($products)) {
            // Message à afficher lorsqu'il n'y a aucun produit
            $stockContent .= <<<HTML
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-box text-4xl mb-4"></i>
                                <p class="text-lg font-medium">Aucun produit en stock</p>
                                <p class="text-sm mt-2">Commencez par ajouter un produit.</p>
                            </div>
                        </td>
                    </tr>
                HTML;
        } 
        else {
            // Génération dynamique des lignes du tableau pour chaque produit
            foreach ($products as $product) {
                // Calcul de la valeur totale pour chaque produit
                $totalValue = (float)($product['quantity'] ?? 0) * (float)($product['price'] ?? 0);
                
                // Échappement XSS de toutes les valeurs pour éviter les injections
                $productId = htmlspecialchars($product['product_id']);
                $productName = htmlspecialchars(strtoupper($product['product_name']));
                $quantity = htmlspecialchars($product['quantity']);
                $price = number_format((float)($product['price'] ?? 0), 2, ',', ' ') . ' €';
                $valueTotal = number_format($totalValue, 2, ',', ' ') . ' €';
                
                // Classe de couleur selon la quantité en stock
                $quantityClass = 'text-gray-900';
                $quantityBadge = '';
                
                if ($quantity < 10) {
                    $quantityClass = 'text-red-600 font-semibold';
                    $quantityBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 ml-2">Faible</span>';
                } 
                elseif ($quantity < 50) {
                    $quantityClass = 'text-yellow-600 font-semibold';
                    $quantityBadge = '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 ml-2">Moyen</span>';
                }
                
                $stockContent .= <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="submitForm('{$productId}')">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {$productName}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm {$quantityClass}">
                                {$quantity}
                                {$quantityBadge}
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
        }

        $stockContent .= <<<HTML
                </tbody>
            </table>
        </div>

        <!-- Formulaire caché pour soumettre l'ID du produit lors du clic sur une ligne -->
        <!-- Permet de naviguer vers la page de modification sans formulaire visible -->
        <form action="/modify-stock" method="POST" id="product-form">
            <input type="hidden" name="productId" id="productId" value="0" required>
            <input type="hidden" name="renderModifyProduct" id="renderModifyProduct" value="true" required>
        </form>

        <!-- Script JavaScript pour gérer le clic sur les lignes du tableau -->
        <!-- Insère l'ID du produit dans le formulaire et le soumet automatiquement -->
        <script>
            function submitForm(productId) {
                document.getElementById('productId').value = productId;
                document.getElementById('product-form').submit();
            }
        </script>

        HTML;

        return $stockContent;
    }
}

