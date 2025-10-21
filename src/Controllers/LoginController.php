<?php

namespace App\Controllers;

use App\Repositories\LoginRepository;
use App\Helpers\RenderService;

class LoginController
{
    public function index(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['email']) {
            $this->login();
            exit;
        }
        else {
            $this->displayLogin();
            exit;
        }
    }

    public function login(): void
    {
        $email = $_POST['email'] ?? '';
        $loginRepository = new LoginRepository();

        if (!$loginRepository->checkEmailExists(email: $email)) {
            $_SESSION['message'] = 'Utilisateur inconnu';
            $this->displayLogin();
            exit;
        }

        $_SESSION['logged_in'] = true;
        $_SESSION['email'] = $email;
        $_SESSION['message'] = 'Vous avez bien connecté';
        header('Location: /home');
        exit;
    }

    public function displayLogin($datas = []): void
    {
        $renderService = new RenderService();
        $renderService->render("LoginTemplate", $datas);
        exit();
    }
}

