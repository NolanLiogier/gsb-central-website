<?php

namespace Templates;

use Templates\BaseTemplate;

/**
 * Template pour la page d'erreur 404
 * Affiche une page d'erreur élégante quand une ressource n'est pas trouvée
 */
class NotFoundTemplate
{
    /**
     * Génère le contenu HTML de la page 404 avec un design moderne
     * @param array $datas - Données optionnelles (non utilisées)
     * @return string - HTML complet de la page 404
     */
    public static function displayNotFound(array $datas = []): string
    {
        // Construction du contenu HTML de la page 404 avec le style du dashboard
        $content = '
        <div class="min-h-screen bg-gray-100 flex items-center justify-center">
            <div class="max-w-lg w-full mx-4">
                <!-- Card principale avec le style du dashboard -->
                <div class="bg-white rounded-lg shadow-sm p-8 text-center">
                    <!-- Icône d\'erreur dans un cercle coloré -->
                    <div class="mb-6">
                        <div class="mx-auto w-20 h-20 bg-red-50 rounded-full flex items-center justify-center mb-4">
                            <i class="fas fa-exclamation-triangle text-red-500 text-3xl"></i>
                        </div>
                        <!-- Titre principal avec le style du dashboard -->
                        <h1 class="text-6xl font-bold text-gray-800 mb-2">404</h1>
                        <h2 class="text-xl font-semibold text-gray-700 mb-2">Page introuvable</h2>
                    </div>
                    
                    <!-- Message d\'erreur avec typographie du dashboard -->
                    <div class="mb-8">
                        <p class="text-gray-600 text-base mb-2">
                            Désolé, la page que vous recherchez n\'existe pas.
                        </p>
                        <p class="text-gray-500 text-sm">
                            Vérifiez l\'URL ou retournez à la page d\'accueil.
                        </p>
                    </div>
                    
                    <!-- Bouton principal avec le style du dashboard -->
                    <div class="mb-6">
                        <a href="/" 
                           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-home mr-2"></i>
                            Retour à l\'accueil
                        </a>
                    </div>
                    
                    <!-- Actions secondaires avec le style minimaliste du dashboard -->
                    <div class="flex justify-center space-x-6 text-sm">
                        <button onclick="history.back()" 
                                class="text-gray-500 hover:text-gray-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-arrow-left mr-1"></i>
                            Précédent
                        </button>
                        <span class="text-gray-300">|</span>
                        <a href="/contact" 
                           class="text-gray-500 hover:text-gray-700 transition-colors duration-200 flex items-center">
                            <i class="fas fa-envelope mr-1"></i>
                            Contact
                        </a>
                    </div>
                </div>
                
                <!-- Informations de débogage avec le style discret du dashboard -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-400">
                        Code d\'erreur: 404 | ' . date('Y-m-d H:i:s') . '
                    </p>
                </div>
            </div>
        </div>';

        return BaseTemplate::render(title: '404 Not Found', content: $content);
    }
}
