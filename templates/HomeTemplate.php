<?php

namespace Templates;

/**
 * Classe HomeTemplate
 * 
 * Gère l'affichage du template de la page d'accueil (dashboard).
 * Template minimaliste actuellement, destiné à être enrichi avec
 * des widgets, statistiques, graphiques et autres données agrégées.
 */
class HomeTemplate {
    /**
     * Génère le contenu HTML de la page d'accueil.
     * 
     * Affiche actuellement un message simple. Destiné à être étendu
     * pour afficher des statistiques, graphiques, notifications,
     * et autres widgets du tableau de bord.
     *
     * @param array $datas Données de la page d'accueil (statistiques, notifications, etc.).
     * @return string HTML complet de la page d'accueil.
     */
    public function displayHome(array $datas = []): string {
        // Échappement XSS du message pour éviter les injections
        $message = htmlspecialchars($datas['message'] ?? '');
        
        $homeContent = <<<HTML
            <h1 class="text-3xl font-bold text-gray-800">{$message}</h1>
        HTML;

        return $homeContent;
    }
}