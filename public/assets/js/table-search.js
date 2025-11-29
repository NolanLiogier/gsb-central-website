/**
 * Script de recherche simple pour les tableaux
 * Filtre les lignes du tableau en fonction du texte saisi dans le champ de recherche
 * Recherche dans toutes les données, pas seulement celles affichées par la pagination
 */

/**
 * Initialise la recherche pour un tableau
 * @param {string} searchInputId - ID du champ de recherche
 * @param {string} tableSelector - Sélecteur CSS du tableau (par défaut: 'table')
 * @param {number[]} searchColumns - Indices des colonnes à rechercher (0-based, par défaut: [0])
 */
function initTableSearch(searchInputId, tableSelector = 'table', searchColumns = [0]) {
    const searchInput = document.getElementById(searchInputId);
    const table = document.querySelector(tableSelector);
    
    if (!searchInput || !table) {
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }
    
    // Trouver le conteneur de pagination (s'il existe)
    const tableContainer = table.closest('.bg-white');
    const paginationContainer = tableContainer ? tableContainer.nextElementSibling : null;
    
    // Fonction de recherche
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        // Sélectionner toutes les lignes, y compris celles masquées par la pagination
        const rows = tbody.querySelectorAll('tr');
        const hasSearchTerm = searchTerm !== '';
        
        // Masquer/afficher la pagination selon si une recherche est active
        if (paginationContainer) {
            paginationContainer.style.display = hasSearchTerm ? 'none' : '';
        }
        
        rows.forEach(row => {
            // Ignorer la ligne d'état vide (colspan)
            if (row.querySelector('td[colspan]')) {
                return;
            }
            
            // Récupérer le texte des colonnes à rechercher
            const cells = row.querySelectorAll('td');
            let found = false;
            
            if (!hasSearchTerm) {
                // Si pas de recherche, restaurer l'affichage original selon la pagination
                // Restaurer l'affichage original basé sur data-original-display
                const originalDisplay = row.getAttribute('data-original-display');
                if (originalDisplay === 'none') {
                    row.style.display = 'none';
                    // S'assurer que la classe hidden est présente
                    if (!row.classList.contains('hidden')) {
                        row.classList.add('hidden');
                    }
                } else {
                    row.style.display = '';
                    // Retirer la classe hidden si elle est présente
                    row.classList.remove('hidden');
                }
                return; // Continuer avec la ligne suivante
            } else {
                // Rechercher dans les colonnes spécifiées
                searchColumns.forEach(colIndex => {
                    if (cells[colIndex]) {
                        const cellText = cells[colIndex].textContent.toLowerCase();
                        if (cellText.includes(searchTerm)) {
                            found = true;
                        }
                    }
                });
                
                // Afficher toutes les lignes correspondantes, même si elles ne sont pas sur la page courante
                if (found) {
                    row.style.display = '';
                    row.classList.remove('hidden'); // Retirer la classe hidden pour afficher la ligne
                } else {
                    row.style.display = 'none';
                    row.classList.add('hidden'); // Ajouter la classe hidden pour masquer la ligne
                }
            }
        });
    }
    
    // Écouter les changements dans le champ de recherche
    searchInput.addEventListener('input', performSearch);
    
    // Exécuter la recherche au chargement si le champ a déjà une valeur
    if (searchInput.value) {
        performSearch();
    }
}

