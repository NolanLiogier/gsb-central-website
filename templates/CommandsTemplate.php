<?php

namespace Templates;

use Templates\widgets\TableWidget;

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
        
        $commandContent .= '<div class="flex items-center space-x-4">';
        $commandContent .= $this->generateCreateButton($userFunctionId);
        $commandContent .= '</div>';
        
        $commandContent .= <<<HTML
        </div>
HTML;

        // Récupération sécurisée de la liste des commandes
        $allCommands = $this->extractCommands($datas);
        
        // Génération des en-têtes selon le rôle
        $headers = $this->buildHeaders($userFunctionId);
        
        // Utilisation du TableWidget pour générer le tableau
        $tableWidget = new TableWidget();
        $tableHtml = $tableWidget->render([
            'headers' => $headers,
            'rows' => $allCommands,
            'itemsPerPage' => 10,
            'baseUrl' => '/Commands',
            'emptyMessage' => 'Aucune commande',
            'emptyIcon' => 'fa-shopping-cart',
            'rowCallback' => function($command) use ($userFunctionId) {
                return $this->generateCommandRow($command, $userFunctionId);
            }
        ]);
        
        $commandContent .= $tableHtml;

        return $commandContent;
    }

    /**
     * Définit les droits d'un utilisateur selon son rôle et le statut de la commande.
     * 
     * @param int $userFunctionId ID de la fonction de l'utilisateur.
     * @param int $statusId ID du statut de la commande.
     * @return array Tableau contenant les droits : ['canModify' => bool, 'canDelete' => bool, 'canValidate' => bool]
     */
    private function defineUserRights(int $userFunctionId, int $statusId): array {
        $rights = [
            'canModify' => false,
            'canDelete' => false,
            'canValidate' => false
        ];
        
        switch ($userFunctionId) {
            case 1: // Commercial
                // Peut modifier et supprimer les commandes en attente (3) et validées (1)
                // Ne peut pas modifier/supprimer les commandes envoyées (2)
                if ($statusId == 3 || $statusId == 1) {
                    $rights['canModify'] = true;
                    $rights['canDelete'] = true;
                }
                // Peut valider uniquement les commandes en attente (3)
                if ($statusId == 3) {
                    $rights['canValidate'] = true;
                }
                break;
                
            case 2: // Client
                // Peut modifier et supprimer uniquement les commandes en attente (3)
                if ($statusId == 3) {
                    $rights['canModify'] = true;
                    $rights['canDelete'] = true;
                }
                break;
                
            case 3: // Logisticien
                // Ne peut que consulter (pas de modification/suppression/validation)
                // Les droits restent à false
                break;
        }
        
        return $rights;
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
        
        // Récupération des droits de l'utilisateur
        $rights = $this->defineUserRights($userFunctionId, $statusId);
        
        // Bouton Modifier
        if ($rights['canModify']) {
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
        
        // Bouton Supprimer
        if ($rights['canDelete']) {
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
        
        // Bouton Valider
        if ($rights['canValidate']) {
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
        HTML;
            // Bouton Envoyer uniquement pour les commandes validées (statut 1)
            // Les commandes déjà envoyées (statut 2) ou en attente (statut 3) ne peuvent pas être envoyées
            if ($statusId == 1) {
                $buttons .= <<<HTML
                <form action="/Commands" method="POST" class="inline-block mr-2" onsubmit="return confirm('Êtes-vous sûr de vouloir envoyer cette commande ?')">
                    <input type="hidden" name="commandId" value="{$commandId}">
                    <input type="hidden" name="sendCommand" value="true">
                    <button type="submit" class="bg-green-500 hover:bg-green-600 text-white text-xs px-2 py-1 rounded transition-colors" title="Envoyer la commande">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
        HTML;
            }
        }
        
        return $buttons ?: '<span class="text-gray-400 text-xs">Aucune action</span>';
    }

    /**
     * Génère le bouton de création de commande.
     * 
     * Affiche le bouton uniquement pour les clients (role 2).
     *
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @return string HTML du bouton de création ou chaîne vide.
     */
    private function generateCreateButton(?int $userFunctionId): string
    {
        // Seuls les clients peuvent créer des commandes
        if ($userFunctionId == 2) {
            return <<<HTML
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
        
        return '';
    }

    /**
     * Construit les en-têtes du tableau selon le rôle de l'utilisateur.
     * 
     * Affiche les colonnes Client et Entreprise uniquement pour les commerciaux
     * et logisticiens (roles 1 et 3).
     *
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @return array Tableau des en-têtes pour le TableWidget.
     */
    private function buildHeaders(?int $userFunctionId): array
    {
        $headers = [];
        
        // Commercial ou Logisticien : affichage des colonnes Client et Entreprise
        if ($userFunctionId == 1 || $userFunctionId == 3) {
            $headers[] = 'CLIENT';
            $headers[] = 'ENTREPRISE';
        }
        
        // Colonnes communes
        $headers[] = 'DATE DE LIVRAISON';
        $headers[] = 'DATE DE CRÉATION';
        $headers[] = 'STATUT';
        $headers[] = 'ACTIONS';
        
        return $headers;
    }

    /**
     * Extrait les commandes depuis les données.
     * 
     * Filtre les éléments qui sont des commandes (ont un command_id)
     * depuis le tableau de données fourni.
     *
     * @param array $datas Données brutes contenant potentiellement des commandes.
     * @return array Liste des commandes extraites.
     */
    private function extractCommands(array $datas): array
    {
        $commands = [];
        
        if (is_array($datas)) {
            // Filtrer les éléments qui sont des commandes (ont un command_id)
            foreach ($datas as $key => $value) {
                if (is_array($value) && isset($value['command_id'])) {
                    $commands[] = $value;
                }
            }
        }
        
        return $commands;
    }

    /**
     * Génère une ligne du tableau pour une commande.
     * 
     * Crée une ligne de tableau avec toutes les informations de la commande :
     * client, entreprise (si applicable), dates, statut et actions.
     *
     * @param array $command Données de la commande.
     * @param int|null $userFunctionId ID de la fonction de l'utilisateur.
     * @return string HTML de la ligne de commande générée.
     */
    private function generateCommandRow(array $command, ?int $userFunctionId): string
    {
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
        $actionsHtml = '';
        if ($statusId !== null) {
            $actionsHtml = $this->generateActionButtons((int)$commandId, (int)$statusId, (int)$userFunctionId);
        }
        
        $html = '<tr class="hover:bg-gray-50 transition-colors">';
        
        // Colonnes selon le rôle
        if ($userFunctionId == 1 || $userFunctionId == 3) {
            $html .= <<<HTML
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$clientName}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{$companyName}</div>
                        </td>
HTML;
        }
        
        $html .= <<<HTML
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
        
        return $html;
    }
}
