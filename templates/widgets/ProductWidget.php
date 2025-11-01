<?php

namespace Templates\widgets;

/**
 * Classe ProductWidget
 * 
 * Widget réutilisable pour afficher des cartes de produits avec pagination.
 * Permet d'afficher un nombre limité de produits par page avec navigation.
 */
class ProductWidget {
    /**
     * Génère les cartes de produits avec pagination.
     * 
     * @param array $config Configuration du widget :
     *   - 'products' => array : Liste des produits avec leurs informations
     *   - 'itemsPerPage' => int : Nombre d'éléments par page (défaut: 6)
     *   - 'baseUrl' => string : URL de base pour la pagination (défaut: $_SERVER['REQUEST_URI'] sans page)
     *   - 'userAttributes' => array : Attributs utilisateur (disabled, readonly, classes)
     *   - 'emptyMessage' => string : Message à afficher si aucun produit (défaut: 'Aucun produit disponible')
     *   - 'emptyIcon' => string : Classe FontAwesome pour l'icône vide (défaut: 'fa-box-open')
     *   - 'useFormPagination' => bool : Utiliser la pagination par formulaire au lieu de liens GET (défaut: false)
     *   - 'formId' => string : ID du formulaire parent pour la pagination par formulaire (défaut: null)
     *   - 'paginationHiddenFields' => array : Champs cachés supplémentaires à inclure dans la pagination (défaut: [])
     * @return string HTML des cartes de produits avec pagination.
     */
    public function render(array $config): string {
        // Configuration par défaut
        $products = $config['products'] ?? [];
        $itemsPerPage = $config['itemsPerPage'] ?? 6;
        $baseUrl = $config['baseUrl'] ?? $this->getBaseUrl();
        $userAttributes = $config['userAttributes'] ?? [
            'disabledAttr' => '',
            'readonlyAttr' => '',
            'disabledClass' => ''
        ];
        $emptyMessage = $config['emptyMessage'] ?? 'Aucun produit disponible';
        $emptyIcon = $config['emptyIcon'] ?? 'fa-box-open';
        $useFormPagination = $config['useFormPagination'] ?? false;
        $formId = $config['formId'] ?? null;
        $paginationHiddenFields = $config['paginationHiddenFields'] ?? [];

        // Pagination - vérifier dans POST si on utilise la pagination par formulaire
        if ($useFormPagination && isset($_POST['paginationPage'])) {
            $currentPage = max(1, (int)$_POST['paginationPage']);
        } else {
            $currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        }
        $totalItems = count($products);
        $totalPages = max(1, ceil($totalItems / $itemsPerPage));
        
        // Validation de la page courante
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        
        // Calcul des indices pour la pagination
        $startIndex = ($currentPage - 1) * $itemsPerPage;
        $paginatedProducts = array_slice($products, $startIndex, $itemsPerPage);

        $html = '';

        // Génération des cartes de produits
        if (empty($paginatedProducts) && $totalItems === 0) {
            $html .= $this->generateEmptyState($emptyMessage, $emptyIcon);
        } else {
            $html .= '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-8">';
            $html .= $this->generateProductCards($paginatedProducts, $userAttributes);
            $html .= '</div>';
        }

        // Ajout de la pagination si nécessaire
        if ($totalItems > $itemsPerPage) {
            if ($useFormPagination) {
                $html .= $this->generateFormPagination($currentPage, $totalPages, $formId, $paginationHiddenFields, $baseUrl);
            } else {
                $html .= $this->generatePagination($currentPage, $totalPages, $baseUrl);
            }
        }

        return $html;
    }

