<?php

namespace App\Controllers;

use App\Helpers\RenderService;

/**
 * Classe NotFoundController
 * Gère l\'affichage de la page 404 (non trouvée).
 */
class NotFoundController
{

    private RenderService $renderService;

    public function __construct()
    {
        $this->renderService = new RenderService();
    }

    /**
     * Affiche la page 404.
     *
    * @return void
     */
    public function index(): void
    {
        $this->renderService->displayTemplates('NotFound', [], "404 Not Found");
        exit();
    }
}
