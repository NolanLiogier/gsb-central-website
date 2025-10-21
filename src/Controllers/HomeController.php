<?php

namespace App\Controllers;

use App\Repositories\HomeRepository;
use App\Helpers\RenderService;
use Templates\HomeTemplates;

class HomeController {
    public function index(): void {
        $repository = new HomeRepository();
        $datas = $repository->getDatas();

        $this->displayHome($datas);
        exit;
    }

    public function displayHome($datas = []): void
    {
        $renderService = new RenderService();
        $renderService->render("HomeTemplate", $datas);
    }
}