    /**
     * Génère les cartes HTML pour chaque produit.
     * 
     * @param array $products Liste des produits avec leurs informations.
     * @param array $userAttributes Attributs utilisateur (disabled, readonly, classes).
     * @return string HTML des cartes de produits générées.
     */
    private function generateProductCards(array $products, array $userAttributes): string {
        $html = '';
        
        foreach ($products as $product) {
            $productId = htmlspecialchars($product['product_id']);
            $productName = htmlspecialchars($product['product_name']);
            $price = htmlspecialchars(number_format($product['price'], 2, ',', ' '));
            $quantity = htmlspecialchars($product['quantity']);
            $orderedQuantity = htmlspecialchars($product['ordered_quantity'] ?? 0);
            $stockStatus = $quantity > 0 ? 'En stock' : 'Rupture';
            $stockClass = $quantity > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
            
            $html .= <<<HTML
            <div class="product-card bg-white rounded-xl shadow-sm border border-gray-200 p-4 hover:shadow-md transition-all duration-200 hover:border-blue-300" data-product-id="{$productId}">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-base font-semibold text-gray-900 leading-tight">{$productName}</h3>
                    <span class="{$stockClass} px-2 py-1 rounded-full text-xs font-medium whitespace-nowrap ml-2">
                        {$stockStatus}: {$quantity}
                    </span>
                </div>
                
                <div class="mb-4">
                    <div class="flex items-baseline space-x-1">
                        <span class="text-xl font-bold text-blue-600">{$price} €</span>
                        <span class="text-xs text-gray-500">par unité</span>
                    </div>
                </div>
                
                <div class="space-y-3">
                    <label class="block text-xs font-medium text-gray-700">Quantité commandée</label>
                    <div class="flex items-center justify-start">
                        <div class="flex items-center border border-gray-300 rounded-lg overflow-hidden bg-white">
                            <button type="button" onclick="decreaseQuantity('{$productId}')" 
                                    {$userAttributes['disabledAttr']}
                                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="decrease-btn-{$productId}">
                                <i class="fas fa-minus text-xs"></i>
                            </button>
                            <input type="number" id="quantity-{$productId}" name="products[{$productId}][quantity]" value="{$orderedQuantity}" min="0" max="{$quantity}" 
                                   {$userAttributes['readonlyAttr']}
                                   class="w-16 text-center border-0 focus:ring-0 focus:outline-none font-medium text-sm text-gray-900 bg-white {$userAttributes['disabledClass']}">
                            <button type="button" onclick="increaseQuantity('{$productId}', {$quantity})" 
                                    {$userAttributes['disabledAttr']}
                                    class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-50 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                    id="increase-btn-{$productId}">
                                <i class="fas fa-plus text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
HTML;
        }
        
        return $html;
    }

