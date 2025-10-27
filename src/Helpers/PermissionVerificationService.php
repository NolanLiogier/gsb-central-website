<?php

namespace App\Helpers;

use App\Repositories\CompaniesRepository;

/**
 * Classe PermissionVerificationService
 * 
 * Service pour vérifier les permissions et droits d'accès des utilisateurs.
 * Centralise toute la logique de vérification des permissions pour éviter la duplication
 * de code et garantir une sécurité cohérente dans toute l'application.
 */
class PermissionVerificationService {
    /**
     * Repository pour l'accès aux données des entreprises.
     * 
     * @var CompaniesRepository
     */
    private CompaniesRepository $companiesRepository;

    /**
     * Initialise le service en créant les dépendances nécessaires.
     * 
     * @return void
     */
    public function __construct()
    {
        $this->companiesRepository = new CompaniesRepository();
    }

    /**
     * Vérifie si un utilisateur a accès à une entreprise spécifique.
     * 
     * Vérifie les permissions selon le rôle de l'utilisateur :
     * - Clients (function_id = 2) : peuvent accéder uniquement à leur propre entreprise
     * - Commerciaux (function_id = 1) : peuvent accéder aux entreprises qui leur sont assignées
     * 
     * @param array $user Informations de l'utilisateur (user_id, fk_company_id, fk_function_id).
     * @param array $data Données contenant 'companyId' ou 'company_id' de l'entreprise à vérifier.
     * @return bool True si l'utilisateur a accès, false sinon.
     */
    public function canAccessCompany(array $user, array $data): bool {
        $userFunctionId = $user['fk_function_id'] ?? null;
        $userCompanyId = $user['fk_company_id'] ?? null;
        $userId = $user['user_id'] ?? null;
        
        // Récupération de l'ID de l'entreprise depuis les données (supporte les deux formats)
        $companyId = $data['companyId'] ?? $data['company_id'] ?? null;
        
        if (empty($companyId)) {
            return false;
        }

        // Cas des clients : vérification qu'ils accèdent uniquement à leur propre entreprise
        if (!empty($userCompanyId) && $userFunctionId == 2) {
            return $companyId == $userCompanyId;
        }

        // Cas des commerciaux : vérification qu'ils ont accès à l'entreprise assignée
        if ($userFunctionId == 1 && !empty($userId)) {
            return $this->hasSalesmanAccessToCompany($userId, (int)$companyId);
        }

        return false;
    }

    /**
     * Vérifie si un utilisateur a le droit de supprimer des entreprises.
     * 
     * Seuls les commerciaux (function_id = 1) peuvent supprimer des entreprises.
     * Vérifie également que le commercial a accès à l'entreprise spécifique.
     *
     * @param array $user Informations de l'utilisateur (user_id, fk_function_id).
     * @param array $data Données contenant 'companyId' ou 'company_id' de l'entreprise.
     * @return bool True si l'utilisateur peut supprimer, false sinon.
     */
    public function canDeleteCompany(array $user, array $data): bool {
        $userFunctionId = $user['fk_function_id'] ?? null;
        
        // Seuls les commerciaux peuvent supprimer des entreprises
        if ($userFunctionId != 1) {
            return false;
        }

        // Vérification supplémentaire : l'utilisateur doit avoir accès à l'entreprise
        return $this->canAccessCompany($user, $data);
    }

    /**
     * Vérifie si un commercial a accès à une entreprise spécifique.
     * 
     * Récupère la liste des entreprises assignées au commercial et vérifie
     * si l'entreprise recherchée est dans cette liste.
     *
     * @param int $salesmanId ID du commercial.
     * @param int $companyId ID de l'entreprise à vérifier.
     * @return bool True si le commercial a accès, false sinon.
     */
    private function hasSalesmanAccessToCompany(int $salesmanId, int $companyId): bool {
        $companiesData = $this->companiesRepository->getCompaniesBySalesman($salesmanId);
        
        if (empty($companiesData['companies'])) {
            return false;
        }

        // Parcours des entreprises assignées pour trouver une correspondance
        foreach ($companiesData['companies'] as $company) {
            if ($company['company_id'] == $companyId) {
                return true;
            }
        }

        return false;
    }
}

