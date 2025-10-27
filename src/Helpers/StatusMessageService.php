<?php

namespace App\Helpers;

use Routing\Router;

class StatusMessageService
{

    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Affiche un message de statut et redirige vers une route spécifiée.
     * 
     * @param string $message Le message à afficher
     * @param string $typeMessage Le type de message (success, error, warning)
     * @return void
     */
    public function setMessage(string $message, string $typeMessage): void
    {
        $_SESSION['notification'] = [
            'type' => $typeMessage,
            'message' => $message,
            'duration' => 3000
        ];
    }
}