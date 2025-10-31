<?php

namespace Templates;

/**
 * Classe ModifyCompanyTemplate
 * 
 * Gère l'affichage du template de modification d'entreprise.
 * Crée un formulaire complet pour modifier les informations d'une entreprise :
 * nom, SIRET, SIREN, secteur et commercial assigné. Gère les listes déroulantes
 * avec présélection des valeurs courantes et validation des formats.
 */
class ModifyCompanyTemplate {
    /**
     * Génère le contenu HTML du formulaire de modification d'entreprise.
     * 
     * Crée un formulaire avec validation des champs SIRET (14 chiffres) et SIREN (9 chiffres).
     * Remplit les listes déroulantes de secteurs et commerciaux avec les options disponibles
     * et présélectionne les valeurs actuelles de l'entreprise. Inclut des boutons d'annulation
     * et de sauvegarde.
     *
     * @param array $datas Données de l'entreprise (company_id, company_name, siret, siren, delivery_address, selected_sector_id, selected_salesman_id) et listes (sectors, salesmen).
     * @return string HTML complet du formulaire de modification.
     */
    public function displayModifyCompany(array $datas = []): string {

        if (empty($datas)) {
            return '';
        }

        $sectors = $datas['sectors'] ?? [];
        $salesmen = $datas['salesmen'] ?? [];

        $companyName = htmlspecialchars($datas['company_name'] ?? '');
        $siret = htmlspecialchars($datas['siret'] ?? '');
        $siren = htmlspecialchars($datas['siren'] ?? '');
        $deliveryAddress = htmlspecialchars($datas['delivery_address'] ?? '');
        $companyIdValue = htmlspecialchars($datas['company_id'] ?? 0);

        // Détection du mode et récupération des textes appropriés
        $modeData = $this->determineMode($datas);
        $isEditMode = $modeData['isEditMode'];
        $pageTitle = $modeData['pageTitle'];
        $buttonText = $modeData['buttonText'];
        
        // Détection du type d'utilisateur (1 = commercial, 2 = client)
        $userFunctionId = $datas['user_function_id'] ?? null;
        $isClient = $userFunctionId == 2;

        $sectorOptions = $this->generateSectorOptions($sectors, $datas['selected_sector_id'] ?? null);
        $salesmanOptions = $this->generateSalesmanOptions($salesmen, $datas['selected_salesman_id'] ?? null);

        $modifyCompanyContent = <<<HTML
        <!-- En-tête de la page -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">{$pageTitle}</h1>
        </div>

        <!-- Formulaire de modification d'entreprise -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <!-- En-tête du formulaire -->
            <div class="bg-white px-8 py-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-800">Informations de l'entreprise</h2>
                    </div>
        HTML;

        // Ajout du bouton supprimer en mode modification uniquement à droite (pas pour les clients)
        if ($isEditMode && !$isClient) {
            $modifyCompanyContent .= <<<HTML
                        <form method="POST" action="/ModifyCompany" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette entreprise ?');" class="inline-block">
                            <input type="hidden" name="companyId" value="{$companyIdValue}">
                            <input type="hidden" name="deleteCompany" value="true">
                            <button 
                                type="submit" 
                                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-red-600 border border-red-700 rounded-lg hover:bg-red-700 transition-colors duration-200">
                                <i class="fas fa-trash mr-2"></i>
                                Supprimer
                            </button>
                        </form>
        HTML;
        } else {
            $modifyCompanyContent .= <<<HTML
                        <div></div>
        HTML;
        }

        $modifyCompanyContent .= <<<HTML
                </div>
            </div>
            
            <form class="p-8" method="POST" action="/ModifyCompany">
                <!-- Champ caché pour détecter le mode (modification ou création) -->
        HTML;

        // Génération des champs cachés selon le mode (modification ou création)
        $hiddenFields = $this->generateHiddenFields($isEditMode, $companyIdValue);

        $modifyCompanyContent .= $hiddenFields;
        $modifyCompanyContent .= <<<HTML

                <!-- Grille des champs du formulaire -->
                <div class="space-y-6">
                    <!-- Nom de l'entreprise -->
                    <div>
                        <label for="companyName" class="block text-sm font-semibold text-gray-700 mb-3">
                            Nom de l'entreprise <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="companyName" 
                            name="companyName" 
                            value="{$companyName}"
                            required
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                            placeholder="Entrez le nom de l'entreprise">
                    </div>

                    <!-- Grille pour SIRET et SIREN -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- SIRET -->
                        <div>
                            <label for="siret" class="block text-sm font-semibold text-gray-700 mb-3">
                                SIRET <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="siret" 
                                name="siret" 
                                value="{$siret}"
                                required
                                pattern="[0-9]{14}"
                                maxlength="14"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                                placeholder="12345678901234">
                            <p class="text-xs text-gray-500 mt-2">Le SIRET doit contenir exactement 14 chiffres</p>
                        </div>

                        <!-- SIREN -->
                        <div>
                            <label for="siren" class="block text-sm font-semibold text-gray-700 mb-3">
                                SIREN <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="siren" 
                                name="siren" 
                                value="{$siren}"
                                required
                                pattern="[0-9]{9}"
                                maxlength="9"
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                                placeholder="123456789">
                            <p class="text-xs text-gray-500 mt-2">Le SIREN doit contenir exactement 9 chiffres</p>
                        </div>
                    </div>

                    <!-- Adresse de livraison -->
                    <div>
                        <label for="deliveryAddress" class="block text-sm font-semibold text-gray-700 mb-3">
                            Adresse de livraison
                        </label>
                        <input 
                            type="text" 
                            id="deliveryAddress" 
                            name="deliveryAddress" 
                            value="{$deliveryAddress}"
                            maxlength="255"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900 placeholder-gray-500"
                            placeholder="Entrez l'adresse de livraison">
                        <p class="text-xs text-gray-500 mt-2">Adresse de livraison de l'entreprise (optionnel)</p>
                    </div>

                    <!-- Grille pour Secteur et Commercial -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Secteur -->
                        <div>
                            <label for="sector" class="block text-sm font-semibold text-gray-700 mb-3">
                                Secteur d'activité <span class="text-red-500">*</span>
                            </label>
                            <select 
                                id="sector" 
                                name="sector" 
                                required
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900">
                                {$sectorOptions}
                            </select>
                        </div>

                        <!-- Commercial assigné -->
                        <div>
                            <label for="salesman" class="block text-sm font-semibold text-gray-700 mb-3">
                                Commercial assigné
                            </label>
        HTML;
        
        // Ajout du select commercial avec les bons attributs selon le type d'utilisateur
        // Pour les clients, on retire le name pour qu'il ne soit pas envoyé dans la requête POST
        if ($isClient) {
            $modifyCompanyContent .= <<<HTML
                            <select 
                                id="salesman" 
                                disabled
                                class="w-full px-4 py-3 bg-gray-100 border border-gray-200 rounded-lg text-gray-600 cursor-not-allowed" 
                                style="pointer-events: none;">
                                {$salesmanOptions}
                            </select>
                            <p class="text-xs text-gray-500 mt-2">Vous ne pouvez pas modifier votre commercial assigné</p>
        HTML;
        } 
        else {
            $modifyCompanyContent .= <<<HTML
                            <select 
                                id="salesman" 
                                name="salesman" 
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900">
                                {$salesmanOptions}
                            </select>
        HTML;
        }
        
        $modifyCompanyContent .= <<<HTML
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
        HTML;

        // Affichage du bouton Retour uniquement si ce n'est pas un client
        $returnButtonHtml = '';
        if (!$isClient) {
            $returnButtonHtml = '<a href="/Companies" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">Retour</a>';
        }

        $modifyCompanyContent .= $returnButtonHtml;
        $modifyCompanyContent .= <<<HTML
                    <button 
                        type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-lg">
                        {$buttonText}
                    </button>
                </div>
            </form>
        </div>

HTML;

        return $modifyCompanyContent;
    }

