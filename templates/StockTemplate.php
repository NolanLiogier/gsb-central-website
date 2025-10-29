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
            <form action="/ModifyStock" method="POST" class="inline-block">
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
            $stockContent .= $this->generateEmptyState();
        } else {
            // Génération dynamique des lignes du tableau pour chaque produit
            $stockContent .= $this->generateProductRows($products);
        }

        $stockContent .= <<<HTML
                </tbody>
            </table>
        </div>

        <!-- Formulaire caché pour soumettre l'ID du produit lors du clic sur une ligne -->
        <!-- Permet de naviguer vers la page de modification sans formulaire visible -->
        <form action="/ModifyStock" method="POST" id="product-form">
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

    /**
     * Génère le message d'état vide lorsqu'il n'y a aucun produit.
     * 
     * Crée une ligne de tableau avec un message informatif centré.
     *
     * @return string HTML de l'état vide généré.
     */
    private function generateEmptyState(): string
    {
        return <<<HTML
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

    /**
     * Génère les lignes du tableau pour chaque produit.
     * 
     * Crée une ligne de tableau avec les informations du produit :
     * nom, quantité (avec badge de statut), prix unitaire et valeur totale.
     *
     * @param array $products Liste des produits à afficher.
     * @return string HTML des lignes de produits générées.
     */
    private function generateProductRows(array $products): string
    {
        $html = '';
        
        foreach ($products as $product) {
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
            
            $html .= <<<HTML
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
        
        return $html;
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
