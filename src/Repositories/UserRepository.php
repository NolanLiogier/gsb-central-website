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
     * Récupère les données complètes d'un utilisateur par son adresse email.
     * 
     * Utilisé lors de l'authentification pour vérifier l'existence de l'utilisateur
     * et récupérer ses informations (notamment le mot de passe hashé pour la vérification).
     * Inclut le rôle/fonction de l'utilisateur via un LEFT JOIN, ainsi que l'entreprise
     * et la fonction de l'utilisateur pour la gestion des permissions.
     *
     * @param string $email L'adresse email de l'utilisateur à rechercher.
     * @return array Les données de l'utilisateur (user_id, email, password, firstname, lastname, fk_company_id, fk_function_id, function_name) si trouvé, un tableau vide sinon.
     */
    public function getUserByEmail(string $email): array
    {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return [];
            }

            // Requête avec paramètres nommés pour éviter les injections SQL
            // LEFT JOIN sur functions pour récupérer le rôle même si non défini
            $sql = "SELECT u.user_id, u.email, u.password, u.firstname, u.lastname, u.fk_company_id, u.fk_function_id, f.function_name 
                    FROM users u 
                    LEFT JOIN functions f ON u.fk_function_id = f.function_id 
                    WHERE u.email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            
            // ?? opérateur null coalescing pour retourner un tableau vide si fetch() retourne false
            $result = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result;
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

}