    /**
     * Génère l'état vide du widget.
     * 
     * @param string $message Message à afficher.
     * @param string $icon Classe FontAwesome pour l'icône.
     * @return string HTML de l'état vide.
     */
    private function generateEmptyState(string $message, string $icon): string {
        $messageEscaped = htmlspecialchars($message);
        $iconEscaped = htmlspecialchars($icon);
        
        return <<<HTML
        <div class="text-center text-gray-500 py-12">
            <i class="fas {$iconEscaped} text-4xl mb-4"></i>
            <p class="text-lg font-medium">{$messageEscaped}</p>
        </div>
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
        
        // Parse baseUrl pour extraire le chemin et les paramètres de requête existants
        $parsedUrl = parse_url($baseUrl);
        $basePath = $parsedUrl['path'] ?? $baseUrl;
        $baseQuery = [];
        
        // Extraire les paramètres de requête de baseUrl s'ils existent
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $baseQuery);
        }
        
        // Utiliser uniquement les paramètres de baseUrl (ignorer $_GET pour éviter les conflits)
        // Cela garantit que la pagination reste sur la page spécifiée dans baseUrl
        $queryParams = $baseQuery;
        unset($queryParams['page']);
        
        // Construire la chaîne de requête
        if (!empty($queryParams)) {
            $queryString = '?' . http_build_query($queryParams) . '&';
        } else {
            $queryString = '?';
        }
        
        // Utiliser le chemin sans paramètres pour baseUrl
        $baseUrl = $basePath;
        
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
     * Génère la pagination basée sur des formulaires (pour éviter les paramètres GET).
     * 
     * @param int $currentPage Numéro de la page courante.
     * @param int $totalPages Nombre total de pages.
     * @param string|null $formId ID du formulaire parent.
     * @param array $hiddenFields Champs cachés supplémentaires à inclure.
     * @param string $baseUrl URL de base pour l'action du formulaire.
     * @return string HTML de la pagination par formulaire.
     */
    private function generateFormPagination(int $currentPage, int $totalPages, ?string $formId, array $hiddenFields, string $baseUrl): string {
        $currentPageEscaped = htmlspecialchars($currentPage);
        $totalPagesEscaped = htmlspecialchars($totalPages);
        
        $prevDisabled = $currentPage <= 1 ? 'pointer-events-none opacity-50' : '';
        $nextDisabled = $currentPage >= $totalPages ? 'pointer-events-none opacity-50' : '';
        
        $prevPage = $currentPage > 1 ? $currentPage - 1 : 1;
        $nextPage = $currentPage < $totalPages ? $currentPage + 1 : $totalPages;
        
        $prevPageEscaped = htmlspecialchars($prevPage);
        $nextPageEscaped = htmlspecialchars($nextPage);
        
        // Générer les champs cachés supplémentaires
        $hiddenFieldsHtml = '';
        foreach ($hiddenFields as $name => $value) {
            $nameEscaped = htmlspecialchars($name);
            $valueEscaped = htmlspecialchars($value);
            $hiddenFieldsHtml .= "<input type=\"hidden\" name=\"{$nameEscaped}\" value=\"{$valueEscaped}\">\n                    ";
        }
        
        // Construction des numéros de page
        $startPage = max(1, $currentPage - 2);
        $endPage = min($totalPages, $currentPage + 2);
        
        $pageNumbersHtml = '';
        
        // Afficher la première page si on n'est pas au début
        if ($startPage > 1) {
            $firstPageEscaped = htmlspecialchars(1);
            $pageNumbersHtml .= <<<HTML
                    <button type="button" onclick="submitPaginationPage({$firstPageEscaped}, '{$formId}')" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        1
                    </button>
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
                    <button type="button" onclick="submitPaginationPage({$pageEscaped}, '{$formId}')" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        {$pageEscaped}
                    </button>
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
                    <button type="button" onclick="submitPaginationPage({$lastPageEscaped}, '{$formId}')" 
                       class="px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors">
                        {$lastPageEscaped}
                    </button>
HTML;
        }
        
        // Ne pas utiliser formId pour la form cachée car elle créerait un conflit avec le formulaire principal
        // Utiliser un ID unique pour le formulaire de pagination si nécessaire
        
        return <<<HTML

        <!-- Pagination par formulaire -->
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Page <span class="font-medium">{$currentPageEscaped}</span> sur <span class="font-medium">{$totalPagesEscaped}</span>
            </div>
            <div class="flex items-center space-x-2">
                <button type="button" onclick="submitPaginationPage({$prevPageEscaped}, '{$formId}')" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 {$prevDisabled} transition-colors">
                    <i class="fas fa-chevron-left"></i> Précédent
                </button>
                
                <!-- Numéros de page -->
                <div class="flex items-center space-x-1">
                    {$pageNumbersHtml}
                </div>
                
                <button type="button" onclick="submitPaginationPage({$nextPageEscaped}, '{$formId}')" 
                   class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 {$nextDisabled} transition-colors">
                    Suivant <i class="fas fa-chevron-right"></i>
                </button>
            </div>
        </div>
        
        <script>
            function submitPaginationPage(page, formId) {
                const form = formId ? document.getElementById(formId) : document.querySelector('form[method="POST"]');
                if (form) {
                    // Créer ou mettre à jour l'input de pagination
                    let pageInput = form.querySelector('input[name="paginationPage"]');
                    if (!pageInput) {
                        pageInput = document.createElement('input');
                        pageInput.type = 'hidden';
                        pageInput.name = 'paginationPage';
                        form.appendChild(pageInput);
                    }
                    pageInput.value = page;
                    
                    // S'assurer que paginationOnly est présent
                    let paginationOnlyInput = form.querySelector('input[name="paginationOnly"]');
                    if (!paginationOnlyInput) {
                        paginationOnlyInput = document.createElement('input');
                        paginationOnlyInput.type = 'hidden';
                        paginationOnlyInput.name = 'paginationOnly';
                        paginationOnlyInput.value = 'true';
                        form.appendChild(paginationOnlyInput);
                    }
                    
                    // Supprimer goToDelivery si présent pour éviter de passer à l'étape suivante lors de la pagination
                    const goToDeliveryInput = form.querySelector('input[name="goToDelivery"]');
                    if (goToDeliveryInput) {
                        goToDeliveryInput.remove();
                    }
                    
                    // Soumettre le formulaire
                    // Les quantités visibles seront préservées, les autres seront recalculées côté serveur
                    form.submit();
                }
            }
        </script>

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

