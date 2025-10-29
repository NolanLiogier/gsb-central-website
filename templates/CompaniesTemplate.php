<?php

namespace Templates;

/**
 * Classe CompaniesTemplate
 * 
 * Gère l'affichage du template de la page de liste des entreprises.
 * Crée un tableau interactif affichant les entreprises avec leurs secteurs
 * et commerciaux assignés. Gère le clic sur une ligne pour accéder à
 * la modification de l'entreprise.
 */
class CompaniesTemplate {
    /**
     * Génère le contenu HTML de la page de liste des entreprises.
     * 
     * Crée un tableau avec toutes les entreprises récupérées, affiche
     * leurs informations (nom, secteur, commercial) et ajoute un formulaire
     * caché pour gérer les clics sur les lignes du tableau.
     *
     * @param array $datas Tableau contenant 'companies' (liste des entreprises) et 'sectors' (secteurs uniques).
     * @return string HTML complet de la page de liste des entreprises.
     */
    public function displayCompanies(array $datas = []): string {
        $companiesContent = <<<HTML
        <!-- Titre de la page et bouton d'ajout -->
        <div class="mb-8 flex justify-between items-center">
            <h1 class="text-4xl font-bold text-gray-800">Entreprises</h1>
            <form action="/ModifyCompany" method="POST" class="inline-block">
                <input type="hidden" name="newCompany" value="true">
                <input type="hidden" name="renderAddCompany" value="true">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                    <i class="fas fa-plus text-white"></i>
                    <span>Ajouter une entreprise</span>
                </button>
            </form>
        </div>

        <!-- Tableau des entreprises -->
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            ENTREPRISE
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            SECTEUR
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider">
                            COMMERCIAL
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
        HTML;

        // Récupération sécurisée de la liste des entreprises (tableau vide si absente)
        $companies = $datas['companies'] ?? [];

        // Vérification si la liste des entreprises est vide
        if (empty($companies)) {
            $companiesContent .= $this->generateEmptyState();
        } else {
            // Génération dynamique des lignes du tableau pour chaque entreprise
            $companiesContent .= $this->generateCompanyRows($companies);
        }

        $companiesContent .= <<<HTML
                </tbody>
            </table>
        </div>

        <!-- Formulaire caché pour soumettre l'ID de l'entreprise lors du clic sur une ligne -->
        <!-- Permet de naviguer vers la page de modification sans formulaire visible -->
        <form action="/ModifyCompany" method="POST" id="company-form">
            <input type="hidden" name="companyId" id="companyId" value="0" required>
            <input type="hidden" name="renderModifyCompany" id="renderModifyCompany" value="true" required>
        </form>

        <!-- Script JavaScript pour gérer le clic sur les lignes du tableau -->
        <!-- Insère l'ID de l'entreprise dans le formulaire et le soumet automatiquement -->
        <script>
            function submitForm(companyId) {
                document.getElementById('companyId').value = companyId;
                document.getElementById('company-form').submit();
            }
        </script>

        HTML;

        return $companiesContent;
    }

    /**
     * Génère le message d'état vide lorsqu'il n'y a aucune entreprise.
     * 
     * Crée une ligne de tableau avec un message informatif centré.
     *
     * @return string HTML de l'état vide généré.
     */
    private function generateEmptyState(): string
    {
        return <<<HTML
                    <tr>
                        <td colspan="3" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas fa-building text-4xl mb-4"></i>
                                <p class="text-lg font-medium">Aucune entreprise trouvée</p>
                                <p class="text-sm mt-2">Vous n'avez aucune entreprise assignée pour le moment.</p>
                            </div>
                        </td>
                    </tr>
HTML;
    }

    /**
     * Génère les lignes du tableau pour chaque entreprise.
     * 
     * Crée une ligne de tableau avec les informations de l'entreprise :
     * nom, secteur et commercial assigné. Gère le cas "non assigné" pour le commercial.
     *
     * @param array $companies Liste des entreprises à afficher.
     * @return string HTML des lignes d'entreprises générées.
     */
    private function generateCompanyRows(array $companies): string
    {
        $html = '';
        
        foreach ($companies as $company) {
            // Construction du nom complet du commercial avec gestion du cas "non assigné"
            $salesmanName = $this->formatSalesmanName($company);
            
            // Normalisation du nom d'entreprise en majuscules pour cohérence visuelle
            $companyNameUpper = strtoupper($company['company_name']);
            
            // Échappement XSS de toutes les valeurs pour éviter les injections
            $companyId = htmlspecialchars($company['company_id']);
            $companyName = htmlspecialchars($companyNameUpper);
            $sectorName = htmlspecialchars($company['sector_name']);
            $salesmanNameEscaped = htmlspecialchars($salesmanName);
            
            $html .= <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="submitForm('{$companyId}')">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {$companyName}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$sectorName}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$salesmanNameEscaped}
                            </div>
                        </td>
                    </tr>
HTML;
        }
        
        return $html;
    }

    /**
     * Formate le nom complet du commercial.
     * 
     * Affiche "Prénom Nom" si les deux sont présents, sinon "Non assigné".
     *
     * @param array $company Données de l'entreprise avec firstname et lastname.
     * @return string Nom formaté du commercial.
     */
    private function formatSalesmanName(array $company): string
    {
        if (!empty($company['firstname']) && !empty($company['lastname'])) {
            return ucfirst($company['firstname']) . ' ' . ucfirst($company['lastname']);
        }
        
        return 'Non assigné';
    }
}
