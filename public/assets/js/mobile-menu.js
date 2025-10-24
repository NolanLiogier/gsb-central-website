/**
 * Gestion du menu mobile pour GSB Central
 */

/**
 * Bascule l'affichage du menu mobile
 */
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('hidden');
    }
}

/**
 * Bascule l'affichage de la barre de recherche mobile
 */
function toggleMobileSearch() {
    const mobileSearch = document.getElementById('mobileSearch');
    if (mobileSearch) {
        mobileSearch.classList.toggle('hidden');
    }
}

/**
 * Ferme le menu mobile et la recherche lors du redimensionnement vers desktop
 */
function handleResize() {
    if (window.innerWidth >= 1024) {
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileSearch = document.getElementById('mobileSearch');
        if (mobileMenu) {
            mobileMenu.classList.add('hidden');
        }
        if (mobileSearch) {
            mobileSearch.classList.add('hidden');
        }
    }
}

// Initialisation des événements
document.addEventListener('DOMContentLoaded', function() {
    // Écouter le redimensionnement de la fenêtre
    window.addEventListener('resize', handleResize);
    
    // Fermer le menu mobile lors du clic sur un lien
    const mobileLinks = document.querySelectorAll('#mobileMenu a');
    mobileLinks.forEach(link => {
        link.addEventListener('click', function() {
            const mobileMenu = document.getElementById('mobileMenu');
            if (mobileMenu) {
                mobileMenu.classList.add('hidden');
            }
        });
    });
});

// Exposer les fonctions globalement
window.toggleMobileMenu = toggleMobileMenu;
window.toggleMobileSearch = toggleMobileSearch;
