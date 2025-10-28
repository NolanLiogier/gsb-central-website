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
        $deliveryDate = isset($datas['delivery_date']) ? htmlspecialchars(date('Y-m-d\TH:i', strtotime($datas['delivery_date']))) : date('Y-m-d\TH:i');
        $statusId = htmlspecialchars($datas['fk_status_id'] ?? '1');
        $createdAt = isset($datas['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($datas['created_at']))) : '-';

        // Récupération des statuts dynamiquement depuis la base de données
        $statusList = $datas['statusList'] ?? [];

        // Détection du mode (modification ou création)
        $isEditMode = !empty($commandId) && $commandId != '0';
        $pageTitle = $isEditMode ? "Modifier la commande" : "Créer une commande";
        $showDeleteButton = $isEditMode;
        $actionUrl = "/ModifyCommand";

        $modifyCommandContent = <<<HTML
<!-- En-tête de la page -->
<div class="mb-8">
    <h1 class="text-3xl font-bold text-gray-900">{$pageTitle}</h1>
</div>

<!-- Formulaire principal -->
<form method="POST" action="{$actionUrl}" class="space-y-8">
    <!-- Champs cachés pour l'ID de la commande et le flag de mise à jour -->
HTML;

        if ($isEditMode) {
            $modifyCommandContent .= "<input type=\"hidden\" name=\"commandId\" value=\"{$commandId}\">";
            $modifyCommandContent .= "<input type=\"hidden\" name=\"updateCommand\" value=\"true\">";
        } else {
            $modifyCommandContent .= "<input type=\"hidden\" name=\"newCommand\" value=\"true\">";
            $modifyCommandContent .= "<input type=\"hidden\" name=\"createCommand\" value=\"true\">";
        }

        $modifyCommandContent .= <<<HTML

    <!-- Sélection des produits -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Sélectionner les produits</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
HTML;

        // Récupération des produits depuis les données
        $products = $datas['products'] ?? [];
        
        // Génération des cartes de produits
        foreach ($products as $product) {
            $productId = htmlspecialchars($product['product_id']);
            $productName = htmlspecialchars($product['product_name']);
            $price = htmlspecialchars(number_format($product['price'], 2, ',', ' '));
            $quantity = htmlspecialchars($product['quantity']);
            $stockStatus = $quantity > 0 ? 'En stock' : 'Rupture';
            $stockClass = $quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            $modifyCommandContent .= <<<HTML
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">{$productName}</h3>
                    <span class="{$stockClass} px-2 py-1 rounded-full text-xs font-medium">
                        {$stockStatus}: {$quantity}
                    </span>
                </div>
                
                <div class="mb-4">
                    <span class="text-2xl font-bold text-blue-600">{$price} €</span>
                </div>
                
                <div class="flex items-center space-x-3">
                    <label class="text-sm font-medium text-gray-700">Quantité</label>
                    <div class="flex items-center border border-gray-300 rounded-lg">
                        <button type="button" onclick="decreaseQuantity('{$productId}')" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-l-lg">
                            <i class="fas fa-minus text-sm"></i>
                        </button>
                        <input type="number" id="quantity-{$productId}" name="products[{$productId}][quantity]" value="0" min="0" max="{$quantity}" 
                               class="w-16 text-center border-0 focus:ring-0 focus:outline-none" readonly>
                        <button type="button" onclick="increaseQuantity('{$productId}', {$quantity})" class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-r-lg">
                            <i class="fas fa-plus text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
HTML;
        }

        $modifyCommandContent .= <<<HTML
        </div>
    </div>

    <!-- Récapitulatif de la commande -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">Récapitulatif de la commande</h2>
        
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
    <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
        <!-- En-tête du formulaire -->
        <div class="bg-white px-8 py-6 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Informations de la commande</h2>
                </div>
HTML;
        
        // Bouton supprimer uniquement en mode modification
        if ($showDeleteButton) {
            $modifyCommandContent .= <<<HTML
                <form method="POST" action="{$actionUrl}" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?');" class="inline-block">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="deleteCommand" value="true">
                    <button 
                        type="submit" 
                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-red-600 border border-red-700 rounded-lg hover:bg-red-700 transition-colors duration-200">
                        <i class="fas fa-trash mr-2"></i>
                        Supprimer
                    </button>
                </form>
HTML;
        } else {
            $modifyCommandContent .= '<div></div>';
        }

        $modifyCommandContent .= <<<HTML
            </div>
        </div>
        
        <div class="p-8">
            <!-- Grille des champs du formulaire -->
            <div class="space-y-6">
HTML;
        
        // Afficher la date de création uniquement en mode modification
        if ($isEditMode && $createdAt !== '-') {
            $modifyCommandContent .= <<<HTML
                <!-- Date de création (affichage uniquement) -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-3">
                        Date de création
                    </label>
                    <div class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-gray-600">
                        {$createdAt}
                    </div>
                </div>
HTML;
        }

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

                <!-- Statut -->
                <div>
                    <label for="statusId" class="block text-sm font-semibold text-gray-700 mb-3">
                        Statut <span class="text-red-500">*</span>
                    </label>
                    <select 
                        id="statusId" 
                        name="statusId" 
                        required
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900">
HTML;

        // Génération dynamique des options de statut
        foreach ($statusList as $status) {
            $statusId = htmlspecialchars($status['status_id']);
            $statusName = htmlspecialchars($status['status_name']);
            $selected = ($statusId === $statusId) ? 'selected' : '';
            $modifyCommandContent .= "<option value=\"{$statusId}\" {$selected}>{$statusName}</option>";
        }

        $modifyCommandContent .= <<<HTML
                    </select>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
                <a href="/Commands" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Retour</a>
                <button 
                    type="submit" 
                    class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-sm">
                    Valider la commande
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Script JavaScript pour gérer la sélection des produits -->
<script>
    // Données des produits pour les calculs
    const products = {
HTML;

        // Ajout des données des produits pour JavaScript
        foreach ($products as $product) {
            $productId = htmlspecialchars($product['product_id']);
            $productName = htmlspecialchars($product['product_name']);
            $price = htmlspecialchars($product['price']);
            
            $modifyCommandContent .= <<<HTML
        {$productId}: {
            name: '{$productName}',
            price: {$price}
        },
HTML;
        }

        $modifyCommandContent .= <<<HTML
    };
</script>

<!-- Import du script de gestion des commandes -->
<script src="/public/assets/js/modify-commands.js"></script>

HTML;

        return $modifyCommandContent;
    }
}

