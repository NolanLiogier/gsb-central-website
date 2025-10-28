<?php

namespace Templates;

/**
 * Classe ModifyStockTemplate
 * 
 * Gère l'affichage du template de modification de produit en stock.
 * Crée un formulaire complet pour modifier les informations d'un produit :
 * nom, quantité et prix. Gère la validation des formats.
 */
class ModifyStockTemplate {
    /**
     * Génère le contenu HTML du formulaire de modification de produit.
     * 
     * Crée un formulaire avec validation des champs quantité (nombre positif)
     * et prix (nombre décimal positif). Inclut des boutons d'annulation
     * et de sauvegarde.
     *
     * @param array $datas Données du produit (product_id, product_name, quantity, price).
     * @return string HTML complet du formulaire de modification.
     */
    public function displayModifyStock(array $datas = []): string {

        if (empty($datas)) {
            $datas = [];
        }

        $productName = htmlspecialchars($datas['product_name'] ?? '');
        $quantity = htmlspecialchars($datas['quantity'] ?? '0');
        $price = htmlspecialchars($datas['price'] ?? '0');
        $productIdValue = htmlspecialchars($datas['product_id'] ?? 0);

        // Détection du mode et récupération des textes appropriés
        $modeData = $this->determineMode($datas);
        $isEditMode = $modeData['isEditMode'];
        $pageTitle = $modeData['pageTitle'];
        $buttonText = $modeData['buttonText'];

        $modifyStockContent = <<<HTML
        <!-- En-tête de la page -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">{$pageTitle}</h1>
        </div>

        <!-- Formulaire de modification de produit -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <!-- En-tête du formulaire -->
            <div class="bg-white px-8 py-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Informations du produit</h2>
                    </div>
        HTML;

        // Ajout du bouton supprimer en mode modification uniquement
        if ($isEditMode) {
            $modifyStockContent .= <<<HTML
                        <form method="POST" action="/modify-stock" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');" class="inline-block">
                            <input type="hidden" name="productId" value="{$productIdValue}">
                            <input type="hidden" name="deleteProduct" value="true">
                            <button 
                                type="submit" 
                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-red-700 rounded-lg hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer
                            </button>
                        </form>
        HTML;
        } else {
            $modifyStockContent .= <<<HTML
                        <div></div>
        HTML;
        }

        $modifyStockContent .= <<<HTML
                </div>
            </div>
            
            <form class="p-8" method="POST" action="/modify-stock">
                <!-- Champ caché pour détecter le mode (modification ou création) -->
        HTML;

        // Génération des champs cachés selon le mode (modification ou création)
        $hiddenFields = $this->generateHiddenFields($isEditMode, $productIdValue);

        $modifyStockContent .= $hiddenFields;
        $modifyStockContent .= <<<HTML

                <!-- Grille des champs du formulaire -->
                <div class="space-y-6">
                    <!-- Nom du produit -->
                    <div>
                        <label for="productName" class="block text-sm font-semibold text-gray-700 mb-3">
                            Nom du produit <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="productName" 
                            name="productName" 
                            value="{$productName}"
                            required
                            maxlength="100"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                            placeholder="Entrez le nom du produit">
                    </div>

                    <!-- Grille pour Quantité et Prix -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Quantité -->
                        <div>
                            <label for="quantity" class="block text-sm font-semibold text-gray-700 mb-3">
                                Quantité <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="number" 
                                id="quantity" 
                                name="quantity" 
                                value="{$quantity}"
                                required
                                min="0"
                                step="1"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                                placeholder="0">
                            <p class="text-xs text-gray-500 mt-2">La quantité doit être un nombre positif</p>
                        </div>

                        <!-- Prix -->
                        <div>
                            <label for="price" class="block text-sm font-semibold text-gray-700 mb-3">
                                Prix unitaire (€) <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="price" 
                                name="price" 
                                value="{$price}"
                                required
                                pattern="^\d+\.\d{2}$"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                                placeholder="0.00">
                            <p class="text-xs text-gray-500 mt-2">Le prix doit être au format XX.XX (ex: 10.50)</p>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
                    <a href="/stock" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Retour</a>
                    <button 
                        type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-sm">
                        {$buttonText}
                    </button>
                </div>
            </form>
        </div>

HTML;

        return $modifyStockContent;
    }

    /**
     * Détermine si on est en mode modification ou création et retourne les textes appropriés.
     * 
     * Analyse les données fournies pour savoir si un produit existe déjà (mode modification)
     * ou si on est en train de créer un nouveau produit (mode création).
     * Retourne un tableau avec les informations nécessaires (mode, titres, textes des boutons).
     *
     * @param array $datas Données du produit avec éventuellement un product_id.
     * @return array Tableau contenant ['isEditMode', 'pageTitle', 'buttonText'].
     */
    private function determineMode(array $datas): array {
        $isEditMode = false;
        if (!empty($datas['product_id']) && $datas['product_id'] != '0') {
            $isEditMode = true;
        }

        $modeData = [
            'isEditMode' => $isEditMode,
            'pageTitle' => "Ajouter un produit",
            'buttonText' => "Créer le produit"
        ];

        if ($isEditMode) {
            $modeData = [
                'isEditMode' => $isEditMode,
                'pageTitle' => "Modifier le produit",
                'buttonText' => "Enregistrer les modifications"
            ];
        }

        return $modeData;
    }

    /**
     * Génère les champs cachés du formulaire selon le mode (modification ou création).
     * 
     * Détecte si on est en mode modification (avec ID) ou création (sans ID) et génère
     * les champs cachés appropriés pour que le contrôleur traite correctement la requête.
     *
     * @param bool $isEditMode True si en mode modification, false si en mode création.
     * @param string|int $productId ID du produit (échappé pour l'affichage).
     * @return string Champs cachés HTML générés.
     */
    private function generateHiddenFields(bool $isEditMode, $productId): string {
        if ($isEditMode) {
            // Mode modification : on transmet l'ID et le flag de mise à jour
            return <<<HTML

                <input type="hidden" name="productId" value="{$productId}">
                <input type="hidden" name="updateProduct" value="true">
            HTML;
        } else {
            // Mode création : on transmet le flag de nouveau produit et de création
            return <<<HTML

                <input type="hidden" name="newProduct" value="true">
                <input type="hidden" name="createProduct" value="true">
            HTML;
        }
    }
}

