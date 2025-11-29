<?php

namespace Templates;

use Templates\widgets\TableWidget;

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
        
        <!-- Champ de recherche -->
        <div class="mb-6">
            <div class="relative">
                <input type="text" 
                       id="company-search" 
                       placeholder="Rechercher par nom d'entreprise..." 
                       class="w-full md:w-1/3 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
HTML;

        // Récupération sécurisée de la liste des entreprises (tableau vide si absente)
        $companies = $datas['companies'] ?? [];

        // Génération des en-têtes
        $headers = ['ENTREPRISE', 'SECTEUR', 'COMMERCIAL'];

        // Utilisation du TableWidget pour générer le tableau
        $tableWidget = new TableWidget();
        $tableHtml = $tableWidget->render([
            'headers' => $headers,
            'rows' => $companies,
            'itemsPerPage' => 10,
            'baseUrl' => '/Companies',
            'emptyMessage' => 'Aucune entreprise trouvée',
            'emptyIcon' => 'fa-building',
            'rowCallback' => function($company) {
                return $this->generateCompanyRow($company);
            }
        ]);

        $companiesContent .= $tableHtml;

        $companiesContent .= <<<HTML

        <!-- Formulaire caché pour soumettre l'ID de l'entreprise lors du clic sur une ligne -->
        <!-- Permet de naviguer vers la page de modification sans formulaire visible -->
        <form action="/ModifyCompany" method="POST" id="company-form">
            <input type="hidden" name="companyId" id="companyId" value="0" required>
            <input type="hidden" name="renderModifyCompany" id="renderModifyCompany" value="true" required>
        </form>

        <!-- Script JavaScript pour gérer le clic sur les lignes du tableau -->
        <!-- Insère l'ID de l'entreprise dans le formulaire et le soumet automatiquement -->
        <script src="/public/assets/js/table-search.js"></script>
        <script>
            function submitForm(companyId) {
                document.getElementById('companyId').value = companyId;
                document.getElementById('company-form').submit();
            }
            
            // Initialisation de la recherche (recherche dans la première colonne : nom d'entreprise)
            document.addEventListener('DOMContentLoaded', function() {
                initTableSearch('company-search', 'table', [0]);
            });
        </script>

        HTML;

        return $companiesContent;
    }

    /**
     * Génère une ligne du tableau pour une entreprise.
     * 
     * Crée une ligne de tableau avec les informations de l'entreprise :
     * nom, secteur et commercial assigné. Gère le cas "non assigné" pour le commercial.
     *
     * @param array $company Données de l'entreprise à afficher.
     * @return string HTML de la ligne d'entreprise générée.
     */
    private function generateCompanyRow(array $company): string
    {
        // Construction du nom complet du commercial avec gestion du cas "non assigné"
        $salesmanName = $this->formatSalesmanName($company);
        
        // Normalisation du nom d'entreprise en majuscules pour cohérence visuelle
        // Utilisation de mb_strtoupper pour gérer correctement les caractères accentués (é → É, à → À, etc.)
        $companyNameUpper = mb_strtoupper($company['company_name'], 'UTF-8');
        
        // Échappement XSS de toutes les valeurs pour éviter les injections
        $companyId = htmlspecialchars($company['company_id']);
        $companyIdJson = json_encode($company['company_id'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        $companyName = htmlspecialchars($companyNameUpper);
        $sectorName = htmlspecialchars($company['sector_name']);
        $salesmanNameEscaped = htmlspecialchars($salesmanName);
        
        return <<<HTML
                    <tr class="hover:bg-gray-50 transition-colors cursor-pointer" onclick="submitForm({$companyIdJson})">
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
