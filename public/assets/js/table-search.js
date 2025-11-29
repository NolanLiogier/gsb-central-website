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
    const paginationContainer = document.getElementById('table-pagination');
    
    // Fonction de recherche
    function performSearch() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        // Sélectionner toutes les lignes
        const rows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
            return !row.querySelector('td[colspan]');
        });
        const hasSearchTerm = searchTerm !== '';
        
        if (!hasSearchTerm) {
            // Si pas de recherche, restaurer l'état initial
            // Restaurer toutes les lignes
            rows.forEach(row => {
                row.style.display = '';
                row.classList.remove('hidden');
            });
            
            // Réinitialiser la pagination si elle existe
            if (typeof window.resetTablePagination === 'function') {
                window.resetTablePagination();
            } else if (paginationContainer) {
                // Si la pagination n'est pas encore initialisée, juste l'afficher
                paginationContainer.style.display = '';
            }
            return;
        }
        
        // Recherche active : masquer la pagination
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        
        // Rechercher dans toutes les lignes
        rows.forEach(row => {
            // Récupérer le texte des colonnes à rechercher
            const cells = row.querySelectorAll('td');
            let found = false;
            
            // Rechercher dans les colonnes spécifiées
            searchColumns.forEach(colIndex => {
                if (cells[colIndex]) {
                    const cellText = cells[colIndex].textContent.toLowerCase();
                    if (cellText.includes(searchTerm)) {
                        found = true;
                    }
                }
            });
            
            // Afficher toutes les lignes correspondantes
            if (found) {
                row.style.display = '';
                row.classList.remove('hidden');
            } else {
                row.style.display = 'none';
                row.classList.add('hidden');
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

