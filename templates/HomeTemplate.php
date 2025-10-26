<?php

namespace Templates;

/**
 * Classe HomeTemplate
 * Gère l'affichage du template de la page d'accueil.
 */
class HomeTemplate {
    /**
     * Affiche le contenu HTML de la page d'accueil.
     *
     * @param array $datas Données à utiliser pour le template.
     * @return string The full HTML page.
     */
    public function displayHome($datas): string {
        $homeContent = <<<HTML
            <h1 class="text-3xl font-bold text-gray-800">{$datas['message']}</h1>
        HTML;

        return $homeContent;
    }
}