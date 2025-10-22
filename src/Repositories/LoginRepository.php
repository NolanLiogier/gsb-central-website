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
    /**
     * Récupère les données d'un utilisateur par son email.
     *
     * @param string $email L\'adresse email de l\'utilisateur.
     * @return array Les données de l\'utilisateur si l\'email existe, un tableau vide sinon.
     */
    public function getUserByEmail(string $email): array
    {
        $database = new Database();
        $conn = $database->getConnection();

        $sql = "SELECT email, password, firstname, lastname FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // PDO::fetch retourne false si aucun résultat, on doit gérer ce cas
        return $user !== false ? $user : [];
    }

}