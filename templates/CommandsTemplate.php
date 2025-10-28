<?php

namespace Templates;

/**
 * Classe CommandsTemplate
 * 
 * Gère l'affichage du template de la page de liste des commandes.
 * Affiche les commandes de l'utilisateur connecté de manière simple.
 */
class CommandsTemplate {
    /**
     * Génère le contenu HTML de la page de liste des commandes.
     * 
     * Crée un tableau avec toutes les commandes de l'utilisateur connecté,
     * affiche leurs informations (ID, date de livraison, date de création, statut).
     *
     * @param array $datas Tableau contenant la liste des commandes.
     * @return string HTML complet de la page de liste des commandes.
     */
    public function displayCommands(array $datas = []): string {
        $commandContent = <<<HTML
        <!-- Titre de la page et bouton d'ajout -->
        <div class="mb-8 flex justify-between items-center">
            <h1 class="text-4xl font-bold text-gray-800">Commandes</h1>
            <form action="/ModifyCommand" method="POST" class="inline-block">
                <input type="hidden" name="newCommand" value="true">
                <input type="hidden" name="renderAddCommand" value="true">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus text-white"></i>
                    <span>Créer une commande</span>
                </button>
            </form>
        </div>

        <!-- Tableau des commandes -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            ID COMMANDE
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            DATE DE LIVRAISON
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            DATE DE CRÉATION
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            STATUT
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
        HTML;

        // Récupération sécurisée de la liste des commandes (tableau vide si absente)
        $commands = is_array($datas) ? $datas : [];

        // Vérification si la liste des commandes est vide
        if (empty($commands)) {
            // Message à afficher lorsqu'il n'y a aucune commande
            $commandContent .= <<<HTML
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-shopping-cart text-4xl mb-4"></i>
                                <p class="text-lg font-medium">Aucune commande</p>
                                <p class="text-sm mt-2">Vous n'avez aucune commande pour le moment.</p>
                            </div>
                        </td>
                    </tr>
                HTML;
        } 
        else {
            // Génération dynamique des lignes du tableau pour chaque commande
            foreach ($commands as $command) {
                // Échappement XSS de toutes les valeurs pour éviter les injections
                $commandId = htmlspecialchars($command['command_id']);
                $deliveryDate = isset($command['delivery_date']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($command['delivery_date']))) : '-';
                $createdAt = isset($command['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($command['created_at']))) : '-';
                $statusId = htmlspecialchars($command['fk_status_id'] ?? '-');
                
                $commandContent .= <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="submitForm('{$commandId}')">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                #{$commandId}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$deliveryDate}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$createdAt}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                Statut: {$statusId}
                            </div>
                        </td>
                    </tr>
                HTML;
            }
        }

        $commandContent .= <<<HTML
                </tbody>
            </table>
        </div>

        <!-- Formulaire caché pour soumettre l'ID de la commande lors du clic sur une ligne -->
        <!-- Permet de naviguer vers la page de modification sans formulaire visible -->
        <form action="/ModifyCommand" method="POST" id="command-form">
            <input type="hidden" name="commandId" id="commandId" value="0" required>
            <input type="hidden" name="renderModifyCommand" id="renderModifyCommand" value="true" required>
        </form>

        <!-- Script JavaScript pour gérer le clic sur les lignes du tableau -->
        <!-- Insère l'ID de la commande dans le formulaire et le soumet automatiquement -->
        <script>
            function submitForm(commandId) {
                document.getElementById('commandId').value = commandId;
                document.getElementById('command-form').submit();
            }
        </script>

        HTML;

        return $commandContent;
    }
}

