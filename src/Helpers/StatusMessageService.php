<?php

namespace App\Helpers;

use Routing\Router;

/**
 * Classe StatusMessageService
 * 
 * Service de gestion des messages de statut pour l'utilisateur.
 * Stocke les messages de succès, d'erreur ou d'avertissement dans la session
 * pour affichage temporaire sur la page suivante (pattern flash messages).
 */
class StatusMessageService
{
    /**
     * Router pour les redirections après définition de messages.
     * 
     * @var Router
     */
    private Router $router;

    /**
     * Initialise le service de messages de statut en créant l'instance du router.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->router = new Router();
    }

    /**
     * Définit un message de statut dans la session pour affichage ultérieur.
     * 
     * Stocke un message flash dans la session avec son type (success, error, warning)
     * et une durée d'affichage. Le message sera affiché sur la prochaine page chargée
     * et automatiquement supprimé de la session après affichage. Pattern flash message
     * pour une meilleure UX lors des redirections après actions (POST).
     * 
     * @param string $message Le message à afficher à l'utilisateur.
     * @param string $typeMessage Le type de message : 'success', 'error', ou 'warning'.
     * @return void
     */
    public function setMessage(string $message, string $typeMessage): void
    {
        // Stockage dans la session pour affichage sur la page suivante
        // Pattern flash message : le message est affiché puis supprimé automatiquement
        $_SESSION['notification'] = [
            'type' => $typeMessage,
            'message' => $message,
            'duration' => 3000  // Durée d'affichage en millisecondes (3 secondes)
        ];
    }
}