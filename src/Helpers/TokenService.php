<?php

namespace App\Helpers;

use App\Repositories\LoginRepository;
use App\Repositories\TempTokenRepository;

/**
 * Classe TokenService
 * Gère la logique métier liée aux tokens temporaires.
 */
class TokenService
{
    private LoginRepository $loginRepository;
    private TempTokenRepository $tempTokenRepository;

    /**
     * Constructeur du TokenService.
     * Initialise les repositories nécessaires.
     */
    public function __construct()
    {
        $this->loginRepository = new LoginRepository();
        $this->tempTokenRepository = new TempTokenRepository();
    }

    /**
     * Vérifie si un token est encore valide en fonction de l'email de l'utilisateur.
     * Un token est considéré comme invalide s'il a plus de 4 heures.
     *
     * @param string $email L'adresse email de l'utilisateur.
     * @return bool True si le token est valide, false sinon.
     */
    public function isTokenValid(string $email): bool
    {
        $user = $this->loginRepository->getUserByEmail($email);
        if (empty($user)) {
            return false;
        }

        $tokenData = $this->tempTokenRepository->getTokenDataById($user['fk_token_id']);
        if (!$tokenData) {
            return false;
        }

        $tokenCreatedAt = new \DateTime($tokenData['creation_datetime']);
        $currentTime = new \DateTime();
        return $currentTime->diff($tokenCreatedAt)->h < 4;
    }
}
