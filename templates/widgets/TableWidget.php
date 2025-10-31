<?php

namespace Templates\widgets;

/**
 * Classe TableWidget
 * 
 * Widget réutilisable pour créer des tableaux dynamiques avec pagination.
 * Permet de construire dynamiquement les en-têtes, les lignes et le corps du tableau.
 */
class TableWidget {
    /**
     * Génère un tableau complet avec pagination.
     * 
     * @param array $config Configuration du tableau :
     *   - 'headers' => array : Tableau d'en-têtes (chaque élément peut être string ou array avec 'label' et 'class')
     *   - 'rows' => array : Tableau de lignes (chaque ligne est un tableau de cellules)
     *   - 'itemsPerPage' => int : Nombre d'éléments par page (défaut: 10)
     *   - 'baseUrl' => string : URL de base pour la pagination (défaut: $_SERVER['REQUEST_URI'] sans page)
     *   - 'emptyMessage' => string : Message à afficher si aucune donnée (défaut: 'Aucune donnée')
     *   - 'emptyIcon' => string : Classe FontAwesome pour l'icône vide (défaut: 'fa-inbox')
     *   - 'tableClass' => string : Classes CSS supplémentaires pour le tableau
     *   - 'rowCallback' => callable : Callback pour générer les cellules d'une ligne (optionnel)
     * @return string HTML du tableau avec pagination.
     */
    public function render(array $config): string {
        // Configuration par défaut
        $headers = $config['headers'] ?? [];
        $rows = $config['rows'] ?? [];
        $itemsPerPage = $config['itemsPerPage'] ?? 10;
        $baseUrl = $config['baseUrl'] ?? $this->getBaseUrl();
        $emptyMessage = $config['emptyMessage'] ?? 'Aucune donnée';
        $emptyIcon = $config['emptyIcon'] ?? 'fa-inbox';
        $tableClass = $config['tableClass'] ?? '';
        $rowCallback = $config['rowCallback'] ?? null;

        // Pagination
        $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $totalItems = count($rows);
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        
        // Validation de la page courante
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        
        // Calcul des indices pour la pagination
        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $paginatedRows = array_slice($rows, $startIndex, $itemsPerPage);
        
        $colspan = count($headers);
        
        $html = <<<HTML
        <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 {$tableClass}">
HTML;

        // Génération des en-têtes
        $html .= $this->generateHeaders($headers);
        
        // Génération du corps
        $html .= '<tbody class="bg-white divide-y divide-gray-200">';
        
        if (empty($paginatedRows) && $totalItems === 0) {
            $html .= $this->generateEmptyState($colspan, $emptyMessage, $emptyIcon);
        } else {
            $html .= $this->generateRows($paginatedRows, $headers, $rowCallback);
        }
        
        $html .= <<<HTML
                </tbody>
            </table>
        </div>
HTML;

        // Ajout de la pagination si nécessaire
        if ($totalItems > $itemsPerPage) {
            $html .= $this->generatePagination($currentPage, $totalPages, $baseUrl);
        }

        return $html;
    }

    /**
     * Génère les en-têtes du tableau.
     * 
     * @param array $headers Tableau d'en-têtes.
     * @return string HTML des en-têtes.
     */
    private function generateHeaders(array $headers): string {
        if (empty($headers)) {
            return '';
        }

        $html = '<thead class="bg-gray-50"><tr>';
        
        foreach ($headers as $header) {
            if (is_array($header)) {
                $label = htmlspecialchars($header['label'] ?? '');
                $class = htmlspecialchars($header['class'] ?? 'px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider');
                $html .= "<th class=\"{$class}\">{$label}</th>";
            } else {
                $label = htmlspecialchars($header);
                $html .= "<th class=\"px-6 py-3 text-left text-xs font-medium text-blue-600 uppercase tracking-wider\">{$label}</th>";
            }
        }
        
        $html .= '</tr></thead>';
        
        return $html;
    }

    /**
     * Génère les lignes du tableau.
     * 
     * @param array $rows Lignes à afficher.
     * @param array $headers En-têtes pour déterminer le nombre de colonnes.
     * @param callable|null $rowCallback Callback pour personnaliser le rendu des lignes.
     * @return string HTML des lignes.
     */
    private function generateRows(array $rows, array $headers, ?callable $rowCallback): string {
        $html = '';
        
        foreach ($rows as $row) {
            if ($rowCallback !== null) {
                // Utiliser le callback pour générer la ligne
                $html .= call_user_func($rowCallback, $row);
            } else {
                // Génération automatique des cellules
                $html .= '<tr class="hover:bg-gray-50 transition-colors">';
                
                foreach ($row as $cell) {
                    $cellContent = is_array($cell) ? ($cell['content'] ?? '') : $cell;
                    $cellClass = is_array($cell) ? ($cell['class'] ?? 'px-6 py-4 whitespace-nowrap') : 'px-6 py-4 whitespace-nowrap';
                    
                    $cellContentEscaped = htmlspecialchars($cellContent);
                    $cellClassEscaped = htmlspecialchars($cellClass);
                    
                    $html .= "<td class=\"{$cellClassEscaped}\"><div class=\"text-sm text-gray-900\">{$cellContentEscaped}</div></td>";
                }
                
                $html .= '</tr>';
            }
        }
        
        return $html;
    }

