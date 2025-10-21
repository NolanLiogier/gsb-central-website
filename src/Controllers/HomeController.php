<?php

namespace App\Controllers;

use App\Repositories\HomeRepository;
use App\Helpers\RenderService;
use Templates\HomeTemplates;

/**
 * Classe HomeController
 * Gère l\'affichage de la page d\'accueil.
 */
class HomeController {
    /**
     * Affiche la page d\'accueil avec les données récupérées.
     *
     * @return void
     */
    public function index(): void {
        $repository = new HomeRepository();
        $datas = $repository->getDatas();

        $this->displayHome($datas);
        exit;
    }

    /**
     * Affiche la vue de la page d\'accueil.
     *
     * @param array $datas Données à passer à la vue.
     * @return void
     */
    public function displayHome($datas = []): void
    {
        $renderService = new RenderService();
        $renderService->render("Home", $datas);
        exit();
    }
}
