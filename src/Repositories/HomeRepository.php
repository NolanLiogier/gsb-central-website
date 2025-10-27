<?php

namespace App\Repositories;

/**
 * Classe HomeRepository
 * 
 * Repository pour la récupération des données de la page d'accueil.
 * Centralise les appels de données nécessaires à l'affichage du tableau de bord.
 */
class HomeRepository {
    /**
     * Récupère toutes les données nécessaires à l'affichage de la page d'accueil.
     * 
     * Cette méthode est destinée à être étendue pour récupérer des statistiques,
     * notifications, graphiques ou autres données agrégées à afficher sur le dashboard.
     * Actuellement retourne un message de test en attendant l'implémentation complète.
     *
     * @return array Les données à afficher sur la page d'accueil.
     */
    public function getDatas(): array {
        // TODO: Implémenter la récupération des vraies données (statistiques, notifications, etc.)
        return ['message' => 'Hello from HomeRepository'];
    }
}