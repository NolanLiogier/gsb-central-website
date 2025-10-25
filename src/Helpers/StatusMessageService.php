<?php

namespace App\Helpers;

class StatusMessageService
{
    public function setSuccessMessage(string $message): void
    {
        $_SESSION['notification'] = [
            'type' => 'success',
            'message' => $message,
            'duration' => 3000
        ];
    }

    public function setErrorMessage(string $message): void
    {
        $_SESSION['notification'] = [
            'type' => 'danger',
            'message' => $message,
            'duration' => 5000
        ];
    }

    public function setWarningMessage(string $message): void
    {
        $_SESSION['notification'] = [
            'type' => 'warning',
            'message' => $message,
            'duration' => 5000
        ];
    }

    /**
     * Affiche un message de statut et redirige vers une route spécifiée.
     * 
     * @param string $message Le message à afficher
     * @param string $typeMessage Le type de message (success, error, warning)
     * @param string $route La route vers laquelle rediriger
     * @param \App\Helpers\RenderService $renderService Le service de rendu
     * @return void
     */
    public function displayMessageAndRedirect(string $message, string $typeMessage, string $route, \App\Helpers\RenderService $renderService): void
    {
        match ($typeMessage) {
            'success' => $this->setSuccessMessage($message),
            'error' => $this->setErrorMessage($message),
            'warning' => $this->setWarningMessage($message),
            default => throw new \Exception('Type de message invalide'),
        };
        $renderService->render($route);
        exit();
    }
}