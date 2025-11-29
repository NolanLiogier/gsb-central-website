/**
 * Script pour la gestion de la modification de commandes
 */

// Augmenter la quantité d'un produit
function increaseQuantity(productId, maxQuantity) {
    const input = document.getElementById('quantity-' + productId);
    const currentValue = parseInt(input.value) || 0;
    if (currentValue < maxQuantity) {
        input.value = currentValue + 1;
        saveProductQuantitiesToStorage();
        updateOrderSummary();
    }
}

// Diminuer la quantité d'un produit
function decreaseQuantity(productId) {
    const input = document.getElementById('quantity-' + productId);
    const currentValue = parseInt(input.value) || 0;
    if (currentValue > 0) {
        input.value = currentValue - 1;
        saveProductQuantitiesToStorage();
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
                saveProductQuantitiesToStorage();
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
                
                saveProductQuantitiesToStorage();
                updateOrderSummary();
            });
        }
    });
}

/**
 * Stocke les quantités des produits dans le localStorage.
 * 
 * Cette fonction est appelée à chaque modification de quantité pour persister
 * les données entre les changements de page de pagination.
 * 
 * @return {void}
 */
function saveProductQuantitiesToStorage() {
    if (typeof products === 'undefined') return;
    
    const quantities = {};
    
    // Parcourir tous les produits connus
    Object.keys(products).forEach(productId => {
        const input = document.getElementById('quantity-' + productId);
        if (input) {
            quantities[productId] = parseInt(input.value) || 0;
        } else {
            // Si le champ n'existe pas, chercher dans le localStorage
            const stored = localStorage.getItem('productQuantity_' + productId);
            if (stored !== null) {
                quantities[productId] = parseInt(stored) || 0;
            } else {
                quantities[productId] = 0;
            }
        }
    });
    
    // Stocker toutes les quantités dans le localStorage
    localStorage.setItem('productQuantities', JSON.stringify(quantities));
}

/**
 * Restaure les quantités des produits depuis le localStorage.
 * 
 * Cette fonction est appelée au chargement de la page pour restaurer
 * les quantités qui ont été saisies sur d'autres pages de pagination.
 * Synchronise d'abord le localStorage avec les valeurs actuelles des champs visibles,
 * puis restaure les valeurs pour les produits visibles depuis le localStorage.
 * 
 * @return {void}
 */
function restoreProductQuantitiesFromStorage() {
    if (typeof products === 'undefined') return;
    
    try {
        // D'abord, synchroniser le localStorage avec les valeurs actuelles des champs visibles
        // Cela permet de préserver les valeurs venant du serveur (commande existante)
        const currentQuantities = {};
        Object.keys(products).forEach(productId => {
            const input = document.getElementById('quantity-' + productId);
            if (input) {
                currentQuantities[productId] = parseInt(input.value) || 0;
            }
        });
        
        // Récupérer les quantités stockées
        const stored = localStorage.getItem('productQuantities');
        let storedQuantities = {};
        if (stored) {
            storedQuantities = JSON.parse(stored);
        }
        
        // Fusionner : les valeurs actuelles (visibles) ont priorité sur les valeurs stockées
        const mergedQuantities = { ...storedQuantities, ...currentQuantities };
        
        // Restaurer les quantités pour les produits visibles depuis le localStorage
        // (mais seulement si elles ne sont pas déjà définies dans les champs)
        Object.keys(mergedQuantities).forEach(productId => {
            const input = document.getElementById('quantity-' + productId);
            if (input) {
                const currentValue = parseInt(input.value) || 0;
                const storedValue = mergedQuantities[productId] || 0;
                // Si le champ est vide ou à 0, restaurer depuis le localStorage
                if (currentValue === 0 && storedValue > 0) {
                    input.value = storedValue;
                    mergedQuantities[productId] = storedValue;
                } else if (currentValue > 0) {
                    // Si le champ a déjà une valeur, la conserver
                    mergedQuantities[productId] = currentValue;
                }
            }
        });
        
        // Mettre à jour le localStorage avec les quantités fusionnées
        localStorage.setItem('productQuantities', JSON.stringify(mergedQuantities));
    } catch (error) {
        console.error('Erreur lors de la restauration des quantités:', error);
    }
}

/**
 * Collecte toutes les quantités des produits (visibles et non visibles) et les stocke dans des champs cachés.
 * 
 * Cette fonction est appelée avant la soumission du formulaire pour la pagination
 * afin de préserver les quantités des produits qui ne sont pas sur la page courante.
 * 
 * @param {HTMLFormElement} form Formulaire à modifier.
 * @return {void}
 */
function collectAllProductQuantities(form) {
    if (!form || typeof products === 'undefined') return;
    
    // Récupérer les quantités depuis le localStorage
    let quantities = {};
    try {
        const stored = localStorage.getItem('productQuantities');
        if (stored) {
            quantities = JSON.parse(stored);
        }
    } catch (error) {
        console.error('Erreur lors de la lecture du localStorage:', error);
    }
    
    // Parcourir tous les produits connus
    Object.keys(products).forEach(productId => {
        // Chercher le champ de quantité dans le DOM (même s'il n'est pas visible)
        const input = document.getElementById('quantity-' + productId);
        let quantity = 0;
        
        if (input) {
            // Si le champ existe dans le DOM, utiliser sa valeur et la sauvegarder
            quantity = parseInt(input.value) || 0;
            quantities[productId] = quantity;
        } else {
            // Si le champ n'existe pas (produit non visible), utiliser la valeur du localStorage
            quantity = quantities[productId] || 0;
        }
        
        // Créer ou mettre à jour le champ caché pour ce produit
        let hiddenInput = form.querySelector(`input[name="products[${productId}][quantity]"]`);
        if (!hiddenInput) {
            hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = `products[${productId}][quantity]`;
            form.appendChild(hiddenInput);
        }
        hiddenInput.value = quantity;
    });
    
    // Mettre à jour le localStorage avec toutes les quantités
    localStorage.setItem('productQuantities', JSON.stringify(quantities));
}

/**
 * Nettoie le localStorage des quantités de produits.
 * 
 * Cette fonction est appelée lorsque la commande est soumise ou annulée
 * pour éviter de conserver des données obsolètes.
 * 
 * @return {void}
 */
function clearProductQuantitiesStorage() {
    try {
        localStorage.removeItem('productQuantities');
        // Nettoyer aussi les quantités individuelles si elles existent
        if (typeof products !== 'undefined') {
            Object.keys(products).forEach(productId => {
                localStorage.removeItem('productQuantity_' + productId);
            });
        }
    } catch (error) {
        console.error('Erreur lors du nettoyage du localStorage:', error);
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les données des produits
    initProductsData();
    
    // Restaurer les quantités depuis le localStorage
    restoreProductQuantitiesFromStorage();
    
    // Initialiser les écouteurs d'événements pour les champs de quantité
    initQuantityInputListeners();
    
    // Mettre à jour le récapitulatif
    updateOrderSummary();
    
    // Nettoyer le localStorage lors de la soumission du formulaire
    const form = document.getElementById('product-selection-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            // C'est une vraie soumission, nettoyer le localStorage
            clearProductQuantitiesStorage();
        });
    }
});
