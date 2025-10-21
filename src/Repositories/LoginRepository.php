<?php

namespace App\Repositories;

use Config\Database;

class LoginRepository
{
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