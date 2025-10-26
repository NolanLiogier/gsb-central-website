<?php

namespace Templates;

/**
 * Classe ModifyCompanyTemplate
 * Gère l'affichage du template de modification d'entreprise.
 */
class ModifyCompanyTemplate {
    /**
     * Affiche le contenu HTML de la page de modification d'entreprise.
     *
     * @param array $datas Données contenant companyData, sectors, salesmen et errors.
     * @return string The full HTML page.
     */
    public function displayModifyCompany(array $datas = []): string {
        // Extraction des données nécessaires directement depuis $datas
        $sectors = $datas['sectors'] ?? [];
        $salesmen = $datas['salesmen'] ?? [];

        // Extraction des données de l'entreprise avec valeurs par défaut
        $companyName = $datas['company_name'] ?? '';
        $siret = $datas['siret'] ?? '';
        $siren = $datas['siren'] ?? '';
        $companyIdValue = $datas['company_id'] ?? 0;

        $sectorOptions = $this->generateSectorOptions($sectors, $datas['selected_sector_id'] ?? null);
        $salesmanOptions = $this->generateSalesmanOptions($salesmen, $datas['selected_salesman_id'] ?? null);

        $modifyCompanyContent = <<<HTML
        <!-- En-tête de la page -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Modifier vos informations</h1>
        </div>

        <!-- Formulaire de modification d'entreprise -->
        <div class="bg-white rounded-xl shadow-lg border border-gray-100 overflow-hidden">
            <!-- En-tête du formulaire -->
            <div class="bg-white px-8 py-6 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">Informations de l'entreprise</h2>
                    </div>
                    <a href="/companies" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-600 bg-gray-50 border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Retour
                    </a>
                </div>
            </div>
            
            <form class="p-8" method="POST" action="/modify-company">
                <!-- Champ caché pour l'ID de l'entreprise -->
                <input type="hidden" name="company_id" value="{$companyIdValue}">
                
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
                            placeholder="Entrez le nom de l'entreprise"
                        >
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
                                placeholder="12345678901234"
                            >
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
                                placeholder="123456789"
                            >
                            <p class="text-xs text-gray-500 mt-2">Le SIREN doit contenir exactement 9 chiffres</p>
                        </div>
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
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900"
                            >
                                {$sectorOptions}
                            </select>
                        </div>

                        <!-- Commercial assigné -->
                        <div>
                            <label for="salesman" class="block text-sm font-semibold text-gray-700 mb-3">
                                Commercial assigné
                            </label>
                            <select 
                                id="salesman" 
                                name="salesman" 
                                class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 text-gray-900"
                            >
                                {$salesmanOptions}
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-4 pt-8 mt-8 border-t border-gray-200">
                    <a href="/companies" class="px-6 py-3 text-gray-600 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200 font-medium">
                        Annuler
                    </a>
                    <button 
                        type="submit" 
                        class="px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors duration-200 font-medium shadow-sm"
                    >
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>
HTML;

        return $modifyCompanyContent;
    }

    /**
     * Génère les options HTML pour le select des secteurs.
     *
     * @param array $sectors Liste des secteurs disponibles
     * @param int|null $selectedSectorId ID du secteur sélectionné
     * @return string Options HTML générées
     */
    private function generateSectorOptions(array $sectors, ?int $selectedSectorId = null): string {
        $options = '<option value="">Sélectionnez un secteur</option>';
        
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
     * @param array $salesmen Liste des commerciaux disponibles
     * @param int|null $selectedSalesmanId ID du commercial sélectionné
     * @return string Options HTML générées
     */
    private function generateSalesmanOptions(array $salesmen, ?int $selectedSalesmanId = null): string {
        $options = '<option value="">Aucun commercial assigné</option>';
        
        foreach ($salesmen as $salesman) {
            $salesmanId = htmlspecialchars($salesman['salesman_id']);
            $salesmanName = htmlspecialchars($salesman['salesman_firstname'] . ' ' . $salesman['salesman_lastname']);
            $selected = ($selectedSalesmanId !== null && $salesmanId == $selectedSalesmanId) ? 'selected' : '';
            $options .= "<option value=\"{$salesmanId}\" {$selected}>{$salesmanName}</option>";
        }
        
        return $options;
    }

    /**
     * Génère les messages d'erreur HTML.
     *
     * @param array $errors Liste des erreurs à afficher
     * @return string Messages d'erreur HTML générés
     */
    private function generateErrorMessages(array $errors): string {
        if (empty($errors)) {
            return '';
        }

        $errorList = '';
        foreach ($errors as $error) {
            $errorList .= '<li>' . htmlspecialchars($error) . '</li>';
        }

        return <<<HTML
        <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-red-800">Erreurs de validation :</h3>
                    <div class="mt-2 text-sm text-red-700">
                        <ul class="list-disc list-inside space-y-1">
                            {$errorList}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
HTML;
    }
}