    /**
     * Détermine si on est en mode modification ou création et retourne les textes appropriés.
     * 
     * Analyse les données fournies pour savoir si une entreprise existe déjà (mode modification)
     * ou si on est en train de créer une nouvelle entreprise (mode création).
     * Retourne un tableau avec les informations nécessaires (mode, titres, textes des boutons).
     *
     * @param array $datas Données de l'entreprise avec éventuellement un company_id.
     * @return array Tableau contenant ['isEditMode', 'pageTitle', 'buttonText'].
     */
    private function determineMode(array $datas): array {
        $isEditMode = false;
        if (!empty($datas['company_id']) && $datas['company_id'] != '0') {
            $isEditMode = true;
        }

        $modeData = [
            'isEditMode' => $isEditMode,
            'pageTitle' => "Ajouter une entreprise",
            'buttonText' => "Créer l'entreprise"
        ];

        if ($isEditMode) {
            $modeData = [
                'isEditMode' => $isEditMode,
                'pageTitle' => "Modifier vos informations",
                'buttonText' => "Enregistrer les modifications"
            ];
        }

        return $modeData;
    }

    /**
     * Génère les champs cachés du formulaire selon le mode (modification ou création).
     * 
     * Détecte si on est en mode modification (avec ID) ou création (sans ID) et génère
     * les champs cachés appropriés pour que le contrôleur traite correctement la requête.
     *
     * @param bool $isEditMode True si en mode modification, false si en mode création.
     * @param string|int $companyId ID de l'entreprise (échappé pour l'affichage).
     * @return string Champs cachés HTML générés.
     */
    private function generateHiddenFields(bool $isEditMode, $companyId): string {
        if ($isEditMode) {
            // Mode modification : on transmet l'ID et le flag de mise à jour
            return <<<HTML

                <input type="hidden" name="companyId" value="{$companyId}">
                <input type="hidden" name="updateCompany" value="true">
            HTML;
        } else {
            // Mode création : on transmet le flag de nouvelle entreprise et de création
            return <<<HTML

                <input type="hidden" name="newCompany" value="true">
                <input type="hidden" name="createCompany" value="true">
            HTML;
        }
    }

