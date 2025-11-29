/**
 * Script de pagination simple pour les tableaux
 * Gère la pagination côté client pour permettre la recherche sur toutes les données
 */

/**
 * Initialise la pagination pour un tableau
 * @param {string} tableSelector - Sélecteur CSS du tableau (par défaut: 'table')
 * @param {number} itemsPerPage - Nombre d'éléments par page (par défaut: 10)
 */
function initTablePagination(tableSelector = 'table', itemsPerPage = 10) {
    const table = document.querySelector(tableSelector);
    
    if (!table) {
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        return;
    }
    
    // Récupérer toutes les lignes (ignorer la ligne d'état vide)
    const allRows = Array.from(tbody.querySelectorAll('tr')).filter(row => {
        return !row.querySelector('td[colspan]');
    });
    
    const totalItems = allRows.length;
    const totalPages = Math.max(1, Math.ceil(totalItems / itemsPerPage));
    
    // Éléments de pagination
    const paginationContainer = document.getElementById('table-pagination');
    
    // Si moins d'éléments que itemsPerPage, masquer la pagination et afficher toutes les lignes
    if (totalItems <= itemsPerPage) {
        if (paginationContainer) {
            paginationContainer.style.display = 'none';
        }
        // Afficher toutes les lignes
        allRows.forEach(row => {
            row.style.display = '';
            row.classList.remove('hidden');
        });
        return;
    }
    
    // Éléments de pagination (suite)
    const currentPageSpan = document.getElementById('current-page');
    const totalPagesSpan = document.getElementById('total-pages');
    const prevButton = document.getElementById('prev-page');
    const nextButton = document.getElementById('next-page');
    const pageNumbersContainer = document.getElementById('page-numbers');
    
    if (!paginationContainer) {
        return;
    }
    
    let currentPage = 1;
    
    // Fonction pour réinitialiser la pagination (appelée quand la recherche est effacée)
    function resetPagination() {
        // Restaurer toutes les lignes visibles
        allRows.forEach((row) => {
            row.style.display = '';
            row.classList.remove('hidden');
        });
        
        // Réinitialiser à la page 1
        currentPage = 1;
        showPage(1);
    }
    
    // Exposer la fonction de réinitialisation globalement
    window.resetTablePagination = resetPagination;
    
    // Fonction pour afficher la page courante
    function showPage(page) {
        // Valider la page
        if (page < 1) page = 1;
        if (page > totalPages) page = totalPages;
        
        currentPage = page;
        
        // Calculer les indices de début et fin
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        
        // Afficher/masquer les lignes
        allRows.forEach((row, index) => {
            if (index >= startIndex && index < endIndex) {
                row.style.display = '';
                row.classList.remove('hidden');
            } else {
                row.style.display = 'none';
                row.classList.add('hidden');
            }
        });
        
        // Mettre à jour l'affichage de la pagination
        if (currentPageSpan) currentPageSpan.textContent = currentPage;
        if (totalPagesSpan) totalPagesSpan.textContent = totalPages;
        
        // Activer/désactiver les boutons
        if (prevButton) {
            prevButton.disabled = currentPage === 1;
            prevButton.classList.toggle('opacity-50', currentPage === 1);
            prevButton.classList.toggle('pointer-events-none', currentPage === 1);
        }
        
        if (nextButton) {
            nextButton.disabled = currentPage === totalPages;
            nextButton.classList.toggle('opacity-50', currentPage === totalPages);
            nextButton.classList.toggle('pointer-events-none', currentPage === totalPages);
        }
        
        // Générer les numéros de page
        if (pageNumbersContainer) {
            pageNumbersContainer.innerHTML = '';
            
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            // Première page
            if (startPage > 1) {
                const firstBtn = createPageButton(1, currentPage === 1);
                pageNumbersContainer.appendChild(firstBtn);
                
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'px-2 text-gray-500';
                    ellipsis.textContent = '...';
                    pageNumbersContainer.appendChild(ellipsis);
                }
            }
            
            // Pages autour de la page courante
            for (let i = startPage; i <= endPage; i++) {
                const btn = createPageButton(i, i === currentPage);
                pageNumbersContainer.appendChild(btn);
            }
            
            // Dernière page
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'px-2 text-gray-500';
                    ellipsis.textContent = '...';
                    pageNumbersContainer.appendChild(ellipsis);
                }
                
                const lastBtn = createPageButton(totalPages, currentPage === totalPages);
                pageNumbersContainer.appendChild(lastBtn);
            }
        }
        
        // Afficher la pagination seulement si nécessaire
        if (totalItems > itemsPerPage) {
            paginationContainer.style.display = '';
        } else {
            paginationContainer.style.display = 'none';
        }
    }
    
    // Créer un bouton de page
    function createPageButton(pageNum, isActive) {
        const button = document.createElement('button');
        button.textContent = pageNum;
        button.className = isActive
            ? 'px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md'
            : 'px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors';
        button.onclick = () => showPage(pageNum);
        return button;
    }
    
    // Événements des boutons
    if (prevButton) {
        prevButton.onclick = () => showPage(currentPage - 1);
    }
    
    if (nextButton) {
        nextButton.onclick = () => showPage(currentPage + 1);
    }
    
    // Afficher la première page
    showPage(1);
}

