<?php

namespace Templates;

/**
 * Template de base pour toutes les pages de l'application GSB Central
 * Gère la structure HTML commune, les notifications et le layout conditionnel
 */
class BaseTemplate
{
    /**
     * Génère le HTML complet d'une page avec header, contenu et footer
     * @param string $title - Titre de la page affiché dans l'onglet du navigateur
     * @param string $content - Contenu principal de la page
     * @return string - HTML complet de la page
     */
    public static function render($title = 'GSB Central', $content = ''): string
    {
        // Initialisation du script de notification pour afficher les messages de session
        $notificationScript = '';
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Récupération et affichage des notifications stockées en session
        if (isset($_SESSION['notification'])) {
            $notification = $_SESSION['notification'];
            $type = $notification['type'] ?? 'success';
            $message = htmlspecialchars($notification['message'] ?? '');
            $duration = $notification['duration'] ?? 5000;
            
            // Génération du script JavaScript pour afficher la notification
            $notificationScript = "<script>showNotification('{$message}', '{$type}', {$duration});</script>";
            unset($_SESSION['notification']);
        }

        // Définition des pages qui utilisent un layout complet (sans header/footer)
        $fullPageTitles = ['Connexion - GSB', '404 Not Found'];
        $isFullPage = in_array($title, $fullPageTitles);

        // Construction du header avec navigation et barre de recherche
        $header = $isFullPage ? '' : '
            <header class="bg-white shadow-sm">
                <nav class="container mx-auto px-4 py-3 flex justify-between items-center">
                    <div class="flex items-center">
                        <span class="text-xl font-bold text-gray-800">GSB Central</span>
                    </div>
                    <ul class="flex space-x-6">
                        <li><a href="#" class="text-blue-600 font-semibold hover:text-blue-800">Tableau de bord</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-gray-800">Entreprises</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-gray-800">Commandes</a></li>
                        <li><a href="#" class="text-gray-600 hover:text-gray-800">Stock</a></li>
                    </ul>
                    <div class="flex items-center space-x-4">
                        <div class="relative group">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                            </div>
                            <input 
                                type="search" 
                                placeholder="Rechercher dans GSB Central..." 
                                class="w-80 pl-10 pr-4 py-2.5 text-sm text-gray-700 placeholder-gray-500 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200 hover:bg-white hover:border-gray-300">
                        </div>
                        <div class="flex items-center space-x-2">
                            <img src="/public/assets/img/user_avatar.png" alt="User Avatar" class="h-8 w-8 rounded-full object-cover ring-2 ring-gray-200">
                            <div class="hidden md:block">
                                <p class="text-sm font-medium text-gray-700">Utilisateur</p>
                                <p class="text-xs text-gray-500">Administrateur</p>
                            </div>
                        </div>
                    </div>
                </nav>
            </header>';

        // Construction de la zone principale avec le contenu de la page
        $main = $isFullPage ? $content : '
            <main class="container mx-auto mt-4 p-4 flex-grow">
                ' . $content . '
            </main>';

        // Construction du footer avec copyright
        $footer = $isFullPage ? '' : '
            <footer class="bg-white py-4 mt-8 border-t border-gray-200">
                <div class="container mx-auto text-center text-gray-600 text-sm">
                    &copy;2024 GSB Central. Tous droits réservés.
                </div>
            </footer>';

        // Définition des classes CSS du body selon le type de page
        $bodyClass = $isFullPage ? 'bg-gray-100 font-sans antialiased min-h-screen' : 'bg-gray-100 font-sans antialiased min-h-screen flex flex-col';

        // Génération du HTML complet de la page
        return '
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>' . htmlspecialchars(string: $title) . '</title>
            
            <!-- Favicon pour l\'icône du site -->
            <link rel="icon" type="image/x-icon" href="/favicon.ico">
            <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
            <link rel="apple-touch-icon" href="/favicon.ico">
            
            <!-- Balises meta pour le SEO -->
            <meta name="description" content="GSB Central - Plateforme de gestion centralisée pour les entreprises. Tableau de bord, gestion des entreprises, commandes et stock.">
            <meta name="keywords" content="GSB, gestion, entreprise, commandes, stock, tableau de bord, administration">
            <meta name="author" content="GSB Central">
            <meta name="robots" content="index, follow">
            <meta name="googlebot" content="index, follow">
            <meta name="language" content="fr">
            <meta name="revisit-after" content="7 days">
            
            <!-- Balises Open Graph pour le partage sur les réseaux sociaux -->
            <meta property="og:title" content="' . htmlspecialchars(string: $title) . '">
            <meta property="og:description" content="GSB Central - Plateforme de gestion centralisée pour les entreprises">
            <meta property="og:type" content="website">
            <meta property="og:url" content="https://gsb-nolan-liogier.fr">
            <meta property="og:site_name" content="GSB Central">
            <meta property="og:locale" content="fr_FR">
            
            <!-- Balises Twitter Card pour l\'affichage sur Twitter -->
            <meta name="twitter:card" content="summary">
            <meta name="twitter:title" content="' . htmlspecialchars(string: $title) . '">
            <meta name="twitter:description" content="GSB Central - Plateforme de gestion centralisée pour les entreprises">
            
            <!-- URL canonique pour éviter le contenu dupliqué -->
            <link rel="canonical" href="https://gsb-nolan-liogier.fr">
            
            <!-- Feuilles de style externes -->
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <!-- Font Awesome pour les icônes -->
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        </head>
        <body class="' . $bodyClass . '">
            ' . $header . '
            ' . $main . '
            ' . $footer . '
            
            <!-- Script du système de notifications -->
            <script src="/public/assets/js/notifications.js"></script>
            ' . $notificationScript . '
        </body>
        </html>
        ';
    }
}