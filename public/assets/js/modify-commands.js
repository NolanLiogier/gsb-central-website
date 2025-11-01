/**
 * Script pour la gestion de la modification de commandes
 */

// Augmenter la quantité d'un produit
function increaseQuantity(productId, maxQuantity) {
    const input = document.getElementById('quantity-' + productId);
    const currentValue = parseInt(input.value) || 0;
    if (currentValue < maxQuantity) {
        input.value = currentValue + 1;
        updateOrderSummary();
    }
}

// Diminuer la quantité d'un produit
function decreaseQuantity(productId) {
    const input = document.getElementById('quantity-' + productId);
    const currentValue = parseInt(input.value) || 0;
    if (currentValue > 0) {
        input.value = currentValue - 1;
        updateOrderSummary();
    }
}

// Variable globale pour stocker les données des produits
let products = {};

// Décode les entités HTML pour obtenir le JSON brut
// Nécessaire car le template PHP échappe le JSON avec htmlspecialchars()
function decodeHtmlEntities(html) {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = html;
    return textarea.value;
}

// Initialiser les données des produits
function initProductsData() {
    const productsDataElement = document.getElementById('products-data');
    if (productsDataElement) {
        try {
            // Récupérer le contenu textuel (qui peut contenir des entités HTML échappées)
            let jsonString = productsDataElement.textContent || productsDataElement.innerText;
            
            // Décoder les entités HTML si présentes
            jsonString = decodeHtmlEntities(jsonString);
            
            // Parser le JSON
            products = JSON.parse(jsonString);
        } catch (error) {
            console.error('Erreur lors du parsing des données produits:', error);
            console.error('Contenu reçu:', productsDataElement ? productsDataElement.textContent : 'élément non trouvé');
            products = {};
        }
    } else {
        console.warn('Élément products-data non trouvé dans le DOM');
        products = {};
    }
}

// Formate un nombre avec séparateur décimal français (virgule)
// Utilise un séparateur d'espaces pour les milliers si nécessaire
function formatFrenchNumber(num) {
    // Convertir en nombre avec 2 décimales
    const fixed = num.toFixed(2);
    // Remplacer le point décimal par une virgule
    return fixed.replace('.', ',');
}

// Mettre à jour le récapitulatif de la commande
function updateOrderSummary() {
    if (typeof products === 'undefined' || Object.keys(products).length === 0) return;
    
    let subtotal = 0;
    let summaryHTML = '';
    let selectedProductsCount = 0;

    Object.keys(products).forEach(productId => {
        const input = document.getElementById('quantity-' + productId);
        if (!input) return;
        
        const quantity = parseInt(input.value) || 0;
        
        // Valider que la quantité est dans les limites min/max
        const min = parseInt(input.getAttribute('min')) || 0;
        const max = parseInt(input.getAttribute('max')) || Infinity;
        
        // Corriger la valeur si elle dépasse les limites
        let validQuantity = quantity;
        if (quantity < min) {
            validQuantity = min;
            input.value = min;
        } else if (quantity > max) {
            validQuantity = max;
            input.value = max;
        }
        
        if (validQuantity > 0) {
            selectedProductsCount++;
            const product = products[productId];
            const lineTotal = product.price * validQuantity;
            subtotal += lineTotal;
            
            summaryHTML += `<div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">${validQuantity}x ${product.name}</span>
                <span class="font-semibold text-gray-900">${formatFrenchNumber(lineTotal)} €</span>
            </div>`;
        }
    });

    // Mettre à jour l'affichage du récapitulatif complet (étape 2)
    const summaryDiv = document.getElementById('order-summary');
    if (summaryDiv) {
        if (summaryHTML) {
            summaryDiv.innerHTML = summaryHTML;
        } else {
            summaryDiv.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-shopping-cart text-4xl mb-2"></i><p>Aucun produit sélectionné</p></div>';
        }
    }

    // Calculer et afficher les totaux complets (étape 2)
    const subtotalElement = document.getElementById('subtotal');
    const vatElement = document.getElementById('vat');
    const totalElement = document.getElementById('total');
    
    if (subtotalElement && vatElement && totalElement) {
        const vat = subtotal * 0.20;
        const total = subtotal + vat;
        
        subtotalElement.textContent = formatFrenchNumber(subtotal) + ' €';
        vatElement.textContent = formatFrenchNumber(vat) + ' €';
        totalElement.textContent = formatFrenchNumber(total) + ' €';
    }

    // Mettre à jour le mini récapitulatif (étape 1)
    const miniTotalElement = document.getElementById('mini-total');
    const miniItemsElement = document.getElementById('mini-items');
    
    if (miniTotalElement && miniItemsElement) {
        const total = subtotal * 1.20;
        miniTotalElement.textContent = formatFrenchNumber(total) + ' €';
        
        const productText = selectedProductsCount === 1 ? 'produit' : 'produits';
        miniItemsElement.textContent = selectedProductsCount + ' ' + productText;
    }
}

// Initialiser les écouteurs d'événements pour les champs de quantité
// Permet de mettre à jour les totaux quand l'utilisateur tape directement dans les champs
function initQuantityInputListeners() {
    Object.keys(products).forEach(productId => {
        const input = document.getElementById('quantity-' + productId);
        if (input) {
            // Mettre à jour lors de la saisie (input) et lors de la validation (change)
            input.addEventListener('input', function() {
                updateOrderSummary();
            });
            input.addEventListener('change', function() {
                // Valider la valeur lors du changement
                const min = parseInt(this.getAttribute('min')) || 0;
                const max = parseInt(this.getAttribute('max')) || Infinity;
                const value = parseInt(this.value) || 0;
                
                if (value < min) {
                    this.value = min;
                } else if (value > max) {
                    this.value = max;
                }
                
                updateOrderSummary();
            });
        }
    });
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les données des produits
    initProductsData();
    
    // Initialiser les écouteurs d'événements pour les champs de quantité
    initQuantityInputListeners();
    
    // Mettre à jour le récapitulatif
    updateOrderSummary();
});
