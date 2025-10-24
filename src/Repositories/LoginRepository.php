<?php

namespace App\Repositories;

use Config\Database;
use PDO;

/**
 * Classe LoginRepository
 * Gère les opérations de base de données liées à l\'authentification.
 */
class LoginRepository
{
    private Database $database;

    public function __construct()
    {
        $this->database = new Database();
    }

    /**
     * Récupère les données d'un utilisateur par son email.
     *
     * @param string $email L\'adresse email de l\'utilisateur.
     * @return array Les données de l\'utilisateur si l\'email existe, un tableau vide sinon.
     */
    public function getUserByEmail(string $email): array
    {
        $conn = $this->database->getConnection();

        $sql = "SELECT u.user_id, u.email, u.password, u.firstname, u.lastname, f.function_name 
                FROM users u 
                LEFT JOIN functions f ON u.fk_function_id = f.function_id 
                WHERE u.email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
        return $result;
    }

}