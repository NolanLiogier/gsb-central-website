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
}