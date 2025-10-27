<?php

namespace App\Controllers;

use App\Helpers\RenderService;

/**
 * Classe NotFoundController
 * Gère l'affichage de la page 404 (non trouvée).
 */
class NotFoundController
{
    /**
     * Service de rendu des templates.
     * 
     * @var RenderService
     */
    private RenderService $renderService;

    /**
     * Initialise le contrôleur en créant le service de rendu nécessaire.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->renderService = new RenderService();
    }

    /**
     * Affiche la page 404.
     * 
     * Cette page est affichée lorsqu'une route n'existe pas ou n'est pas reconnue
     * par le routeur. Elle fournit un feedback à l'utilisateur concernant l'erreur.
     *
     * @return void
     */
    public function index(): void
    {
        // Affichage du template 404 sans données supplémentaires
        $this->renderService->displayTemplates('NotFound', [], "404 Not Found");
        exit();
    }
}
