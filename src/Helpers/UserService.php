<?php

namespace App\Helpers;

use App\Repositories\UserRepository;

/**
 * Classe UserService
 * 
 * Service pour la gestion des données utilisateur courantes.
 * Récupère les informations utilisateur depuis la base de données en utilisant
 * l'email stocké en session et vérifie l'intégrité avec un hash de sécurité.
 */
class UserService
{
    /**
     * Repository pour l'accès aux données des utilisateurs.
     * 
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * Initialise le service utilisateur en créant l'instance du repository.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->userRepository = new UserRepository();
    }

    /**
     * Récupère les données complètes de l'utilisateur actuellement connecté.
     * 
     * Vérifie la présence de l'email en session et du hash de sécurité,
     * puis récupère les données utilisateur depuis la base de données.
     * Cette méthode garantit que les données sont toujours à jour et
     * empêche les attaques par manipulation de session.
     *
     * @return array Les données complètes de l'utilisateur (user_id, email, firstname, lastname, fk_company_id, fk_function_id, function_name) si authentifié, tableau vide sinon.
     */
    public function getCurrentUser(): array
    {
        // Démarre la session si elle n'est pas déjà active
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        // Vérification de la présence des données de session requises
        if (!isset($_SESSION['user_email']) || !isset($_SESSION['user_hash'])) {
            return [];
        }

        $email = $_SESSION['user_email'];
        $sessionHash = $_SESSION['user_hash'];

        // Récupération des données utilisateur depuis la base de données
        $user = $this->userRepository->getUserByEmail($email);
        
        if (empty($user)) {
            return [];
        }

        // Vérification de l'intégrité avec le hash de sécurité
        // Le hash est généré avec l'ID utilisateur pour empêcher les attaques
        $expectedHash = $this->generateUserHash($user['user_id']);
        
        if (!hash_equals($sessionHash, $expectedHash)) {
            // Hash invalide : possible tentative d'attaque
            $this->clearSession();
            return [];
        }

        return $user;
    }

    /**
     * Génère un hash de sécurité pour un utilisateur.
     * 
     * Utilise l'ID utilisateur et une clé secrète pour créer un hash
     * qui permet de vérifier l'intégrité de la session et d'empêcher
     * les attaques par manipulation de session.
     *
     * @param int $userId L'ID de l'utilisateur.
     * @return string Le hash de sécurité généré.
     */
    public function generateUserHash(int $userId): string
    {
        // Utilise une clé secrète combinée avec l'ID utilisateur
        // En production, cette clé devrait être dans une variable d'environnement
        $secretKey = 'GSB_CENTRAL_SECRET_KEY_2024';
        return hash('sha256', $secretKey . $userId);
    }

    /**
     * Vérifie si l'utilisateur actuel est authentifié.
     * 
     * Vérifie la présence des données de session et l'intégrité
     * des données avec le hash de sécurité.
     *
     * @return bool True si l'utilisateur est authentifié, false sinon.
     */
    public function isAuthenticated(): bool
    {
        $user = $this->getCurrentUser();
        return !empty($user);
    }

    /**
     * Récupère l'ID de l'utilisateur actuellement connecté.
     * 
     * @return int|null L'ID de l'utilisateur ou null si non authentifié.
     */
    public function getCurrentUserId(): ?int
    {
        $user = $this->getCurrentUser();
        return $user['user_id'] ?? null;
    }

    /**
     * Récupère l'email de l'utilisateur actuellement connecté.
     * 
     * @return string|null L'email de l'utilisateur ou null si non authentifié.
     */
    public function getCurrentUserEmail(): ?string
    {
        $user = $this->getCurrentUser();
        return $user['email'] ?? null;
    }

    /**
     * Récupère le rôle (function_id) de l'utilisateur actuellement connecté.
     * 
     * @return int|null Le function_id de l'utilisateur ou null si non authentifié.
     */
    public function getCurrentUserRole(): ?int
    {
        $user = $this->getCurrentUser();
        return $user['fk_function_id'] ?? null;
    }

    /**
     * Récupère l'ID de l'entreprise de l'utilisateur actuellement connecté.
     * 
     * @return int|null L'ID de l'entreprise ou null si non authentifié.
     */
    public function getCurrentUserCompanyId(): ?int
    {
        $user = $this->getCurrentUser();
        return $user['fk_company_id'] ?? null;
    }

    /**
     * Nettoie la session utilisateur en cas d'erreur de sécurité.
     * 
     * Supprime toutes les variables de session liées à l'authentification
     * pour éviter les sessions corrompues ou compromises.
     *
     * @return void
     */
    private function clearSession(): void
    {
        unset($_SESSION['user_email']);
        unset($_SESSION['user_hash']);
    }
}
