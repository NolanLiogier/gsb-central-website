<?php

namespace Templates;

use Templates\BaseTemplate;

/**
 * Classe NotFoundTemplate
 * Gère l'affichage du template de la page 404.
 */
class NotFoundTemplate
{
    /**
     * Affiche le contenu HTML de la page 404.
     *
     * @param array $datas Données à utiliser pour le template (non utilisées ici).
     * @return string Le HTML complet de la page 404.
     */
    public static function displayNotFound(array $datas = []): string
    {
        ob_start(); // Démarre la mise en mémoire tampon de sortie
?>
        <div class="text-center py-12">
            <div class="bg-white mx-auto p-8 rounded-lg shadow-md max-w-lg">
                <h1 class="text-red-600 text-5xl mb-4">404</h1>
                <p class="text-xl">Oops! The page you are looking for could not be found.</p>
                <p class="mt-4"><a href="/" class="text-blue-600 hover:underline">Go to Homepage</a></p>
            </div>
        </div>
<?php
        $content = ob_get_clean(); // Récupère le contenu mis en mémoire tampon

        return BaseTemplate::render('404 Not Found', $content);
    }
}
