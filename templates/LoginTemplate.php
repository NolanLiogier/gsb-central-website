<?php

namespace Templates;

/**
 * Classe LoginTemplate
 * Gère l'affichage du template de la page de connexion.
 */
class LoginTemplate
{
    /**
     * Affiche le contenu HTML de la page de connexion.
     *
     * @param array $datas Données à utiliser pour le template, par exemple les messages d'erreur.
     * @return void
     */
    public static function displayLogin(array $datas = []): void
    {
        // Example of how to use datas if needed, e.g., for error messages
        $errorMessage = $datas['error'] ?? '';

        echo <<<'HTML'
            <!DOCTYPE html>
            <html lang="fr">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Connexion - GSB</title>
                <script src="https://cdn.tailwindcss.com"></script>
            </head>
            <body class="bg-gray-100 flex items-center justify-center h-screen">
                <div class="bg-white p-8 rounded-lg shadow-md w-full max-w-md border-t-4 border-blue-500">
                    <h1 class="text-2xl font-bold text-center mb-2">Bienvenue</h1>
                    <p class="text-gray-600 text-center mb-6">Connectez-vous pour continuer vers votre écosystème GSB.</p>
                    <form action="/login" method="POST">
                        <div class="mb-4">
                            <label for="email" class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                            <input type="email" id="email" name="email" placeholder="vous@exemple.com" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                        </div>
                        <div class="flex items-center justify-between">
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                                Se connecter
                            </button>
                        </div>
                    </form>
                    <!-- Display error message if present -->
                    <?php if (!empty($errorMessage)): ?>
                        <p class="text-red-500 text-center mt-4"><?php echo htmlspecialchars($errorMessage); ?></p>
                    <?php endif; ?>
                </div>
            </body>
            </html>
        HTML;
    }
}