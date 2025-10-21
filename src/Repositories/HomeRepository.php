<?php

namespace App\Repositories;

/**
 * Classe HomeRepository
 * Gère la récupération des données pour la page d\'accueil.
 */
class HomeRepository {
    /**
     * Récupère les données nécessaires à l\'affichage de la page d\'accueil.
     *
     * @return array Les données à afficher sur la page d\'accueil.
     */
    public function getDatas(): array {
        return ['message' => 'Hello from HomeRepository'];
    }
}