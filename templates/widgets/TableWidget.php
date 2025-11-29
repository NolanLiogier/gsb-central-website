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
        $emptyMessage = $config['emptyMessage'] ?? 'Aucune donnée';
        $emptyIcon = $config['emptyIcon'] ?? 'fa-inbox';
        $tableClass = $config['tableClass'] ?? '';
        $rowCallback = $config['rowCallback'] ?? null;

        $totalItems = count($rows);
        $colspan = count($headers);
        
        // Générer toutes les lignes (pagination gérée en JavaScript)
        $html = <<<HTML
        <div class="bg-white shadow-sm rounded-lg overflow-hidden" data-items-per-page="{$itemsPerPage}">
            <table class="min-w-full divide-y divide-gray-200 {$tableClass}">
HTML;

        // Génération des en-têtes
        $html .= $this->generateHeaders($headers);
        
        // Génération du corps
        $html .= '<tbody class="bg-white divide-y divide-gray-200">';
        
        if (empty($rows)) {
            $html .= $this->generateEmptyState($colspan, $emptyMessage, $emptyIcon);
        } else {
            // Générer toutes les lignes simplement, sans pagination côté serveur
            $html .= $this->generateRows($rows, $headers, $rowCallback);
        }
        
        $html .= <<<HTML
                </tbody>
            </table>
        </div>
        
        <!-- Pagination JavaScript -->
        <div id="table-pagination" class="mt-6 flex items-center justify-between" style="display: none;">
            <div class="text-sm text-gray-700">
                Page <span class="font-medium" id="current-page">1</span> sur <span class="font-medium" id="total-pages">1</span>
            </div>
            <div class="flex items-center space-x-2">
                <button id="prev-page" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    <i class="fas fa-chevron-left"></i> Précédent
                </button>
                <div class="flex items-center space-x-1" id="page-numbers"></div>
                <button id="next-page" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                    Suivant <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <script src="/public/assets/js/table-pagination.js"></script>
        <script>
            // Initialiser la pagination JavaScript après le chargement
            document.addEventListener('DOMContentLoaded', function() {
                initTablePagination('table', {$itemsPerPage});
            });
        </script>
HTML;

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

}
