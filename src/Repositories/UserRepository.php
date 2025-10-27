<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;

/**
 * Classe UserRepository
 * 
 * Repository pour l'accès aux données des utilisateurs.
 * Gère les opérations de base de données liées à l'authentification et à la récupération
 * des informations utilisateur, notamment la vérification des identifiants.
 */
class UserRepository
{
    /**
     * Instance de la classe Database pour accéder à la connexion.
     * 
     * @var Database
     */
    private Database $database;

    /**
     * Initialise le repository en créant l'instance de la classe Database.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Récupère les données complètes d'un utilisateur par son adresse email.
     * 
     * Utilisé lors de l'authentification pour vérifier l'existence de l'utilisateur
     * et récupérer ses informations (notamment le mot de passe hashé pour la vérification).
     * Inclut le rôle/fonction de l'utilisateur via un LEFT JOIN.
     *
     * @param string $email L'adresse email de l'utilisateur à rechercher.
     * @return array Les données de l'utilisateur (user_id, email, password, firstname, lastname, function_name) si trouvé, un tableau vide sinon.
     */
    public function getUserByEmail(string $email): array
    {
        try {
            // Récupération de la connexion à la base de données
            $conn = $this->database->getConnection();

            // Requête avec paramètres nommés pour éviter les injections SQL
            // LEFT JOIN sur functions pour récupérer le rôle même si non défini
            $sql = "SELECT u.user_id, u.email, u.password, u.firstname, u.lastname, f.function_name 
                    FROM users u 
                    LEFT JOIN functions f ON u.fk_function_id = f.function_id 
                    WHERE u.email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            // ?? opérateur null coalescing pour retourner un tableau vide si fetch() retourne false
            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
            return $result;
        } catch (PDOException $e) {
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

}