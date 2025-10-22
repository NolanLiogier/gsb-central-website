<?php

namespace App\Controllers;

use App\Repositories\HomeRepository;
use App\Helpers\RenderService;

/**
 * Classe HomeController
 * Gère l\'affichage de la page d\'accueil.
 */
class HomeController {
    private HomeRepository $homeRepository;
    private RenderService $renderService;

    public function __construct()
    {
        $this->homeRepository = new HomeRepository();
        $this->renderService = new RenderService();
    }
    /**
     * Affiche la page d\'accueil avec les données récupérées.
     *
     * @return void
     */
    public function index(): void {
        $datas = $this->homeRepository->getDatas();
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
        $this->renderService->render("Home", $datas);
        exit();
    }
}
