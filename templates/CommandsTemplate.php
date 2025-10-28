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
HTML;

        // Affichage du bouton de création selon le rôle
        $currentUser = $datas['currentUser'] ?? [];
        $userFunctionId = $currentUser['fk_function_id'] ?? null;
        
        // Seuls les clients peuvent créer des commandes
        if ($userFunctionId == 2) {
            $commandContent .= <<<HTML
            <form action="/ModifyCommand" method="POST" class="inline-block">
                <input type="hidden" name="newCommand" value="true">
                <input type="hidden" name="renderAddCommand" value="true">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus text-white"></i>
                    <span>Créer une commande</span>
                </button>
            </form>
HTML;
        }
        
        $commandContent .= <<<HTML
        </div>

        <!-- Tableau des commandes -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
HTML;

        // Affichage des colonnes selon le rôle
        if ($userFunctionId == 1 || $userFunctionId == 3) { // Commercial ou Logisticien
            $commandContent .= <<<HTML
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            CLIENT
                        </th>
HTML;
        }
        
        if ($userFunctionId == 1 || $userFunctionId == 3) { // Commercial ou Logisticien
            $commandContent .= <<<HTML
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            ENTREPRISE
                        </th>
HTML;
        }
        
        $commandContent .= <<<HTML
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            DATE DE LIVRAISON
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            DATE DE CRÉATION
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            STATUT
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            ACTIONS
                        </th>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
        HTML;

        // Récupération sécurisée de la liste des commandes (tableau vide si absente)
        // Les commandes sont maintenant directement dans $datas, pas dans une sous-clé
        $commands = [];
        if (is_array($datas)) {
            // Filtrer les éléments qui sont des commandes (ont un command_id)
            foreach ($datas as $key => $value) {
                if (is_array($value) && isset($value['command_id'])) {
                    $commands[] = $value;
                }
            }
        }

        // Calcul du nombre de colonnes pour le colspan
        $colspan = 3; // Date livraison, Date création, Statut
        if ($userFunctionId == 1 || $userFunctionId == 3) {
            $colspan += 2; // Client, Entreprise
        }
        $colspan += 1; // Actions
        
        // Vérification si la liste des commandes est vide
        if (empty($commands)) {
            // Message à afficher lorsqu'il n'y a aucune commande
            $commandContent .= <<<HTML
                    <tr>
                        <td colspan="{$colspan}" class="px-6 py-12 text-center">
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
                $commandId = htmlspecialchars($command['command_id'] ?? '0');
                $deliveryDate = isset($command['delivery_date']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($command['delivery_date']))) : '-';
                $createdAt = isset($command['created_at']) ? htmlspecialchars(date('d/m/Y H:i', strtotime($command['created_at']))) : '-';
                $statusName = htmlspecialchars($command['status_name'] ?? '-');
                // Récupérer le status_id avec gestion appropriée du type
                $statusId = isset($command['fk_status_id']) ? (int)$command['fk_status_id'] : null;
                // En cas d'absence, utiliser status_id depuis la requête si disponible
                if ($statusId === null && isset($command['status_id'])) {
                    $statusId = (int)$command['status_id'];
                }
                
                // Informations client et entreprise pour commerciaux et logisticiens
                $clientName = '';
                $companyName = '';
                if ($userFunctionId == 1 || $userFunctionId == 3) {
                    $clientName = htmlspecialchars(($command['firstname'] ?? '') . ' ' . ($command['lastname'] ?? ''));
                    $companyName = htmlspecialchars($command['company_name'] ?? '-');
                }
                
                // Génération des actions selon le rôle et le statut
                // Vérifier que statusId n'est pas null avant de caster
                $actionsHtml = '';
                if ($statusId !== null) {
                    $actionsHtml = $this->generateActionButtons((int)$commandId, (int)$statusId, (int)$userFunctionId);
                }
                
                $commandContent .= <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors">
HTML;
                
                // Colonnes selon le rôle
                if ($userFunctionId == 1 || $userFunctionId == 3) {
                    $commandContent .= <<<HTML
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$clientName}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$companyName}</div>
                        </td>
HTML;
                }
                
                $commandContent .= <<<HTML
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$deliveryDate}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$createdAt}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$statusName}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {$actionsHtml}
                        </td>
                    </tr>
                HTML;
            }
        }

        $commandContent .= <<<HTML
                </tbody>
            </table>
        </div>

        HTML;

        return $commandContent;
    }

    /**
     * Génère les boutons d'action selon le rôle de l'utilisateur et le statut de la commande.
     * 
     * @param int $commandId ID de la commande.
     * @param int $statusId ID du statut de la commande.
     * @param int $userFunctionId ID de la fonction de l'utilisateur.
     * @return string HTML des boutons d'action.
     */
    private function generateActionButtons(int $commandId, int $statusId, int $userFunctionId): string {
        $buttons = '';
        
        // Bouton Modifier (tous les rôles selon les règles)
        if (($userFunctionId == 2 && $statusId == 3) || $userFunctionId == 1) {
            $buttons .= <<<HTML
                <form action="/ModifyCommand" method="POST" class="inline-block mr-2">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="renderModifyCommand" value="true">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-2 py-1 rounded transition-colors">
                        <i class="fas fa-edit"></i>
                    </button>
                </form>
HTML;
        }
        
        // Bouton Supprimer (tous les rôles selon les règles)
        if (($userFunctionId == 2 && $statusId == 3) || $userFunctionId == 1) {
            $buttons .= <<<HTML
                <form action="/Commands" method="POST" class="inline-block mr-2" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="deleteCommand" value="true">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white text-xs px-2 py-1 rounded transition-colors">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
HTML;
        }
        
        // Bouton Valider (Commercial uniquement, statut "en attente")
        if ($userFunctionId == 1 && $statusId == 3) {
            $buttons .= <<<HTML
                <form action="/Commands" method="POST" class="inline-block mr-2" onsubmit="return confirm('Êtes-vous sûr de vouloir valider cette commande ?')">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="validateCommand" value="true">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded transition-colors">
                        <i class="fas fa-check"></i>
                    </button>
                </form>
HTML;
        }
        
        // Bouton Voir (Logisticien uniquement - consultation uniquement)
        if ($userFunctionId == 3) {
            $buttons .= <<<HTML
                <form action="/ModifyCommand" method="POST" class="inline-block mr-2">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="renderModifyCommand" value="true">
                    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white text-xs px-2 py-1 rounded transition-colors" title="Voir les détails de la commande">
                        <i class="fas fa-eye"></i>
                    </button>
                </form>
                <form action="/Commands" method="POST" class="inline-block mr-2" onsubmit="return confirm('Êtes-vous sûr de vouloir envoyer cette commande ?')">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="sendCommand" value="true">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded transition-colors" title="Envoyer la commande">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
        HTML;
        }
        
        return $buttons ?: '<span class="text-gray-400 text-xs">Aucune action</span>';
    }
}

