<?php

namespace Templates;

use Templates\widgets\ProductWidget;

/**
 * Classe ModifyCommandsTemplate
 * 
 * Gère l'affichage du template de modification de commande en deux étapes :
 * - Étape 1 : Sélection des produits
 * - Étape 2 : Informations de livraison et récapitulatif
 */
class ModifyCommandsTemplate {
    /**
     * Génère le contenu HTML selon l'étape demandée.
     * 
     * Route vers displayProductStep() ou displayDeliveryStep() selon l'étape.
     *
     * @param array $datas Données de la commande (command_id, delivery_date, fk_status_id, created_at, step).
     * @return string HTML complet du formulaire.
     */
    public function displayModifyCommands(array $datas = []): string {
        $step = $datas['step'] ?? 'products';
        
        if ($step === 'delivery') {
            return $this->displayDeliveryStep($datas);
        }
        
        return $this->displayProductStep($datas);
    }

    /**
     * Génère le contenu HTML de l'étape 1 : Sélection des produits.
     * 
     * Affiche les produits disponibles avec pagination et permet leur sélection.
     *
     * @param array $datas Données de la commande et produits.
     * @return string HTML de l'étape de sélection des produits.
     */
    private function displayProductStep(array $datas = []): string {

        if (empty($datas)) {
            $datas = [];
        }

        // Préparation des données pour l'affichage
        $commandId = htmlspecialchars($datas['command_id'] ?? 0);
        
        // Détection du mode (modification ou création)
        $isEditMode = !empty($commandId) && $commandId != '0';
        $pageTitle = $isEditMode ? "Modifier la commande - Étape 1" : "Créer une commande - Étape 1";
        $actionUrl = "/ModifyCommand";

        // Indicateur de progression
        $progressHtml = $this->generateProgressIndicator(1);

        $modifyCommandContent = <<<HTML
<!-- En-tête de la page -->
<div class="mb-8">
    <h1 class="text-4xl font-bold text-gray-800">{$pageTitle}</h1>
    <p class="text-gray-600 mt-2">Sélectionnez les produits à commander</p>
</div>

{$progressHtml}

<!-- Formulaire principal pour passer à l'étape suivante -->
<form method="POST" action="{$actionUrl}" id="product-selection-form">
    <!-- Champs cachés pour l'ID de la commande et l'étape -->
HTML;
        
        $modifyCommandContent .= $this->generateHiddenFieldsForProductStep($isEditMode, $commandId);

        // Récupération des informations utilisateur (nécessaire pour les contrôles de lecture seule)
        $currentUser = $datas['currentUser'] ?? [];
        $userFunctionId = $currentUser['fk_function_id'] ?? null;
        $userAttributes = $this->generateUserAttributes($userFunctionId);
        
        // Récupération des produits depuis les données
        $products = $datas['products'] ?? [];
        
        // Construction simple et propre de l'URL de base pour la pagination
        $paginationBaseUrl = $this->buildPaginationUrl($actionUrl, $isEditMode, $commandId);
        
        // Génération des cartes de produits avec pagination via le widget
        // Utiliser la pagination par formulaire pour éviter les paramètres GET
        $productWidget = new ProductWidget();
        $hiddenFields = [];
        if ($isEditMode) {
            $hiddenFields['commandId'] = $commandId;
        }
        $hiddenFields['step'] = 'products';
        $hiddenFields['paginationOnly'] = 'true';
        
        $productsHtml = $productWidget->render([
            'products' => $products,
            'itemsPerPage' => 6,
            'baseUrl' => $paginationBaseUrl,
            'userAttributes' => $userAttributes,
            'useFormPagination' => true,
            'formId' => 'product-selection-form',
            'paginationHiddenFields' => $hiddenFields
        ]);

        // Génération des données JSON des produits pour JavaScript
        $productsJson = $this->generateProductsJson($products);

        // Mini récapitulatif pour l'étape 1
        $miniSummaryHtml = $this->generateMiniSummary();

        $modifyCommandContent .= <<<HTML

    <!-- Sélection des produits -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-semibold text-gray-800">Sélectionner les produits</h2>
        </div>
        
        {$productsHtml}
    </div>

    <!-- Mini récapitulatif -->
    {$miniSummaryHtml}

    <!-- Boutons d'action -->
    <div class="flex justify-end space-x-4 mt-8">
        <a href="/Commands" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Annuler</a>
        <button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-lg" {$userAttributes['disabledAttr']}>
            Continuer <i class="fas fa-arrow-right ml-2"></i>
        </button>
    </div>

<script type="application/json" id="products-data">{$productsJson}</script>
</form>

<script>
// Debug: vérifier que le formulaire est bien soumis
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('product-selection-form');
    if (form) {
        // Vérifier que goToDelivery est bien présent
        const goToDeliveryInput = form.querySelector('input[name="goToDelivery"]');
        if (!goToDeliveryInput) {
            console.error('Erreur: Le champ goToDelivery est manquant dans le formulaire');
        }
        
        // Log lors de la soumission pour debug
        form.addEventListener('submit', function(e) {
            const goToDelivery = form.querySelector('input[name="goToDelivery"]');
            const paginationOnly = form.querySelector('input[name="paginationOnly"]');
            
            if (paginationOnly && paginationOnly.value === 'true') {
                console.log('Pagination détectée, pas de progression vers l\'étape suivante');
            } else if (goToDelivery && goToDelivery.value === 'true') {
                console.log('Soumission du formulaire pour passer à l\'étape de livraison');
            } else {
                console.warn('Avertissement: Aucun indicateur d\'action détecté (goToDelivery ou paginationOnly)');
            }
        });
    }
});
</script>

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
     * Génère le contenu HTML de l'étape 2 : Informations de livraison et récapitulatif.
     * 
     * Affiche le récapitulatif des produits sélectionnés, les informations de livraison
     * et permet la soumission finale de la commande.
     *
     * @param array $datas Données de la commande et produits sélectionnés.
     * @return string HTML de l'étape de livraison.
     */
    private function displayDeliveryStep(array $datas = []): string {
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

        // Récupération des statuts dynamiquement depuis la base de données
        $statusList = $datas['statusList'] ?? [];

        // Détection du mode (modification ou création)
        $isEditMode = !empty($commandId) && $commandId != '0';
        $pageTitle = $isEditMode ? "Modifier la commande - Étape 2" : "Créer une commande - Étape 2";
        $actionUrl = "/ModifyCommand";

        // Récupération des produits sélectionnés (depuis POST)
        // Format attendu: [$productId => ['quantity' => X]]
        $selectedProducts = $datas['selectedProducts'] ?? [];

        // Récupération des informations utilisateur
        $currentUser = $datas['currentUser'] ?? [];
        $userFunctionId = $currentUser['fk_function_id'] ?? null;
        $userAttributes = $this->generateUserAttributes($userFunctionId);

        // Indicateur de progression
        $progressHtml = $this->generateProgressIndicator(2);

        $modifyCommandContent = <<<HTML
<!-- En-tête de la page -->
<div class="mb-8">
    <h1 class="text-4xl font-bold text-gray-800">{$pageTitle}</h1>
    <p class="text-gray-600 mt-2">Vérifiez les informations et finalisez votre commande</p>
</div>

{$progressHtml}

<!-- Formulaire principal pour sauvegarder les informations -->
<form method="POST" action="{$actionUrl}">
    <!-- Champs cachés pour l'ID de la commande et le flag de mise à jour -->
HTML;
        
        $modifyCommandContent .= $this->generateHiddenFields($isEditMode, $commandId);
        
        // Ajouter les produits sélectionnés comme champs cachés
        $modifyCommandContent .= $this->generateHiddenProductFields($selectedProducts);

        $modifyCommandContent .= <<<HTML

    <!-- Récapitulatif de la commande -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Récapitulatif de la commande</h2>
        
        {$this->generateOrderSummary($selectedProducts, $datas['products'] ?? [])}
        
        <div class="border-t border-gray-200 pt-4 mt-6">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Sous-total</span>
                <span id="subtotal" class="font-medium">{$this->calculateSubtotal($selectedProducts, $datas['products'] ?? [])}</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">TVA (20%)</span>
                <span id="vat" class="font-medium">{$this->calculateVAT($selectedProducts, $datas['products'] ?? [])}</span>
            </div>
            <div class="flex justify-between items-center text-lg font-semibold">
                <span>Total</span>
                <span id="total" class="text-blue-600">{$this->calculateTotal($selectedProducts, $datas['products'] ?? [])}</span>
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
HTML;

        // Préparer le bouton de soumission selon le mode
        $submitButton = $this->generateSubmitButton($userAttributes['isReadOnly']);
        
        // Affichage du statut selon le rôle
        $modifyCommandContent .= $this->generateStatusField($userFunctionId, $statusList, $statusId, $userAttributes);

        $modifyCommandContent .= <<<HTML
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
                <button type="submit" name="backToProducts" value="true" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </button>
                <a href="/Commands" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Annuler</a>
                {$submitButton}
            </div>
        </div>
    </div>
</form>

HTML;

        return $modifyCommandContent;
    }

    /**
     * Génère les champs cachés pour l'étape de sélection des produits.
     * 
     * @param bool $isEditMode True si en mode modification.
     * @param string|int $commandId ID de la commande.
     * @return string Champs cachés HTML générés.
     */
    private function generateHiddenFieldsForProductStep(bool $isEditMode, $commandId): string
    {
        $commandIdEscaped = htmlspecialchars($commandId);
        
        $html = '<input type="hidden" name="step" value="products">';
        // Le champ goToDelivery sera présent par défaut, mais sera supprimé par JavaScript lors de la pagination
        // Cela permet de distinguer un clic sur "Continuer" (goToDelivery présent) d'un clic sur pagination (paginationOnly présent)
        $html .= '<input type="hidden" name="goToDelivery" id="goToDeliveryField" value="true">';
        
        if ($isEditMode) {
            $html .= '<input type="hidden" name="commandId" value="' . $commandIdEscaped . '">';
        }
        
        return $html;
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
     * Génère l'indicateur de progression pour les deux étapes.
     * 
     * @param int $currentStep Étape actuelle (1 ou 2).
     * @return string HTML de l'indicateur de progression.
     */
    private function generateProgressIndicator(int $currentStep): string
    {
        $step1Class = $currentStep >= 1 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600';
        $step2Class = $currentStep >= 2 ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600';
        $progressLineClass = $currentStep >= 2 ? 'bg-blue-600' : 'bg-gray-200';
        $step1LabelClass = $currentStep >= 1 ? 'text-blue-600' : 'text-gray-500';
        $step2LabelClass = $currentStep >= 2 ? 'text-blue-600' : 'text-gray-500';
        
        return <<<HTML
<div class="mb-8 bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center justify-center">
        <div class="flex items-center">
            <div class="flex items-center {$step1Class} rounded-full w-10 h-10 justify-center font-semibold">
                1
            </div>
            <div class="w-24 h-1 mx-4 {$progressLineClass}"></div>
            <div class="flex items-center {$step2Class} rounded-full w-10 h-10 justify-center font-semibold">
                2
            </div>
        </div>
    </div>
    <div class="flex items-center justify-center mt-4">
        <div class="flex items-center space-x-32">
            <span class="text-sm font-medium {$step1LabelClass}">Sélection des produits</span>
            <span class="text-sm font-medium {$step2LabelClass}">Informations de livraison</span>
        </div>
    </div>
</div>
HTML;
    }

    /**
     * Génère un mini récapitulatif pour l'étape 1.
     * 
     * @return string HTML du mini récapitulatif.
     */
    private function generateMiniSummary(): string
    {
        return <<<HTML
    <!-- Mini récapitulatif -->
    <div class="bg-blue-50 rounded-lg border border-blue-200 p-6 mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-semibold text-gray-800 mb-2">Récapitulatif</h3>
                <p class="text-sm text-gray-600">Les détails complets seront affichés à l'étape suivante</p>
            </div>
            <div class="text-right">
                <div class="text-2xl font-bold text-blue-600" id="mini-total">0,00 €</div>
                <div class="text-xs text-gray-500 mt-1" id="mini-items">0 produit(s)</div>
            </div>
        </div>
    </div>
HTML;
    }

    /**
     * Génère le récapitulatif de la commande pour l'étape 2.
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
     * Crée le bouton de validation uniquement si l'utilisateur n'est pas en lecture seule.
     *
     * @param bool $isReadOnly True si l'utilisateur est en lecture seule.
     * @return string HTML du bouton de soumission ou chaîne vide.
     */
    private function generateSubmitButton(bool $isReadOnly): string
    {
        if ($isReadOnly) {
            return '';
        }
        
        return '<button type="submit" class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-lg">Valider la commande</button>';
    }

    /**
     * Génère le champ de sélection du statut selon le rôle de l'utilisateur.
     * 
     * Crée soit un select avec les statuts filtrés selon le rôle (Commercial, Logisticien),
     * soit un champ caché avec le statut "en attente" pour les clients.
     *
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @param array $statusList Liste des statuts disponibles.
     * @param string $selectedStatusId ID du statut actuellement sélectionné.
     * @param array $userAttributes Attributs utilisateur (disabled, readonly, classes).
     * @return string HTML du champ de statut généré.
     */
    private function generateStatusField(?int $userFunctionId, array $statusList, string $selectedStatusId, array $userAttributes): string
    {
        // Client : statut fixé à "en attente" (3)
        if ($userFunctionId == 2) {
            return <<<HTML

                <!-- Statut (caché pour les clients) -->
                <input type="hidden" name="statusId" value="3">
HTML;
        }
        
        // Commercial ou Logisticien : affichage du select avec filtrage selon le rôle
        $statusOptions = $this->generateStatusOptions($statusList, $selectedStatusId, $userFunctionId);
        $disabledAttr = htmlspecialchars($userAttributes['disabledAttr']);
        $disabledOpacityClass = htmlspecialchars($userAttributes['disabledOpacityClass']);
        
        return <<<HTML

                <!-- Statut -->
                <div>
                    <label for="statusId" class="block text-sm font-semibold text-gray-700 mb-3">
                        Statut <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="statusId" 
                        name="statusId" 
                        required
                        {$disabledAttr}
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 {$disabledOpacityClass}">
                        {$statusOptions}
                    </select>
                </div>
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

