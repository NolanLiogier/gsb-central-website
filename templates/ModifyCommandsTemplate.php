<?php

namespace Templates;

/**
 * Classe ModifyCommandsTemplate
 * 
 * Gère l'affichage du template de modification de commande.
 * Crée un formulaire complet pour modifier les informations d'une commande :
 * date de livraison et statut.
 */
class ModifyCommandsTemplate {
    /**
     * Génère le contenu HTML du formulaire de modification de commande.
     * 
     * Crée un formulaire avec validation des champs date de livraison
     * et statut. Inclut des boutons d'annulation, suppression et sauvegarde.
     *
     * @param array $datas Données de la commande (command_id, delivery_date, fk_status_id, created_at).
     * @return string HTML complet du formulaire de modification.
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

        // Récupération des statuts dynamiquement depuis la base de données
        $statusList = $datas['statusList'] ?? [];

        // Détection du mode (modification ou création)
        $isEditMode = !empty($commandId) && $commandId != '0';
        $pageTitle = $isEditMode ? "Modifier la commande" : "Créer une commande";
        $actionUrl = "/ModifyCommand";

        $modifyCommandContent = <<<HTML
<!-- En-tête de la page -->
<div class="mb-8">
    <h1 class="text-4xl font-bold text-gray-800">{$pageTitle}</h1>
</div>

<!-- Formulaire principal pour sauvegarder les informations -->
<form method="POST" action="{$actionUrl}">
    <!-- Champs cachés pour l'ID de la commande et le flag de mise à jour -->
HTML;
        
        $modifyCommandContent .= $this->generateHiddenFields($isEditMode, $commandId);

        $modifyCommandContent .= <<<HTML

    <!-- Sélection des produits -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-8 mb-8">
        <div class="flex items-center justify-between mb-8">
            <h2 class="text-xl font-semibold text-gray-800">Sélectionner les produits</h2>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">
HTML;

        // Récupération des informations utilisateur (nécessaire pour les contrôles de lecture seule)
        $currentUser = $datas['currentUser'] ?? [];
        $userFunctionId = $currentUser['fk_function_id'] ?? null;
        $userAttributes = $this->generateUserAttributes($userFunctionId);
        
        // Récupération des produits depuis les données
        $products = $datas['products'] ?? [];
        
        // Génération des cartes de produits
        $modifyCommandContent .= $this->generateProductCards($products, $userAttributes);

        $modifyCommandContent .= <<<HTML
        </div>
    </div>

    <!-- Récapitulatif de la commande -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Récapitulatif de la commande</h2>
        
        <div id="order-summary" class="space-y-3 mb-6">
            <div class="text-center text-gray-500 py-8">
                <i class="fas fa-shopping-cart text-4xl mb-2"></i>
                <p>Aucun produit sélectionné</p>
            </div>
        </div>
        
        <div class="border-t border-gray-200 pt-4">
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">Sous-total</span>
                <span id="subtotal" class="font-medium">0,00 €</span>
            </div>
            <div class="flex justify-between items-center mb-2">
                <span class="text-gray-600">TVA (20%)</span>
                <span id="vat" class="font-medium">0,00 €</span>
            </div>
            <div class="flex justify-between items-center text-lg font-semibold">
                <span>Total</span>
                <span id="total" class="text-blue-600">0,00 €</span>
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

        // Génération des données JSON des produits pour JavaScript
        $productsJson = $this->generateProductsJson($products);

        $modifyCommandContent .= <<<HTML
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
                <a href="/Commands" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Retour</a>
                {$submitButton}
            </div>
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
     * Génère les cartes HTML pour chaque produit.
     * 
     * Crée les cartes de produits avec leurs informations (nom, prix, stock)
     * et les contrôles de quantité. Applique les attributs de lecture seule
     * pour les utilisateurs sans droits de modification.
     *
     * @param array $products Liste des produits avec leurs informations.
     * @param array $userAttributes Attributs utilisateur (disabled, readonly, classes).
     * @return string HTML des cartes de produits générées.
     */
    private function generateProductCards(array $products, array $userAttributes): string
    {
        $html = '';
        
        foreach ($products as $product) {
            $productId = htmlspecialchars($product['product_id']);
            $productName = htmlspecialchars($product['product_name']);
            $price = htmlspecialchars(number_format($product['price'], 2, ',', ' '));
            $quantity = htmlspecialchars($product['quantity']);
            $orderedQuantity = htmlspecialchars($product['ordered_quantity'] ?? 0);
            $stockStatus = $quantity > 0 ? 'En stock' : 'Rupture';
            $stockClass = $quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            $html .= <<<HTML
            <div class="product-card bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all duration-200 hover:border-blue-300" data-product-id="{$productId}">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-base font-semibold text-gray-900 leading-tight">{$productName}</h3>
                    <span class="{$stockClass} px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap ml-2">
                        {$stockStatus}: {$quantity}
                    </span>
                </div>
                
                <div class="mb-4">
                    <div class="flex items-baseline space-x-1">
                        <span class="text-xl font-bold text-blue-600">{$price} €</span>
                        <span class="text-xs text-gray-500">par unité</span>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <label class="block text-xs font-medium text-gray-700">Quantité commandée</label>
                    <div class="flex items-center justify-start">
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-white">
                            <button type="button" onclick="decreaseQuantity('{$productId}')" 
                                    {$userAttributes['disabledAttr']}
                                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="decrease-btn-{$productId}">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" id="quantity-{$productId}" name="products[{$productId}][quantity]" value="{$orderedQuantity}" min="0" max="{$quantity}" 
                                   {$userAttributes['readonlyAttr']}
                                   class="w-16 text-center border-0 focus:ring-0 focus:outline-none font-medium text-sm text-gray-900 bg-white {$userAttributes['disabledClass']}">
                            <button type="button" onclick="increaseQuantity('{$productId}', {$quantity})" 
                                    {$userAttributes['disabledAttr']}
                                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="increase-btn-{$productId}">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
HTML;
        }
        
        return $html;
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
     *
     * @param array $products Liste des produits avec leurs informations.
     * @return string Code JSON généré pour les données des produits (échappé pour HTML).
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
        
        // Convertir en JSON et échapper pour l'affichage HTML
        return htmlspecialchars(json_encode($productsData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
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

