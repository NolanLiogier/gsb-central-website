<?php

namespace Templates;

/**
 * Classe CompanyTemplate
 * Gère l'affichage du template de la page des entreprises.
 */
class CompaniesTemplate {
    /**
     * Affiche le contenu HTML de la page des entreprises.
     *
     * @param array $datas Données à utiliser pour le template.
     * @return string The full HTML page.
     */
    public function displayCompanies($datas): string {
        $companiesContent = <<<HTML
        <!-- Titre de la page et bouton d'ajout -->
        <div class="mb-8 flex justify-between items-center">
            <h1 class="text-4xl font-bold text-gray-800">Entreprises</h1>
            <button class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg shadow-lg transition-colors duration-200 flex items-center space-x-2">
                <i class="fas fa-plus text-white"></i>
                <span>Ajouter une entreprise</span>
            </button>
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

        // Génération des lignes du tableau avec les données des entreprises
        foreach ($datas['companies'] as $company) {
            // Construction du nom du commercial
            $salesmanName = 'Non assigné';
            if (!empty($company['firstname']) && !empty($company['lastname'])) {
                $salesmanName = ucfirst($company['firstname']) . ' ' . ucfirst($company['lastname']);
            }
            
            // Conversion du nom de l'entreprise en majuscules
            $companyNameUpper = strtoupper($company['company_name']);
            
            $companiesContent .= <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="submitForm('{$company['company_id']}')">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                {$companyNameUpper}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$company['sector_name']}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">
                                {$salesmanName}
                            </div>
                        </td>
                    </tr>
                HTML;
        }

        $companiesContent .= <<<HTML
                </tbody>
            </table>
        </div>

        <form action="/modify-company" method="POST" id="company-form">
            <input type="hidden" name="companyId" id="companyId" value="0" required>
            <input type="hidden" name="renderModifyCompany" id="renderModifyCompany" value="true" required>
        </form>

        <script>
            function submitForm(companyId) {
                document.getElementById('companyId').value = companyId;
                document.getElementById('company-form').submit();
            }
        </script>

        HTML;

        return $companiesContent;
    }
}
