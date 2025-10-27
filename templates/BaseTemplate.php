<?php

namespace Templates;

/**
 * Template de base pour toutes les pages de l'application GSB Central.
 * 
 * Encapsule toute la structure HTML commune (doctype, meta tags, head, body).
 * Gère le header avec navigation responsive (desktop/mobile), le footer,
 * les notifications flash messages, et détermine dynamiquement la présence
 * du header/footer selon le type de page (page de connexion vs pages internes).
 */
class BaseTemplate
{
    /**
     * Détermine les classes CSS pour un lien de navigation selon son état actif.
     * 
     * Compare la route du lien avec la route actuelle pour appliquer les bonnes
     * classes CSS. Gère également les cas spéciaux (route racine = home) et
     * les relations parent/enfant (ex: /modify-company = enfant de /companies).
     * Utilisé pour mettre en évidence le lien de la page courante dans la navigation.
     * 
     * @param string $linkRoute Route du lien à vérifier (ex: '/home', '/companies').
     * @param string $currentRoute Route actuelle de la page.
     * @return string Classes CSS pour l'état actif (bleu) ou normal (gris).
     */
    private function getNavLinkClasses(array $linkRoutes, string $currentRoute): string
    {
        $currentRoute = strtolower(ltrim($currentRoute, '/'));
          
        foreach ($linkRoutes as $linkRoute) {
            $link = strtolower(ltrim($linkRoute, '/'));
            if ($link === $currentRoute) {
                return 'text-blue-600 font-semibold hover:text-blue-800';
            }
        }

        return 'text-gray-600 hover:text-gray-800';
    }

    /**
     * Génère le header HTML avec navigation responsive et barre de recherche.
     * 
     * Crée l'en-tête de navigation avec deux versions : desktop (visible sur écrans larges)
     * et mobile (menu hamburger). Détermine les classes CSS actives pour les liens de
     * navigation. Inclut le logo GSB Central, les liens de navigation, et la barre de recherche.
     * 
     * @param string $currentRoute Route actuelle pour déterminer l'état actif des liens.
     * @return string HTML complet du header avec navigation responsive.
     */
    private function getHeader(string $currentRoute): string
    {
        // Génération des classes CSS dynamiques pour chaque lien selon son état actif
        // Permet de mettre en évidence visuellement la page courante
        $homeNavClasses = $this->getNavLinkClasses(['/Home'], $currentRoute);
        $companiesNavClasses = $this->getNavLinkClasses(['/Companies', '/ModifyCompany'], $currentRoute);
        $ordersNavClasses = $this->getNavLinkClasses(['/Orders'], $currentRoute);
        $stockNavClasses = $this->getNavLinkClasses(['/Stock'], $currentRoute);

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
                            <li><a href="/stock" class="{$stockNavClasses} transition-colors">Stock</a></li>
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
                            <li><a href="/stock" class="{$stockNavClasses} transition-colors block py-2">Stock</a></li>
                        </ul>
                    </div>
                </nav>
            </header>
        HTML;
    }

    /**
     * Génère le footer HTML avec copyright et informations de base.
     * 
     * Crée le pied de page avec l'année courante dynamique pour le copyright.
     * Design minimaliste avec bordure supérieure pour séparation visuelle.
     * 
     * @return string HTML du footer.
     */
    private function getFooter(): string
    {
        // Année courante pour copyright dynamique
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
     * Génère le script JavaScript pour afficher les notifications de session.
     * 
     * Récupère la notification stockée dans la session (pattern flash message),
     * l'échappe pour éviter les injections XSS, puis génère le code JavaScript
     * nécessaire à l'affichage de la notification. Supprime la notification de
     * la session après récupération pour éviter les affichages répétés.
     * 
     * @return string Script JavaScript pour afficher la notification, ou chaîne vide si aucune notification.
     */
    private function getNotificationScript(): string
    {
        // Initialisation de la session si elle n'est pas déjà active
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérification de la présence d'une notification en session
        if (!isset($_SESSION['notification'])) {
            return '';
        }
        
        // Récupération et normalisation des données de notification
        $notification = $_SESSION['notification'];
        $message = $notification['message'] ?? '';
        $type = $notification['type'] ?? 'success';
        $duration = $notification['duration'] ?? 5000;
        
        // Suppression immédiate de la notification de la session (flash message pattern)
        // Évite les affichages répétés si l'utilisateur recharge la page
        unset($_SESSION['notification']);
        
        // Utilisation de json_encode pour échapper correctement les chaînes JavaScript
        // Plus sûr que la concaténation directe pour éviter les injections JS
        // JSON_HEX flags convertissent les caractères dangereux en séquences hex
        $messageJs = json_encode($message, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $typeJs = json_encode($type, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        
        // Génération du script JavaScript pour appeler showNotification()
        return "<script>showNotification({$messageJs}, {$typeJs}, {$duration});</script>";
    }

    /**
     * Génère le HTML complet d'une page avec tous ses composants.
     * 
     * Point d'entrée principal du template de base. Assemble tous les composants :
     * head (meta tags, SEO, CDN), body, header (navigation), main (contenu),
     * footer, notifications, et scripts JavaScript. Détermine dynamiquement
     * si le header/footer doivent être affichés selon le type de page.
     * 
     * @param string $title Titre de la page affiché dans l'onglet du navigateur.
     * @param string $content Contenu principal de la page (généré par le template spécifique).
     * @param string $currentRoute Route actuelle pour la navigation et état actif des liens.
     * @return string HTML complet de la page (doctype + html + head + body avec tous les composants).
     */
    public function render(string $title = 'GSB Central', string $content = '', string $currentRoute = ''): string
    {
        // Récupération du script de notification flash si présent en session
        $notificationScript = $this->getNotificationScript();

        // Liste des pages qui n'utilisent PAS le header/footer (page de connexion, 404, etc.)
        // Ces pages ont leur propre layout complet sans navigation
        $noHeaderFooterPages = ['Connexion', '404 Not Found'];
        $hasHeaderFooter = !in_array($title, $noHeaderFooterPages);

        // Génération conditionnelle des composants selon le type de page
        // Pages internes : header + footer + layout avec container
        // Pages spéciales (login, 404) : pas de header/footer, layout plein écran
        $header = $hasHeaderFooter ? $this->getHeader($currentRoute) : '';
        $footer = $hasHeaderFooter ? $this->getFooter() : '';
        
        // Wrapper du contenu : container responsive pour pages internes, contenu brut pour pages spéciales
        $main = $hasHeaderFooter ? 
            <<<HTML
                <main class="container mx-auto mt-4 p-4 flex-grow">
                    {$content}
                </main>
            HTML : $content;
        
        // Classes du body : flex-col pour pages internes (push footer en bas), simple pour pages spéciales
        $bodyClass = $hasHeaderFooter ? 'bg-gray-100 font-sans antialiased min-h-screen flex flex-col' : 'bg-gray-100 font-sans antialiased min-h-screen';
        
        // Échappement du titre pour éviter les injections XSS dans la balise <title>
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
            <script src="/public/assets/js/stock-price-formatter.js"></script>
            {$notificationScript}
        </body>
        </html>
        HTML;
    }
}