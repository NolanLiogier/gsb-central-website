<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;

/**
 * Classe CompaniesRepository
 * 
 * Repository pour l'accès et la manipulation des données des entreprises.
 * Fournit des méthodes pour récupérer, mettre à jour et gérer les entreprises,
 * leurs secteurs et leurs commerciaux associés depuis la base de données.
 */
class CompaniesRepository {
    /**
     * Connexion PDO à la base de données.
     * Réutilisée pour toutes les opérations de ce repository.
     * 
     * @var PDO
     */
    private PDO $connection;

    /**
     * Initialise le repository en établissant la connexion à la base de données.
     * La connexion est établie via la classe Database qui centralise la configuration.
     * 
     * @return void
     */
    public function __construct() {
        $database = new Database();
        $this->connection = $database->getConnection();
    }


    /**
     * Récupère toutes les entreprises avec leurs informations complètes.
     * 
     * Effectue une jointure avec les tables sectors et users pour récupérer
     * les secteurs d'activité et les commerciaux associés. Extrait également
     * la liste unique des secteurs pour les filtres ou statistiques.
     *
     * @return array Liste des entreprises et secteurs: ['companies' => [...], 'sectors' => [...]]
     */
    public function getCompanies(): array {
        try {
            // Vérification de la connexion avant toute requête pour éviter les erreurs fatales
            if (!$this->connection) {
                return [
                    'companies' => [],
                    'sectors' => []
                ];
            }

            // Requête optimisée : récupération en une seule fois avec JOINs
            // INNER JOIN sur sectors car une entreprise doit avoir un secteur
            // LEFT JOIN sur users car un commercial peut être optionnel
            $query = "SELECT c.company_id, c.company_name, c.siret, c.siren, s.sector_name, 
                             u.user_id, u.firstname, u.lastname
                      FROM companies c 
                      INNER JOIN sectors s ON c.fk_sector_id = s.sector_id 
                      LEFT JOIN users u ON c.fk_salesman_id = u.user_id
                      ORDER BY c.company_name";
            
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            // Récupération en mode associatif pour faciliter l'accès par nom de colonne
            $companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Extraction et déduplication des secteurs à partir des résultats
            // Plus efficace que de faire une requête séparée pour obtenir tous les secteurs
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
            // En cas d'erreur, retourner des structures vides pour éviter les erreurs fatales côté vue
            return [
                'companies' => [],
                'sectors' => []
            ];
        }
    }

    /**
     * Récupère les données complètes d'une entreprise spécifique.
     * 
     * Récupère toutes les informations nécessaires pour l'édition d'une entreprise,
     * incluant le secteur d'activité et le commercial assigné. Les alias "selected_*"
     * permettent de distinguer ces données des options du formulaire.
     *
     * @param int $companyId ID de l'entreprise à récupérer.
     * @return array Données de l'entreprise avec secteurs et commerciaux, ou tableau vide.
     */
    public function getCompanyData(int $companyId): array {
        try {
            // Vérification de la connexion avant la requête
            if (!$this->connection) {
                return [];
            }

            // Préfixe "selected_" pour distinguer les valeurs courantes des options du formulaire
            // Permet de présélectionner le bon secteur et commercial dans les listes déroulantes
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
            
            // fetch() retourne false si aucun résultat, convertir en tableau vide
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ?: [];
            
        } catch (PDOException $e) {
            // Retourner un tableau vide en cas d'erreur pour éviter les erreurs fatales
            return [];
        }
    }

    /**
     * Récupère tous les secteurs d'activité disponibles.
     * 
     * Utilisé pour peupler les listes déroulantes de sélection de secteur
     * dans les formulaires d'ajout ou de modification d'entreprise.
     *
     * @return array Liste des secteurs avec ID et nom, triés par nom.
     */
    public function getSectors(): array {
        try {
            if (!$this->connection) {
                return [];
            }

            // Ordre alphabétique pour faciliter la sélection par l'utilisateur
            $query = "SELECT sector_id, sector_name FROM sectors ORDER BY sector_name";
            $stmt = $this->connection->prepare($query);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Récupère tous les commerciaux disponibles pour assignation aux entreprises.
     * 
     * Filtre uniquement les utilisateurs ayant le rôle "commercial" (function_id = 1)
     * pour n'afficher que les personnes habilitées à gérer les entreprises.
     * Utilisé pour peupler les listes déroulantes dans les formulaires.
     *
     * @return array Liste des commerciaux avec ID, prénom et nom, triés par nom et prénom.
     */
    public function getSalesmen(): array {
        try {
            if (!$this->connection) {
                return [];
            }

            // Filtre sur fk_function_id = 1 (commercial) pour récupérer uniquement les commerciaux
            // Alias pour cohérence avec les noms de champs du formulaire
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
     * Valide les données d'entreprise (SIRET, SIREN au bon format), vérifie la présence
     * des champs requis, et met à jour toutes les informations de l'entreprise. Le commercial
     * est optionnel et peut être null pour ne pas assigner de commercial.
     *
     * @param array $companyData Données de l'entreprise à mettre à jour (company_id, company_name, siret, siren, sector, salesman).
     * @return bool True si la mise à jour a réussi, false en cas d'erreur ou de données invalides.
     */
    public function updateCompany(array $companyData): bool {
        try {
            // Vérification de la connexion avant toute opération
            if (!$this->connection) {
                return false;
            }

            // Validation de la présence des champs obligatoires
            // S'assure que toutes les données nécessaires sont présentes avant la mise à jour
            if (empty($companyData['company_id']) || empty($companyData['company_name']) || 
                empty($companyData['siret']) || empty($companyData['siren']) || 
                empty($companyData['sector'])) {
                return false;
            }

            // Validation du format SIRET : exactement 14 chiffres (format français officiel)
            if (!preg_match('/^\d{14}$/', $companyData['siret'])) {
                return false;
            }

            // Validation du format SIREN : exactement 9 chiffres (format français officiel)
            if (!preg_match('/^\d{9}$/', $companyData['siren'])) {
                return false;
            }

            // Préparation de la requête UPDATE avec paramètres nommés pour éviter les injections SQL
            $query = "UPDATE companies 
                      SET company_name = :company_name, 
                          siret = :siret, 
                          siren = :siren, 
                          fk_sector_id = :sector_id, 
                          fk_salesman_id = :salesman_id
                      WHERE company_id = :company_id";
            
            $stmt = $this->connection->prepare($query);
            
            // Gestion du commercial : valeur nullable (peut être null si non assigné)
            // UnbindParams ne supporte pas null directement, donc on gère ici
            $salesmanId = !empty($companyData['salesman']) ? $companyData['salesman'] : null;
            
            // Bind des paramètres avec types appropriés pour éviter les injections et erreurs de type
            $stmt->bindParam(':company_id', $companyData['company_id'], PDO::PARAM_INT);
            $stmt->bindParam(':company_name', $companyData['company_name'], PDO::PARAM_STR);
            $stmt->bindParam(':siret', $companyData['siret'], PDO::PARAM_STR);
            $stmt->bindParam(':siren', $companyData['siren'], PDO::PARAM_STR);
            $stmt->bindParam(':sector_id', $companyData['sector'], PDO::PARAM_INT);
            $stmt->bindParam(':salesman_id', $salesmanId, PDO::PARAM_INT);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            // En cas d'erreur (contrainte DB, connexion perdue, etc.), retourner false
            return false;
        }
    }
}
