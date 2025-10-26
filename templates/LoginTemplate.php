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
     * @param array $datas Les données à passer au template.
     * @return string Le contenu HTML de la page.
     */
    public static function displayLogin(array $datas = []): string
    {
        $loginContent = <<<HTML
            <div class="min-h-screen flex items-center justify-center bg-gray-100 py-12 px-4 sm:px-6 lg:px-8">
                <div class="max-w-md w-full space-y-8">
                    <div class="bg-white p-8 rounded-lg shadow-md border-t-4 border-blue-500">
                        <h1 class="text-2xl font-bold text-center mb-2">Bienvenue</h1>
                        <p class="text-gray-600 text-center mb-6">Connectez-vous pour continuer vers votre écosystème GSB.</p>
                        <form action="/user" method="POST">
                            <div class="mb-4">
                                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">E-mail</label>
                                <input type="email" id="email" name="email" placeholder="vous@exemple.com" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="mb-6">
                                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">Mot de passe</label>
                                <input type="password" id="password" name="password" placeholder="Votre mot de passe" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            <div class="flex items-center justify-between">
                                <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline w-full">
                                    Se connecter
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        HTML;

        return $loginContent;
    }
}
