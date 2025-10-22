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

        $sql = "SELECT user_id, email, password, firstname, lastname, fk_function_id, fk_token_id FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];
        return $result;
    }

}