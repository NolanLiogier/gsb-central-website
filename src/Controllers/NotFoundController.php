<?php

namespace App\Controllers;

use App\Helpers\RenderService;

/**
 * Classe NotFoundController
 * Gère l\'affichage de la page 404 (non trouvée).
 */
class NotFoundController
{
    /**
     * Affiche la page 404.
     *
     * @return void
     */
    public function index(): void
    {
        $renderService = new RenderService();
        $renderService->render('NotFound');
        exit();
    }
}
