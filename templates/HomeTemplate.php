<?php

namespace Templates;

use Templates\BaseTemplate;

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
    public static function displayHome($datas) {
        ob_start(); // Start output buffering
?>
    <h1 class="text-3xl font-bold text-gray-800"><?php echo htmlspecialchars($datas['message']); ?></h1>
<?php
        $content = ob_get_clean(); // Get the buffered content

        return BaseTemplate::render('Home Page', $content);
    }
}