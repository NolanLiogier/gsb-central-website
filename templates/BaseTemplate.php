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
    private function getNavLinkClasses(string $linkRoute, string $currentRoute): string
    {
        // Normalisation des routes pour la comparaison (suppression du slash initial et conversion en minuscules)
        $normalizedLinkRoute = strtolower(ltrim($linkRoute, '/'));
        $normalizedCurrentRoute = strtolower(ltrim($currentRoute, '/'));
        
        $isActive = ($normalizedCurrentRoute === $normalizedLinkRoute) || 
                   ($normalizedCurrentRoute === '' && $normalizedLinkRoute === 'home') ||
                   ($normalizedCurrentRoute === 'home' && $normalizedLinkRoute === 'home');
        
        if ($isActive) {
            return 'text-blue-600 font-semibold hover:text-blue-800';
        }
        
        return 'text-gray-600 hover:text-gray-800';
    }

    /**
     * Génère le header HTML avec navigation et barre de recherche
     * @param string $currentRoute - Route actuelle pour déterminer l'état actif des liens
     * @return string - HTML du header
     */
    private function getHeader(string $currentRoute): string
    {
        // Génération des classes CSS pour les liens de navigation
        $homeNavClasses = $this->getNavLinkClasses('/home', $currentRoute);
        $companiesNavClasses = $this->getNavLinkClasses('/companies', $currentRoute);
        $ordersNavClasses = $this->getNavLinkClasses('/orders', $currentRoute);
        $stockNavClasses = $this->getNavLinkClasses('/stock', $currentRoute);

        return <<<HTML
            <header class="bg-white shadow-sm">
                <nav class="container mx-auto px-4 py-3">
                    <!-- Header principal pour desktop -->
                    <div class="hidden lg:flex justify-between items-center">
                        <div class="flex items-center">
                            <a href="/home" class="flex items-center text-xl font-bold text-gray-800 hover:text-blue-600 transition-colors">
                                <img src="/public/assets/img/gsb-logo-no-name.png" alt="GSB Logo" class="h-8 w-auto mr-3">
                                GSB Central
                            </a>
                        </div>
                        
                        <!-- Navigation desktop -->
                        <ul class="hidden lg:flex space-x-6">
                            <li><a href="/home" class="{$homeNavClasses} transition-colors">Tableau de bord</a></li>
                            <li><a href="/companies" class="{$companiesNavClasses} transition-colors">Entreprises</a></li>
                            <li><a href="#" class="{$ordersNavClasses} transition-colors">Commandes</a></li>
                            <li><a href="#" class="{$stockNavClasses} transition-colors">Stock</a></li>
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
                            <a href="/home" class="flex items-center text-lg font-bold text-gray-800 hover:text-blue-600 transition-colors">
                                <img src="/public/assets/img/gsb-logo-no-name.png" alt="GSB Logo" class="h-8 w-auto mr-2">
                                GSB Central
                            </a>
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
                            <li><a href="/home" class="{$homeNavClasses} transition-colors block py-2">Tableau de bord</a></li>
                            <li><a href="/companies" class="{$companiesNavClasses} transition-colors block py-2">Entreprises</a></li>
                            <li><a href="#" class="{$ordersNavClasses} transition-colors block py-2">Commandes</a></li>
                            <li><a href="#" class="{$stockNavClasses} transition-colors block py-2">Stock</a></li>
                        </ul>
                    </div>
                </nav>
            </header>
        HTML;
    }

    /**
     * Génère le footer HTML avec copyright
     * @return string - HTML du footer
     */
    private function getFooter(): string
    {
        $currentYear = date('Y');
        
        return <<<HTML
            <footer class="bg-white py-4 mt-8 border-t border-gray-200">
                <div class="container mx-auto text-center text-gray-600 text-sm">
                    &copy;{$currentYear} GSB Central. Tous droits réservés.
                </div>
            </footer>
        HTML;
    }

    /**
     * Génère le script JavaScript pour afficher les notifications de session
     * @return string - Script JavaScript pour les notifications ou chaîne vide
     */
    private function getNotificationScript(): string
    {
        // Initialisation de la session si nécessaire
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérification de la présence d'une notification en session
        if (!isset($_SESSION['notification'])) {
            return '';
        }
        
        $notification = $_SESSION['notification'];
        $type = $notification['type'] ?? 'success';
        $message = htmlspecialchars($notification['message'] ?? '');
        $duration = $notification['duration'] ?? 5000;
        
        // Suppression de la notification de la session après récupération
        unset($_SESSION['notification']);
        
        // Génération du script JavaScript pour afficher la notification
        return "<script>showNotification('{$message}', '{$type}', {$duration});</script>";
    }

    /**
     * Génère le HTML complet d'une page avec header, contenu et footer
     * @param string $title - Titre de la page affiché dans l'onglet du navigateur
     * @param string $content - Contenu principal de la page
     * @param string $currentRoute - Route actuelle pour déterminer l'état actif des liens
     * @return string - HTML complet de la page
     */
    public function render(string $title = 'GSB Central', string $content = '', string $currentRoute = ''): string
    {
        $notificationScript = $this->getNotificationScript();

        // Définition des pages qui utilisent un layout complet (sans header/footer)
        $noHeaderFooterPages = ['Connexion', '404 Not Found'];
        $hasHeaderFooter = !in_array($title, $noHeaderFooterPages);

        // Génération des composants selon le type de page
        $header = $hasHeaderFooter ? $this->getHeader($currentRoute) : '';
        $footer = $hasHeaderFooter ? $this->getFooter() : '';
        $main = $hasHeaderFooter ? 
            <<<HTML
                <main class="container mx-auto mt-4 p-4 flex-grow">
                    {$content}
                </main>
            HTML : $content;
        $bodyClass = $hasHeaderFooter ? 'bg-gray-100 font-sans antialiased min-h-screen flex flex-col' : 'bg-gray-100 font-sans antialiased min-h-screen';
        $escapedTitle = htmlspecialchars($title);

        return <<<HTML
        <!DOCTYPE html>
        <html lang="fr">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>{$escapedTitle}</title>
            
            <link rel="icon" type="image/x-icon" href="/favicon.ico">
            <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico">
            <link rel="apple-touch-icon" href="/favicon.ico">
            
            <meta name="description" content="GSB Central - Plateforme de gestion centralisée pour les entreprises. Tableau de bord, gestion des entreprises, commandes et stock.">
            <meta name="keywords" content="GSB, gestion, entreprise, commandes, stock, tableau de bord, administration">
            <meta name="author" content="GSB Central">
            <meta name="robots" content="index, follow">
            <meta name="googlebot" content="index, follow">
            <meta name="language" content="fr">
            <meta name="revisit-after" content="7 days">
            
            <meta property="og:title" content="{$escapedTitle}">
            <meta property="og:description" content="GSB Central - Plateforme de gestion centralisée pour les entreprises">
            <meta property="og:type" content="website">
            <meta property="og:url" content="https://gsb-nolan-liogier.fr">
            <meta property="og:site_name" content="GSB Central">
            <meta property="og:locale" content="fr_FR">
            
            <meta name="twitter:card" content="summary">
            <meta name="twitter:title" content="{$escapedTitle}">
            <meta name="twitter:description" content="GSB Central - Plateforme de gestion centralisée pour les entreprises">
            
            <link rel="canonical" href="https://gsb-nolan-liogier.fr">
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        </head>
        <body class="{$bodyClass}">
            {$header}
            {$main}
            {$footer}
            
            <!-- Scripts JavaScript -->
            <script src="/public/assets/js/notifications.js"></script>
            <script src="/public/assets/js/mobile-menu.js"></script>
            {$notificationScript}
        </body>
        </html>
        HTML;
    }
}