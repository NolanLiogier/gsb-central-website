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
            $query = "SELECT c.company_id, c.company_name, s.sector_name, 
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
}
