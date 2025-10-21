<?php

namespace Templates;

/**
 * Classe NotFoundTemplate
 * Gère l\'affichage du template de la page 404.
 */
class NotFoundTemplate
{
    /**
     * Affiche le contenu HTML de la page 404.
     *
     * @param array $datas Données à utiliser pour le template (non utilisées ici).
     * @return void
     */
    public static function displayNotFound(array $datas = []): void
    {
        echo <<<'HTML'
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>404 Not Found</title>
                <script src="https://cdn.tailwindcss.com"></script>
            </head>
            <body class="font-sans bg-gray-100 text-gray-800 text-center py-12">
                <div class="bg-white mx-auto p-8 rounded-lg shadow-md max-w-lg">
                    <h1 class="text-red-600 text-5xl mb-4">404</h1>
                    <p class="text-xl">Oops! The page you are looking for could not be found.</p>
                    <p class="mt-4"><a href="/" class="text-blue-600 hover:underline">Go to Homepage</a></p>
                </div>
            </body>
            </html>
        HTML;
    }
}
