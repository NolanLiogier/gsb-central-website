/**
 * Système de notifications basique pour GSB Central
 */

let notificationContainer = null;

/**
 * Affiche une notification simple
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type (success, warning, danger, info)
 */
function showNotification(message, type = 'success') {
    // Créer le conteneur s'il n'existe pas
    if (!notificationContainer) {
        notificationContainer = document.createElement('div');
        notificationContainer.className = 'fixed bottom-4 right-4 z-50 space-y-2';
        document.body.appendChild(notificationContainer);
    }
    
    // Créer la notification
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 
                   type === 'warning' ? 'bg-yellow-500' : 
                   type === 'danger' ? 'bg-red-500' : 'bg-blue-500';
    
    notification.className = `w-80 p-4 rounded-lg text-white shadow-lg ${bgColor}`;
    notification.innerHTML = `
        <div class="flex justify-between items-center">
            <span>${message}</span>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-4 text-white hover:text-gray-200">×</button>
        </div>
    `;
    
    notificationContainer.appendChild(notification);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Exposer la fonction globalement
window.showNotification = showNotification;