    /**
     * Génère l'état vide du tableau.
     * 
     * @param int $colspan Nombre de colonnes à fusionner.
     * @param string $message Message à afficher.
     * @param string $icon Classe FontAwesome pour l'icône.
     * @return string HTML de l'état vide.
     */
    private function generateEmptyState(int $colspan, string $message, string $icon): string {
        $colspanEscaped = htmlspecialchars($colspan);
        $messageEscaped = htmlspecialchars($message);
        $iconEscaped = htmlspecialchars($icon);
        
        return <<<HTML
                    <tr>
                        <td colspan="{$colspanEscaped}" class="px-6 py-12 text-center">
                            <div class="text-gray-500">
                                <i class="fas {$iconEscaped} text-4xl mb-4"></i>
                                <p class="text-lg font-medium">{$messageEscaped}</p>
                            </div>
                        </td>
                    </tr>
HTML;
    }

    /**
     * Génère les contrôles de pagination.
     * 
     * @param int $currentPage Numéro de la page courante.
     * @param int $totalPages Nombre total de pages.
     * @param string $baseUrl URL de base pour la pagination.
     * @return string HTML des contrôles de pagination.
     */
    private function generatePagination(int $currentPage, int $totalPages, string $baseUrl): string {
        $currentPageEscaped = htmlspecialchars($currentPage);
        $totalPagesEscaped = htmlspecialchars($totalPages);
        
        // Construction de l'URL de base en préservant les autres paramètres GET
        $queryParams = $_GET;
        unset($queryParams['page']);
        $queryString = !empty($queryParams) ? '?' . http_build_query($queryParams) . '&' : '?';
        
        $prevDisabled = $currentPage <= 1 ? 'pointer-events-none opacity-50' : '';
        $nextDisabled = $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '';
        
        $prevPage = $currentPage > 1 ? $currentPage - 1 : 1;
        $nextPage = $currentPage < $totalPages ? $currentPage + 1 : $totalPages;
        
        $prevPageEscaped = htmlspecialchars($prevPage);
        $nextPageEscaped = htmlspecialchars($nextPage);
        
        // Construction des numéros de page
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        $pageNumbersHtml = '';
        
        // Afficher la première page si on n'est pas au début
        if ($startPage > 1) {
            $firstPageEscaped = htmlspecialchars(1);
            $pageNumbersHtml .= <<<HTML
                    <a href="{$baseUrl}{$queryString}page={$firstPageEscaped}" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        1
                    </a>
HTML;
            if ($startPage > 2) {
                $pageNumbersHtml .= '<span class="px-2 text-gray-500">...</span>';
            }
        }
        
        // Afficher les pages autour de la page courante
        for ($i = $startPage; $i <= $endPage; $i++) {
            $pageEscaped = htmlspecialchars($i);
            if ($i == $currentPage) {
                $pageNumbersHtml .= <<<HTML
                    <span class="px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md">
                        {$pageEscaped}
                    </span>
HTML;
            } else {
                $pageNumbersHtml .= <<<HTML
                    <a href="{$baseUrl}{$queryString}page={$pageEscaped}" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        {$pageEscaped}
                    </a>
HTML;
            }
        }
        
        // Afficher la dernière page si on n'est pas à la fin
        if ($endPage < $totalPages) {
            $lastPageEscaped = htmlspecialchars($totalPages);
            if ($endPage < $totalPages - 1) {
                $pageNumbersHtml .= '<span class="px-2 text-gray-500">...</span>';
            }
            $pageNumbersHtml .= <<<HTML
                    <a href="{$baseUrl}{$queryString}page={$lastPageEscaped}" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        {$lastPageEscaped}
                    </a>
HTML;
        }
        
        return <<<HTML

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Page <span class="font-medium">{$currentPageEscaped}</span> sur <span class="font-medium">{$totalPagesEscaped}</span>
            </div>
            <div class="flex items-center space-x-2">
                <a href="{$baseUrl}{$queryString}page={$prevPageEscaped}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 {$prevDisabled} transition-colors">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
                
                <!-- Numéros de page -->
                <div class="flex items-center space-x-1">
                    {$pageNumbersHtml}
                </div>
                
                <a href="{$baseUrl}{$queryString}page={$nextPageEscaped}" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 {$nextDisabled} transition-colors">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        </div>

HTML;
    }

    /**
     * Obtient l'URL de base pour la pagination.
     * 
     * @return string URL de base sans le paramètre page.
     */
    private function getBaseUrl(): string {
        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $parts = parse_url($requestUri);
        return $parts['path'] ?? '/';
    }
}
