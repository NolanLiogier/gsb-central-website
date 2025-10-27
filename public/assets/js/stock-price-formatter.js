/**
 * Formatage automatique des prix en format XX.XX
 * S'exécute au chargement de la page
 */
document.addEventListener('DOMContentLoaded', function() {
    const priceInputs = document.querySelectorAll('input[name="price"]');
    
    priceInputs.forEach(input => {
        // Au clic, supprime les caractères non numériques
        input.addEventListener('input', function() {
            this.value = this.value.replace(/[^\d.]/g, '');
        });
        
        // À la perte de focus, formate avec 2 décimales
        input.addEventListener('blur', function() {
            let value = parseFloat(this.value) || 0;
            this.value = value.toFixed(2);
        });
    });
});

