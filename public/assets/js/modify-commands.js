/**
 * Script pour la gestion de la modification de commandes
 * 
 * Gère la sélection des produits, le calcul des quantités et
 * la mise à jour du récapitulatif de commande en temps réel.
 */

// Fonction pour augmenter la quantité d'un produit
function increaseQuantity(productId, maxQuantity) {
    const input = document.getElementById('quantity-' + productId);
    const currentValue = parseInt(input.value) || 0;
    if (currentValue < maxQuantity) {
        input.value = currentValue + 1;
        updateOrderSummary();
    }
}

// Fonction pour diminuer la quantité d'un produit
function decreaseQuantity(productId) {
    const input = document.getElementById('quantity-' + productId);
    const currentValue = parseInt(input.value) || 0;
    if (currentValue > 0) {
        input.value = currentValue - 1;
        updateOrderSummary();
    }
}

// Fonction pour mettre à jour le récapitulatif de la commande
function updateOrderSummary() {
    const summaryDiv = document.getElementById('order-summary');
    let subtotal = 0;
    let summaryHTML = '';

    // Parcourir tous les produits sélectionnés
    Object.keys(products).forEach(productId => {
        const input = document.getElementById('quantity-' + productId);
        const quantity = parseInt(input.value) || 0;
        
        if (quantity > 0) {
            const product = products[productId];
            const lineTotal = product.price * quantity;
            subtotal += lineTotal;
            
            summaryHTML += '<div class="flex justify-between items-center">' +
                '<span>' + quantity + 'x ' + product.name + '</span>' +
                '<span class="font-medium">' + lineTotal.toFixed(2).replace('.', ',') + ' €</span>' +
                '</div>';
        }
    });

    // Mettre à jour l'affichage
    if (summaryHTML) {
        summaryDiv.innerHTML = summaryHTML;
    } else {
        summaryDiv.innerHTML = '<div class="text-center text-gray-500 py-8">' +
            '<i class="fas fa-shopping-cart text-4xl mb-2"></i>' +
            '<p>Aucun produit sélectionné</p>' +
            '</div>';
    }

    // Calculer la TVA et le total
    const vat = subtotal * 0.20;
    const total = subtotal + vat;

    // Mettre à jour les totaux
    document.getElementById('subtotal').textContent = subtotal.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('vat').textContent = vat.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('total').textContent = total.toFixed(2).replace('.', ',') + ' €';
}

// Initialiser le récapitulatif au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
});
