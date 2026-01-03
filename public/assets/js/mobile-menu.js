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
 * Ferme le menu mobile lors du redimensionnement vers desktop
 */
function handleResize() {
    if (window.innerWidth >= 1024) {
        const mobileMenu = document.getElementById('mobileMenu');
        if (mobileMenu) {
            mobileMenu.classList.add('hidden');
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
