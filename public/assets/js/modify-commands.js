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

// Mettre à jour le récapitulatif de la commande
function updateOrderSummary() {
    if (typeof products === 'undefined') return;
    
    let subtotal = 0;
    let summaryHTML = '';
    let selectedProductsCount = 0;

    Object.keys(products).forEach(productId => {
        const input = document.getElementById('quantity-' + productId);
        const quantity = parseInt(input.value) || 0;
        
        if (quantity > 0) {
            selectedProductsCount++;
            const product = products[productId];
            const lineTotal = product.price * quantity;
            subtotal += lineTotal;
            
            summaryHTML += `<div class="flex justify-between items-center py-2 border-b border-gray-100">
                <span class="text-gray-700">${quantity}x ${product.name}</span>
                <span class="font-semibold text-gray-900">${lineTotal.toFixed(2).replace('.', ',')} €</span>
            </div>`;
        }
    });

    // Mettre à jour l'affichage
    const summaryDiv = document.getElementById('order-summary');
    
    if (summaryHTML) {
        summaryDiv.innerHTML = summaryHTML;
    } else {
        summaryDiv.innerHTML = '<div class="text-center text-gray-500 py-8"><i class="fas fa-shopping-cart text-4xl mb-2"></i><p>Aucun produit sélectionné</p></div>';
    }

    // Calculer et afficher les totaux
    const vat = subtotal * 0.20;
    const total = subtotal + vat;
    
    document.getElementById('subtotal').textContent = subtotal.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('vat').textContent = vat.toFixed(2).replace('.', ',') + ' €';
    document.getElementById('total').textContent = total.toFixed(2).replace('.', ',') + ' €';
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    updateOrderSummary();
});