    /**
     * Génère les options HTML pour le select des secteurs d'activité.
     * 
     * Crée les balises <option> pour peupler la liste déroulante des secteurs.
     * Présélectionne le secteur actuel de l'entreprise et échappe toutes les
     * valeurs pour éviter les injections XSS.
     *
     * @param array $sectors Liste des secteurs disponibles (avec sector_id et sector_name).
     * @param int|null $selectedSectorId ID du secteur actuellement sélectionné (pour présélection).
     * @return string Options HTML générées pour le <select>.
     */
    private function generateSectorOptions(array $sectors, ?int $selectedSectorId = null): string {
        $options = '<option value="">Sélectionnez un secteur</option>';
        
        // Génération de chaque option avec échappement XSS et présélection si nécessaire
        foreach ($sectors as $sector) {
            $sectorId = htmlspecialchars($sector['sector_id']);
            $sectorName = htmlspecialchars($sector['sector_name']);
            $selected = ($selectedSectorId !== null && $sectorId == $selectedSectorId) ? 'selected' : '';
            $options .= "<option value=\"{$sectorId}\" {$selected}>{$sectorName}</option>";
        }
        
        return $options;
    }

    /**
     * Génère les options HTML pour le select des commerciaux.
     * 
     * Crée les balises <option> pour peupler la liste déroulante des commerciaux.
     * Présélectionne le commercial actuellement assigné (peut être "Aucun").
     * Échappe toutes les valeurs pour éviter les injections XSS.
     *
     * @param array $salesmen Liste des commerciaux disponibles.
     * @param int|null $selectedSalesmanId ID du commercial actuellement assigné (peut être null).
     * @return string Options HTML générées pour le <select>.
     */
    private function generateSalesmanOptions(array $salesmen, ?int $selectedSalesmanId = null): string {
        $options = '<option value="">Aucun commercial assigné</option>';
        
        // Génération de chaque option avec concaténation prénom + nom et présélection
        foreach ($salesmen as $salesman) {
            $salesmanId = htmlspecialchars($salesman['salesman_id']);
            $salesmanName = htmlspecialchars($salesman['salesman_firstname'] . ' ' . $salesman['salesman_lastname']);
            $selected = ($selectedSalesmanId !== null && $salesmanId == $selectedSalesmanId) ? 'selected' : '';
            $options .= "<option value=\"{$salesmanId}\" {$selected}>{$salesmanName}</option>";
        }
        
        return $options;
    }
}
