<?php

namespace App\Repositories;

use Config\Database;

/**
 * Classe LoginRepository
 * Gère les opérations de base de données liées à l\'authentification.
 */
class LoginRepository
{
    /**
     * Vérifie si un email existe déjà dans la base de données.
     *
     * @param string $email L\'adresse email à vérifier.
     * @return bool Vrai si l\'email existe, faux sinon.
     */
    public function checkEmailExists(string $email): bool
    {
        $database = new Database();
        $conn = $database->getConnection();

        $sql = "SELECT email FROM users WHERE email = :email";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();

        return $user !== false;
    }
}