<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;

/**
 * Classe CompaniesRepository
 * Gère les données des entreprises depuis la base de données.
 */
class CompaniesRepository {
    private PDO $connection;

    /**
     * Constructeur - initialise la connexion à la base de données.
     */
    public function __construct() {
        $database = new Database();
        $this->connection = $database->getConnection();
    }


    /**
     * Récupère la liste des entreprises avec leurs informations depuis la base de données.
     * Joint les tables companies, sectors et users pour récupérer les noms des secteurs et les commerciaux.
     *
     * @return array Liste des entreprises avec leurs données
     */
    public function getCompanies(): array {
        try {
            // Vérification de la connexion
            if (!$this->connection) {
                return [
                    'companies' => [],
                    'sectors' => []
                ];
            }

            // Requête unique pour récupérer les entreprises avec leurs secteurs et commerciaux
            $query = "SELECT c.company_id, c.company_name, c.siret, c.siren, s.sector_name, 
                             u.user_id, u.firstname, u.lastname
                      FROM companies c 
                      INNER JOIN sectors s ON c.fk_sector_id = s.sector_id 
                      LEFT JOIN users u ON c.fk_salesman_id = u.user_id
                      ORDER BY c.company_name";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Extraction des secteurs uniques à partir des données des entreprises
            $sectors = [];
            foreach ($companies as $company) {
                if (!empty($company['sector_name']) && !in_array($company['sector_name'], $sectors)) {
                    $sectors[] = $company['sector_name'];
                }
            }
            sort($sectors);
            
            return [
                'companies' => $companies,
                'sectors' => $sectors
            ];
            
        } catch (PDOException $e) {
            return [
                'companies' => [],
                'sectors' => []
            ];
        }
    }

    /**
     * Récupère les données d'une entreprise spécifique par son ID.
     *
     * @param int $companyId ID de l'entreprise à récupérer.
     * @return array Données de l'entreprise ou tableau vide si non trouvée.
     */
    public function getCompanyData(int $companyId): array {
        try {
            if (!$this->connection) {
                return [];
            }

            $query = "SELECT c.company_id, c.company_name, c.siret, c.siren, 
                             s.sector_id as selected_sector_id, s.sector_name as selected_sector_name,
                             u.user_id as selected_salesman_id, u.firstname as selected_salesman_firstname, u.lastname as selected_salesman_lastname
                      FROM companies c 
                      INNER JOIN sectors s ON c.fk_sector_id = s.sector_id 
                      INNER JOIN users u ON c.fk_salesman_id = u.user_id
                      WHERE c.company_id = :company_id";
            
            $stmt = $this->connection->prepare($query);
            $stmt->bindParam(':company_id', $companyId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [];
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère la liste de tous les secteurs disponibles.
     *
     * @return array Liste des secteurs avec ID et nom.
     */
    public function getSectors(): array {
        try {
            if (!$this->connection) {
                return [];
            }

            $query = "SELECT sector_id, sector_name FROM sectors ORDER BY sector_name";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère la liste de tous les commerciaux disponibles.
     *
     * @return array Liste des commerciaux avec ID, prénom et nom.
     */
    public function getSalesmen(): array {
        try {
            if (!$this->connection) {
                return [];
            }

            $query = "SELECT user_id as salesman_id, firstname as salesman_firstname, lastname as salesman_lastname 
                      FROM users u
                      WHERE u.fk_function_id = 1
                      ORDER BY u.lastname, u.firstname";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Met à jour les informations d'une entreprise dans la base de données.
     *
     * @param array $companyData Données de l'entreprise à mettre à jour.
     * @return bool True si la mise à jour a réussi, false sinon.
     */
    public function updateCompany(array $companyData): bool {
        try {
            if (!$this->connection) {
                return false;
            }

            // Validation des données requises
            if (empty($companyData['company_id']) || empty($companyData['company_name']) || 
                empty($companyData['siret']) || empty($companyData['siren']) || 
                empty($companyData['sector'])) {
                return false;
            }

            // Validation du format SIRET (14 chiffres)
            if (!preg_match('/^\d{14}$/', $companyData['siret'])) {
                return false;
            }

            // Validation du format SIREN (9 chiffres)
            if (!preg_match('/^\d{9}$/', $companyData['siren'])) {
                return false;
            }

            $query = "UPDATE companies 
                      SET company_name = :company_name, 
                          siret = :siret, 
                          siren = :siren, 
                          fk_sector_id = :sector_id, 
                          fk_salesman_id = :salesman_id
                      WHERE company_id = :company_id";
            
            $stmt = $this->connection->prepare($query);
            
            // Gestion du commercial (peut être null)
            $salesmanId = !empty($companyData['salesman']) ? $companyData['salesman'] : null;
            
            $stmt->bindParam(':company_id', $companyData['company_id'], PDO::PARAM_INT);
            $stmt->bindParam(':company_name', $companyData['company_name'], PDO::PARAM_STR);
            $stmt->bindParam(':siret', $companyData['siret'], PDO::PARAM_STR);
            $stmt->bindParam(':siren', $companyData['siren'], PDO::PARAM_STR);
            $stmt->bindParam(':sector_id', $companyData['sector'], PDO::PARAM_INT);
            $stmt->bindParam(':salesman_id', $salesmanId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            return false;
        }
    }
}
