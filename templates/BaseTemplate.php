<?php

namespace Templates;

/**
 * Template de base pour toutes les pages de l'application GSB Central
 * Gère la structure HTML commune, les notifications et le layout conditionnel
 */
class BaseTemplate
{
    /**
     * Détermine si un lien de navigation est actif selon la route actuelle
     * @param string $linkRoute - Route du lien à vérifier
     * @param string $currentRoute - Route actuelle
     * @return string - Classes CSS pour l'état actif ou normal
     */
    private static function getNavLinkClasses(string $linkRoute, string $currentRoute): string
    {
        $isActive = ($currentRoute === $linkRoute) || 
                   ($currentRoute === '/' && $linkRoute === '/home');
        
        if ($isActive) {
            return 'text-blue-600 font-semibold hover:text-blue-800';
        }
        
        return 'text-gray-600 hover:text-gray-800';
    }

    /**
     * Génère le HTML complet d'une page avec header, contenu et footer
     * @param string $title - Titre de la page affiché dans l'onglet du navigateur
     * @param string $content - Contenu principal de la page
     * @param string $currentRoute - Route actuelle pour déterminer l'état actif des liens
     * @return string - HTML complet de la page
     */
    public static function render($title = 'GSB Central', $content = '', $currentRoute = ''): string
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
                <nav class="container mx-auto px-4 py-3">
                    <!-- Header principal pour desktop -->
                    <div class="hidden lg:flex justify-between items-center">
                        <div class="flex items-center">
                            <a href="/home" class="text-xl font-bold text-gray-800 hover:text-blue-600 transition-colors">GSB Central</a>
                        </div>
                        
                        <!-- Navigation desktop -->
                        <ul class="hidden lg:flex space-x-6">
                            <li><a href="/home" class="' . self::getNavLinkClasses('/home', $currentRoute) . ' transition-colors">Tableau de bord</a></li>
                            <li><a href="/companies" class="' . self::getNavLinkClasses('/companies', $currentRoute) . ' transition-colors">Entreprises</a></li>
                            <li><a href="#" class="text-gray-600 hover:text-gray-800 transition-colors">Commandes</a></li>
                            <li><a href="#" class="text-gray-600 hover:text-gray-800 transition-colors">Stock</a></li>
                        </ul>
                        
                        <!-- Zone recherche desktop -->
                        <div class="hidden lg:flex items-center">
                            <div class="relative group">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400 group-hover:text-gray-600 transition-colors"></i>
                                </div>
                                <input 
                                    type="search" 
                                    placeholder="Rechercher..." 
                                    class="w-64 pl-10 pr-4 py-2.5 text-sm text-gray-700 placeholder-gray-500 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200 hover:bg-white hover:border-gray-300">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Header mobile -->
                    <div class="lg:hidden flex justify-between items-center">
                        <!-- Bouton menu mobile à gauche -->
                        <button class="p-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors" onclick="toggleMobileMenu()">
                            <i class="fas fa-bars text-xl"></i>
                        </button>
                        
                        <!-- Titre au centre -->
                        <div class="flex-1 flex justify-center">
                            <a href="/home" class="text-lg font-bold text-gray-800 hover:text-blue-600 transition-colors">GSB Central</a>
                        </div>
                        
                        <!-- Icône recherche à droite -->
                        <button class="p-2 rounded-md text-gray-600 hover:text-gray-800 hover:bg-gray-100 transition-colors" onclick="toggleMobileSearch()">
                            <i class="fas fa-search text-xl"></i>
                        </button>
                    </div>
                    
                    <!-- Barre de recherche mobile -->
                    <div id="mobileSearch" class="hidden lg:hidden mt-4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="search" 
                                placeholder="Rechercher..." 
                                class="w-full pl-10 pr-4 py-2.5 text-sm text-gray-700 placeholder-gray-500 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent focus:bg-white transition-all duration-200">
                        </div>
                    </div>
                    
                    <!-- Menu mobile -->
                    <div id="mobileMenu" class="hidden lg:hidden mt-4 pb-4 border-t border-gray-200">
                        <ul class="flex flex-col space-y-3 pt-4">
                            <li><a href="/home" class="' . self::getNavLinkClasses('/home', $currentRoute) . ' transition-colors block py-2">Tableau de bord</a></li>
                            <li><a href="/companies" class="' . self::getNavLinkClasses('/companies', $currentRoute) . ' transition-colors block py-2">Entreprises</a></li>
                            <li><a href="#" class="text-gray-600 hover:text-gray-800 transition-colors block py-2">Commandes</a></li>
                            <li><a href="#" class="text-gray-600 hover:text-gray-800 transition-colors block py-2">Stock</a></li>
                        </ul>
                        
                        
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
            
            <!-- Scripts JavaScript -->
            <script src="/public/assets/js/notifications.js"></script>
            <script src="/public/assets/js/mobile-menu.js"></script>
            ' . $notificationScript . '
        </body>
        </html>
        ';
    }
}