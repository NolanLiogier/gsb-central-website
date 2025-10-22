<?php

namespace App\Repositories;

use Config\Database;
use PDO;

/**
 * Classe TempTokenRepository
 * Gère les opérations de base de données liées aux tokens temporaires.
 */
class TempTokenRepository
{
    private Database $database;

    public function __construct()
    {
        $this->database = new Database();
    }
    /**
     * Récupère la valeur du token (hash) par son ID.
     *
     * @param int $tokenId L'ID du token à récupérer.
     * @return array La valeur du token (hash) et la date de création si trouvé, un tableau vide sinon.
     */
    public function getTokenDataById(int $tokenId): array
    {
        $conn = $this->database->getConnection();

        $sql = "SELECT value, creation_datetime FROM temp_token WHERE token_id = :token_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute(['token_id' => $tokenId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC) ?? [];

        return $result;
    }

    /**
     * Met à jour la valeur du token (hash) pour un token_id donné.
     *
     * @param int $tokenId L'ID du token à mettre à jour.
     * @param string $newTokenValue La nouvelle valeur du token (hash).
     * @return bool True si l'opération a réussi, false sinon.
     */
    public function updateTokenValue(int $tokenId, string $newTokenValue): bool
    {
        $conn = $this->database->getConnection();

        $sql = "UPDATE temp_token SET value = :new_value, creation_datetime = NOW() WHERE token_id = :token_id";
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            'new_value' => $newTokenValue,
            'token_id' => $tokenId
        ]);

        if (!$result || !($stmt->rowCount() > 0)) {
            return false;
        }

        return true;
    }
}
