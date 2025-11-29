<?php

namespace Templates;

use Templates\widgets\ProductWidget;

/**
 * Classe ModifyCommandsTemplate
 * 
 * Gère l'affichage du template de modification de commande sur une seule page :
 * - Sélection des produits
 * - Récapitulatif de la commande
 * - Informations de livraison
 */
class ModifyCommandsTemplate {
    /**
     * Génère le contenu HTML complet de la page de modification de commande.
     * 
     * Affiche tout sur une seule page : sélection des produits, récapitulatif et informations de livraison.
     *
     * @param array $datas Données de la commande (command_id, delivery_date, fk_status_id, created_at, products, selectedProducts).
     * @return string HTML complet du formulaire.
     */
    public function displayModifyCommands(array $datas = []): string {
        if (empty($datas)) {
            $datas = [];
        }

        // Préparation des données pour l'affichage
        $commandId = htmlspecialchars($datas['command_id'] ?? 0);
        
        // Date par défaut: demain à midi
        if (isset($datas['delivery_date'])) {
            $deliveryDate = htmlspecialchars(date('Y-m-d\TH:i', strtotime($datas['delivery_date'])));
        } 
        else {
            // Créer une nouvelle commande: demain à 12:00
            $tomorrow = strtotime('+1 day');
            $deliveryDate = date('Y-m-d', $tomorrow) . 'T12:00';
        }
        
        $statusId = htmlspecialchars($datas['fk_status_id'] ?? '1');
        $createdAt = isset($datas['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($datas['created_at']))) : '-';

        // Détection du mode (modification ou création)
        $isEditMode = !empty($commandId) && $commandId != '0';
        
        // Récupération des données d'adresse structurées ou texte simple
        $deliveryAddressData = $datas['delivery_address_data'] ?? null;
        $deliveryStreet = htmlspecialchars($deliveryAddressData['street'] ?? '');
        $deliveryCity = htmlspecialchars($deliveryAddressData['city'] ?? '');
        $deliveryPostalCode = htmlspecialchars($deliveryAddressData['postal_code'] ?? '');
        $deliveryCountry = htmlspecialchars($deliveryAddressData['country'] ?? 'France');
        $deliveryAdditionalInfo = htmlspecialchars($deliveryAddressData['additional_info'] ?? '');
        
        // Fallback pour l'adresse texte simple (compatibilité)
        if (empty($deliveryStreet) && empty($deliveryCity)) {
            $simpleAddress = '';
            if ($isEditMode) {
                $simpleAddress = htmlspecialchars($datas['delivery_address'] ?? '');
            } else {
                $simpleAddress = htmlspecialchars($datas['company_delivery_address'] ?? '');
            }
            // Si on a une adresse texte simple, essayer de la parser
            if (!empty($simpleAddress)) {
                // Parser simple : format attendu "rue, code postal ville, pays"
                $parts = array_map('trim', explode(',', $simpleAddress));
                if (count($parts) >= 2) {
                    $deliveryStreet = $parts[0];
                    $cityPostalParts = explode(' ', $parts[1], 2);
                    if (count($cityPostalParts) == 2) {
                        $deliveryPostalCode = $cityPostalParts[0];
                        $deliveryCity = $cityPostalParts[1];
                    } else {
                        $deliveryCity = $parts[1];
                    }
                    if (isset($parts[2])) {
                        $deliveryCountry = $parts[2];
                    }
                } else {
                    $deliveryStreet = $simpleAddress;
                }
            }
        }

        // Récupération des statuts dynamiquement depuis la base de données
        $statusList = $datas['statusList'] ?? [];

        $pageTitle = $isEditMode ? "Modifier la commande" : "Créer une commande";
        $actionUrl = "/ModifyCommand";

        // Récupération des produits sélectionnés (depuis POST ou depuis la commande existante)
        $selectedProducts = $datas['selectedProducts'] ?? [];
        
        // Si pas de produits sélectionnés depuis POST, construire depuis les produits de la commande
        if (empty($selectedProducts) && isset($datas['products'])) {
            foreach ($datas['products'] as $product) {
                $productId = $product['product_id'];
                $quantity = (int)($product['ordered_quantity'] ?? 0);
                if ($quantity > 0) {
                    $selectedProducts[$productId] = ['quantity' => $quantity];
                }
            }
        }

        // Récupération des informations utilisateur
        $currentUser = $datas['currentUser'] ?? [];
        $userFunctionId = $currentUser['fk_function_id'] ?? null;
        $userAttributes = $this->generateUserAttributes($userFunctionId);
        
        // Récupération des produits depuis les données
        $products = $datas['products'] ?? [];
        
        // Construction de l'URL de base pour la pagination
        $paginationBaseUrl = $this->buildPaginationUrl($actionUrl, $isEditMode, $commandId);
        
        // Génération des données JSON des produits pour JavaScript
        $productsJson = $this->generateProductsJson($products);

        // Génération des boutons d'action (masqués pour les logisticiens)
        $isLogistician = ($userFunctionId == 3);
        $submitButton = $this->generateSubmitButton($userAttributes['isReadOnly']);

        $modifyCommandContent = <<<HTML
<!-- En-tête de la page -->
<div class="mb-8">
    <h1 class="text-4xl font-bold text-gray-800">{$pageTitle}</h1>
    <p class="text-gray-600 mt-2">Sélectionnez les produits et complétez les informations de livraison</p>
</div>

<!-- Formulaire principal -->
<form method="POST" action="{$actionUrl}" id="product-selection-form">
    <!-- Champs cachés pour l'ID de la commande et le flag de mise à jour -->
HTML;
        
        $modifyCommandContent .= $this->generateHiddenFields($isEditMode, $commandId);
        
        // Génération des cartes de produits avec pagination JavaScript dynamique
        $productWidget = new ProductWidget();
        
        $productsHtml = $productWidget->render([
            'products' => $products,
            'itemsPerPage' => 6,
            'baseUrl' => $paginationBaseUrl,
            'userAttributes' => $userAttributes
        ]);

        $modifyCommandContent .= <<<HTML

    <!-- Sélection des produits -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-semibold text-gray-800">Sélectionner les produits</h2>
        </div>
        
        {$productsHtml}
    </div>

    <!-- Récapitulatif de la commande -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Récapitulatif de la commande</h2>
        
        <div id="order-summary" class="space-y-3">
            {$this->generateOrderSummary($selectedProducts, $products)}
        </div>
        
        <div class="border-t border-gray-200 pt-4 mt-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Sous-total</span>
                <span id="subtotal" class="font-medium">{$this->calculateSubtotal($selectedProducts, $products)}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">TVA (20%)</span>
                <span id="vat" class="font-medium">{$this->calculateVAT($selectedProducts, $products)}</span>
            </div>
            <div class="flex justify-between items-center text-lg font-semibold">
                <span>Total</span>
                <span id="total" class="text-blue-600">{$this->calculateTotal($selectedProducts, $products)}</span>
            </div>
        </div>
    </div>

    <!-- Informations de la commande -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden mb-8">
        <!-- En-tête du formulaire -->
        <div class="bg-white px-8 py-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-gray-800">Informations de la commande</h2>
                </div>
HTML;
        
        // Formulaire de suppression à droite du titre (uniquement en mode modification et si pas en lecture seule)
        $modifyCommandContent .= $this->generateDeleteButton($isEditMode, $commandId, $actionUrl, $userAttributes['isReadOnly']);

        $modifyCommandContent .= <<<HTML
            </div>
        </div>
        
        <div class="p-8">
            <!-- Grille des champs du formulaire -->
            <div class="space-y-6">
HTML;
        
        // Afficher la date de création uniquement en mode modification
        $modifyCommandContent .= $this->generateCreatedDateField($isEditMode, $createdAt);

        $modifyCommandContent .= <<<HTML

                <!-- Date de livraison -->
                <div>
                    <label for="deliveryDate" class="block text-sm font-semibold text-gray-700 mb-3">
                        Date de livraison <span class="text-red-500">*</span>
                    </label>
                    <input 
                        type="datetime-local" 
                        id="deliveryDate" 
                        name="deliveryDate" 
                        value="{$deliveryDate}"
                        required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900">
                </div>

                <!-- Adresse de livraison -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        Adresse de livraison <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-4">
                        <!-- Rue -->
                        <div>
                            <label for="deliveryStreet" class="block text-xs font-medium text-gray-600 mb-2">
                                Rue <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="deliveryStreet" 
                                name="deliveryAddress[street]" 
                                value="{$deliveryStreet}"
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900"
                                placeholder="Numéro et nom de rue">
                        </div>
                        
                        <!-- Code postal et ville -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="deliveryPostalCode" class="block text-xs font-medium text-gray-600 mb-2">
                                    Code postal <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="deliveryPostalCode" 
                                    name="deliveryAddress[postal_code]" 
                                    value="{$deliveryPostalCode}"
                                    required
                                    maxlength="20"
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900"
                                    placeholder="75001">
                            </div>
                            <div>
                                <label for="deliveryCity" class="block text-xs font-medium text-gray-600 mb-2">
                                    Ville <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text" 
                                    id="deliveryCity" 
                                    name="deliveryAddress[city]" 
                                    value="{$deliveryCity}"
                                    required
                                    class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900"
                                    placeholder="Paris">
                            </div>
                        </div>
                        
                        <!-- Pays -->
                        <div>
                            <label for="deliveryCountry" class="block text-xs font-medium text-gray-600 mb-2">
                                Pays
                            </label>
                            <input 
                                type="text" 
                                id="deliveryCountry" 
                                name="deliveryAddress[country]" 
                                value="{$deliveryCountry}"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900"
                                placeholder="France">
                        </div>
                        
                        <!-- Informations complémentaires -->
                        <div>
                            <label for="deliveryAdditionalInfo" class="block text-xs font-medium text-gray-600 mb-2">
                                Informations complémentaires
                            </label>
                            <textarea 
                                id="deliveryAdditionalInfo" 
                                name="deliveryAddress[additional_info]" 
                                rows="2"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 resize-y min-h-[3rem]"
                                placeholder="Appartement, étage, bâtiment, etc.">{$deliveryAdditionalInfo}</textarea>
                        </div>
                    </div>
                </div>
HTML;
        
        // Affichage du statut selon le rôle
        $modifyCommandContent .= $this->generateStatusField($userFunctionId, $statusList, $statusId, $userAttributes);

        // Génération des boutons d'action
        // Le bouton "Annuler" est affiché pour tout le monde
        // Le bouton "Enregistrer" est masqué pour les logisticiens
        $actionButtonsHtml = <<<HTML
            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
                <a href="/Commands" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Annuler</a>
                {$submitButton}
            </div>
HTML;

        $modifyCommandContent .= <<<HTML
            </div>

            {$actionButtonsHtml}
        </div>
    </div>

<script type="application/json" id="products-data">{$productsJson}</script>
</form>

<!-- Import du script de gestion des commandes -->
<script src="/public/assets/js/modify-commands.js"></script>

<!-- Styles personnalisés pour l'espacement des cartes -->
<style>
.product-card {
    margin-bottom: 1.5rem;
}

@media (min-width: 768px) {
    .product-card {
        margin-bottom: 2rem;
    }
}

@media (min-width: 1024px) {
    .product-card {
        margin-bottom: 2rem;
    }
}
</style>

HTML;

        return $modifyCommandContent;
    }


    /**
     * Génère les champs cachés du formulaire selon le mode (modification ou création).
     * 
     * Détecte si on est en mode modification (avec ID) ou création (sans ID) et génère
     * les champs cachés appropriés pour que le contrôleur traite correctement la requête.
     *
     * @param bool $isEditMode True si en mode modification, false si en mode création.
     * @param string|int $commandId ID de la commande (échappé pour l'affichage).
     * @return string Champs cachés HTML générés.
     */
    private function generateHiddenFields(bool $isEditMode, $commandId): string
    {
        $commandIdEscaped = htmlspecialchars($commandId);
        
        if ($isEditMode) {
            return <<<HTML
    <input type="hidden" name="commandId" value="{$commandIdEscaped}">
    <input type="hidden" name="updateCommand" value="true">
HTML;
        }
        
        return <<<HTML
    <input type="hidden" name="newCommand" value="true">
    <input type="hidden" name="createCommand" value="true">
HTML;
    }

    /**
     * Génère les champs cachés pour les produits sélectionnés.
     * 
     * @param array $selectedProducts Produits sélectionnés avec leurs quantités.
     * @return string Champs cachés HTML générés.
     */
    private function generateHiddenProductFields(array $selectedProducts): string
    {
        $html = '';
        foreach ($selectedProducts as $productId => $productData) {
            $quantity = (int)($productData['quantity'] ?? 0);
            if ($quantity > 0) {
                $productIdEscaped = htmlspecialchars($productId);
                $quantityEscaped = htmlspecialchars($quantity);
                $html .= "<input type=\"hidden\" name=\"products[{$productIdEscaped}][quantity]\" value=\"{$quantityEscaped}\">\n    ";
            }
        }
        return $html;
    }


    /**
     * Génère le récapitulatif de la commande.
     * 
     * @param array $selectedProducts Produits sélectionnés avec leurs quantités.
     * @param array $allProducts Tous les produits avec leurs informations (prix, nom).
     * @return string HTML du récapitulatif.
     */
    private function generateOrderSummary(array $selectedProducts, array $allProducts): string
    {
        // Créer un tableau de recherche pour les produits
        $productsMap = [];
        foreach ($allProducts as $product) {
            $productsMap[$product['product_id']] = $product;
        }
        
        $summaryHtml = '<div class="space-y-3">';
        $hasProducts = false;
        
        foreach ($selectedProducts as $productId => $productData) {
            $quantity = (int)($productData['quantity'] ?? 0);
            if ($quantity > 0 && isset($productsMap[$productId])) {
                $hasProducts = true;
                $product = $productsMap[$productId];
                $productName = htmlspecialchars($product['product_name']);
                $price = (float)$product['price'];
                $lineTotal = $price * $quantity;
                $lineTotalFormatted = number_format($lineTotal, 2, ',', ' ');
                
                $summaryHtml .= <<<HTML
            <div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">{$quantity}x {$productName}</span>
                <span class="font-semibold text-gray-900">{$lineTotalFormatted} €</span>
            </div>
HTML;
            }
        }
        
        if (!$hasProducts) {
            $summaryHtml .= <<<HTML
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                <p>Aucun produit sélectionné</p>
            </div>
HTML;
        }
        
        $summaryHtml .= '</div>';
        
        return $summaryHtml;
    }

    /**
     * Calcule le sous-total de la commande.
     * 
     * @param array $selectedProducts Produits sélectionnés avec leurs quantités.
     * @param array $allProducts Tous les produits avec leurs informations (prix).
     * @return string Sous-total formaté.
     */
    private function calculateSubtotal(array $selectedProducts, array $allProducts): string
    {
        $subtotal = 0;
        
        // Créer un tableau de recherche pour les produits
        $productsMap = [];
        foreach ($allProducts as $product) {
            $productsMap[$product['product_id']] = $product;
        }
        
        foreach ($selectedProducts as $productId => $productData) {
            $quantity = (int)($productData['quantity'] ?? 0);
            if ($quantity > 0 && isset($productsMap[$productId])) {
                $price = (float)$productsMap[$productId]['price'];
                $subtotal += $price * $quantity;
            }
        }
        
        return number_format($subtotal, 2, ',', ' ') . ' €';
    }

    /**
     * Calcule la TVA (20%) de la commande.
     * 
     * @param array $selectedProducts Produits sélectionnés avec leurs quantités.
     * @param array $allProducts Tous les produits avec leurs informations (prix).
     * @return string TVA formatée.
     */
    private function calculateVAT(array $selectedProducts, array $allProducts): string
    {
        $subtotal = 0;
        
        // Créer un tableau de recherche pour les produits
        $productsMap = [];
        foreach ($allProducts as $product) {
            $productsMap[$product['product_id']] = $product;
        }
        
        foreach ($selectedProducts as $productId => $productData) {
            $quantity = (int)($productData['quantity'] ?? 0);
            if ($quantity > 0 && isset($productsMap[$productId])) {
                $price = (float)$productsMap[$productId]['price'];
                $subtotal += $price * $quantity;
            }
        }
        
        $vat = $subtotal * 0.20;
        return number_format($vat, 2, ',', ' ') . ' €';
    }

    /**
     * Calcule le total de la commande (sous-total + TVA).
     * 
     * @param array $selectedProducts Produits sélectionnés avec leurs quantités.
     * @param array $allProducts Tous les produits avec leurs informations (prix).
     * @return string Total formaté.
     */
    private function calculateTotal(array $selectedProducts, array $allProducts): string
    {
        $subtotal = 0;
        
        // Créer un tableau de recherche pour les produits
        $productsMap = [];
        foreach ($allProducts as $product) {
            $productsMap[$product['product_id']] = $product;
        }
        
        foreach ($selectedProducts as $productId => $productData) {
            $quantity = (int)($productData['quantity'] ?? 0);
            if ($quantity > 0 && isset($productsMap[$productId])) {
                $price = (float)$productsMap[$productId]['price'];
                $subtotal += $price * $quantity;
            }
        }
        
        $total = $subtotal * 1.20;
        return number_format($total, 2, ',', ' ') . ' €';
    }

    /**
     * Construit l'URL de base pour la pagination de manière propre et simple.
     * 
     * Génère une URL propre qui reste sur la page ModifyCommand,
     * en incluant l'ID de la commande uniquement si en mode édition.
     *
     * @param string $actionUrl URL de base de l'action (généralement /ModifyCommand).
     * @param bool $isEditMode True si en mode modification.
     * @param string|int $commandId ID de la commande à inclure dans l'URL.
     * @return string URL de base pour la pagination.
     */
    private function buildPaginationUrl(string $actionUrl, bool $isEditMode, $commandId): string
    {
        // Toujours utiliser /ModifyCommand comme base
        $baseUrl = '/ModifyCommand';
        
        // Ajouter commandId uniquement en mode édition
        if ($isEditMode && !empty($commandId) && $commandId != '0') {
            $commandIdEscaped = htmlspecialchars($commandId);
            $baseUrl .= '?commandId=' . $commandIdEscaped;
        }
        
        return $baseUrl;
    }

    /**
     * Génère les attributs utilisateur selon le rôle (lecture seule pour logisticien).
     * 
     * Détermine si l'utilisateur est en mode lecture seule et génère les attributs
     * HTML appropriés (disabled, readonly, classes CSS) pour les contrôles du formulaire.
     *
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @return array Tableau contenant les attributs : ['isReadOnly', 'disabledAttr', 'readonlyAttr', 'disabledClass', 'disabledOpacityClass'].
     */
    private function generateUserAttributes(?int $userFunctionId): array
    {
        $isReadOnly = ($userFunctionId == 3);
        
        return [
            'isReadOnly' => $isReadOnly,
            'disabledAttr' => $isReadOnly ? 'disabled' : '',
            'readonlyAttr' => $isReadOnly ? 'readonly' : '',
            'disabledClass' => $isReadOnly ? 'bg-gray-100 cursor-not-allowed' : '',
            'disabledOpacityClass' => $isReadOnly ? 'opacity-60 cursor-not-allowed' : ''
        ];
    }


    /**
     * Génère le bouton de suppression de commande.
     * 
     * Crée le bouton de suppression uniquement si on est en mode modification
     * et si l'utilisateur n'est pas en lecture seule.
     *
     * @param bool $isEditMode True si en mode modification.
     * @param string|int $commandId ID de la commande.
     * @param string $actionUrl URL d'action du formulaire.
     * @param bool $isReadOnly True si l'utilisateur est en lecture seule.
     * @return string HTML du bouton de suppression ou d'un div vide.
     */
    private function generateDeleteButton(bool $isEditMode, $commandId, string $actionUrl, bool $isReadOnly): string
    {
        if ($isEditMode && $commandId && $commandId != '0' && !$isReadOnly) {
            $commandIdEscaped = htmlspecialchars($commandId);
            $actionUrlEscaped = htmlspecialchars($actionUrl);
            
            return <<<HTML
                <form method="POST" action="{$actionUrlEscaped}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?');" class="inline-block">
                    <input type="hidden" name="commandId" value="{$commandIdEscaped}">
                    <input type="hidden" name="deleteCommand" value="true">
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 border border-red-700 rounded-lg hover:bg-red-700 transition-colors duration-200">
                        <i class="fas fa-trash mr-2"></i>
                        Supprimer
                    </button>
                </form>
HTML;
        }
        
        return '<div></div>';
    }

    /**
     * Génère le bouton de soumission du formulaire.
     * 
     * Crée le bouton d'enregistrement uniquement si l'utilisateur n'est pas en lecture seule.
     *
     * @param bool $isReadOnly True si l'utilisateur est en lecture seule.
     * @return string HTML du bouton de soumission ou chaîne vide.
     */
    private function generateSubmitButton(bool $isReadOnly): string
    {
        if ($isReadOnly) {
            return '';
        }
        
        return '<button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-lg">Enregistrer</button>';
    }

    /**
     * Génère le champ de sélection du statut selon le rôle de l'utilisateur.
     * 
     * Le champ statut est toujours caché pour tous les utilisateurs.
     * Les modifications de statut doivent être effectuées depuis la page globale des commandes.
     *
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @param array $statusList Liste des statuts disponibles.
     * @param string $selectedStatusId ID du statut actuellement sélectionné.
     * @param array $userAttributes Attributs utilisateur (disabled, readonly, classes).
     * @return string HTML du champ de statut généré (toujours caché).
     */
    private function generateStatusField(?int $userFunctionId, array $statusList, string $selectedStatusId, array $userAttributes): string
    {
        // Client : statut fixé à "en attente" (3)
        if ($userFunctionId == 2) {
            return <<<HTML

                <!-- Statut (caché - modification via la page globale) -->
                <input type="hidden" name="statusId" value="3">
HTML;
        }
        
        // Tous les autres utilisateurs : statut caché avec la valeur actuelle
        // Les modifications de statut se font via la page globale des commandes
        $selectedStatusIdEscaped = htmlspecialchars($selectedStatusId);
        return <<<HTML

                <!-- Statut (caché - modification via la page globale) -->
                <input type="hidden" name="statusId" value="{$selectedStatusIdEscaped}">
HTML;
    }

    /**
     * Génère les options HTML pour le select des statuts.
     * 
     * Crée les balises <option> pour peupler la liste déroulante des statuts.
     * Filtre les statuts selon le rôle :
     * - Commercial : tous les statuts
     * - Logisticien : uniquement "validé" (1) et "envoyé" (2)
     * Présélectionne le statut actuel et échappe toutes les valeurs pour éviter les injections XSS.
     *
     * @param array $statusList Liste des statuts disponibles (avec status_id et status_name).
     * @param string $selectedStatusId ID du statut actuellement sélectionné.
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @return string Options HTML générées pour le <select>.
     */
    private function generateStatusOptions(array $statusList, string $selectedStatusId, ?int $userFunctionId): string
    {
        $options = '';
        
        foreach ($statusList as $status) {
            $statusIdValue = htmlspecialchars($status['status_id']);
            $statusName = htmlspecialchars($status['status_name']);
            $selected = ($statusIdValue === $selectedStatusId) ? 'selected' : '';
            
            // Filtrer les statuts selon le rôle
            $showStatus = $this->shouldShowStatus($statusIdValue, $userFunctionId);
            
            if ($showStatus) {
                $options .= "<option value=\"{$statusIdValue}\" {$selected}>{$statusName}</option>";
            }
        }
        
        return $options;
    }

    /**
     * Détermine si un statut doit être affiché selon le rôle de l'utilisateur.
     * 
     * Vérifie les permissions d'affichage selon le rôle :
     * - Commercial : tous les statuts
     * - Logisticien : uniquement "validé" (1) et "envoyé" (2)
     *
     * @param string $statusId ID du statut à vérifier.
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @return bool True si le statut doit être affiché, false sinon.
     */
    private function shouldShowStatus(string $statusId, ?int $userFunctionId): bool
    {
        if ($userFunctionId == 1) {
            // Commercial peut voir tous les statuts
            return true;
        }
        
        if ($userFunctionId == 3) {
            // Logisticien ne peut voir que "validé" et "envoyé"
            return in_array($statusId, ['1', '2']);
        }
        
        return false;
    }

    /**
     * Génère les données JSON des produits pour les calculs côté client.
     * 
     * Crée un objet JSON contenant les informations des produits (ID, nom, prix)
     * nécessaires pour le calcul dynamique du total de la commande.
     * Le JSON est placé dans une balise <script type="application/json"> et lu
     * avec textContent, donc pas besoin d'échappement HTML supplémentaire.
     * JSON_HEX_TAG gère déjà la sécurisation contre les balises </script>.
     *
     * @param array $products Liste des produits avec leurs informations.
     * @return string Code JSON généré pour les données des produits (non échappé, sécurisé avec JSON_HEX_TAG).
     */
    private function generateProductsJson(array $products): string
    {
        $productsData = [];
        
        foreach ($products as $product) {
            $productId = $product['product_id'];
            
            $productsData[$productId] = [
                'name' => $product['product_name'],
                'price' => (float)$product['price']
            ];
        }
        
        // Convertir en JSON avec flags de sécurité (JSON_HEX_TAG convertit < et > en séquences Unicode)
        // Pas besoin de htmlspecialchars() car le JSON est dans une balise <script type="application/json">
        // et lu avec textContent, pas innerHTML
        return json_encode($productsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }

    /**
     * Génère le champ d'affichage de la date de création.
     * 
     * Affiche la date de création uniquement en mode modification
     * et si une date valide est disponible.
     *
     * @param bool $isEditMode True si en mode modification.
     * @param string $createdAt Date de création formatée ou '-' si absente.
     * @return string HTML du champ de date de création ou chaîne vide.
     */
    private function generateCreatedDateField(bool $isEditMode, string $createdAt): string
    {
        if ($isEditMode && $createdAt !== '-') {
            $createdAtEscaped = htmlspecialchars($createdAt);
            
            return <<<HTML
                <!-- Date de création (affichage uniquement) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        Date de création
                    </label>
                    <div class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-gray-600">
                        {$createdAtEscaped}
                    </div>
                </div>
HTML;
        }
        
        return '';
    }
}

