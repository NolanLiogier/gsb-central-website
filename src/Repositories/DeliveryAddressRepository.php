<?php

namespace App\Repositories;

use Config\Database;
use PDO;
use PDOException;

/**
 * Classe DeliveryAddressRepository
 * 
 * Repository pour l'accès et la manipulation des données des adresses de livraison.
 * Fournit des méthodes pour rechercher et créer des adresses de livraison.
 */
class DeliveryAddressRepository {

    /**
     * Recherche une adresse de livraison existante par ses caractéristiques.
     * 
     * Compare tous les champs de l'adresse pour éviter les doublons.
     * Utilise une comparaison insensible à la casse et ignore les espaces en début/fin.
     *
     * @param array $addressData Données de l'adresse (street, city, postal_code, country, additional_info).
     * @return int|null ID de l'adresse trouvée, ou null si aucune adresse correspondante n'existe.
     */
    public function findAddressByData(array $addressData): ?int {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return null;
            }

            // Normalisation des données pour la comparaison
            $street = trim($addressData['street'] ?? '');
            $city = trim($addressData['city'] ?? '');
            $postalCode = trim($addressData['postal_code'] ?? '');
            $country = trim($addressData['country'] ?? 'France');
            $additionalInfo = trim($addressData['additional_info'] ?? '');

            // Vérification que les champs obligatoires sont présents
            if (empty($street) || empty($city) || empty($postalCode)) {
                return null;
            }

            // Recherche d'une adresse correspondante (comparaison insensible à la casse)
            $query = "SELECT address_id 
                      FROM delivery_address 
                      WHERE LOWER(TRIM(street)) = LOWER(:street)
                        AND LOWER(TRIM(city)) = LOWER(:city)
                        AND LOWER(TRIM(postal_code)) = LOWER(:postal_code)
                        AND LOWER(TRIM(country)) = LOWER(:country)
                        AND (
                          (additional_info IS NULL AND :additional_info IS NULL)
                          OR LOWER(TRIM(COALESCE(additional_info, ''))) = LOWER(TRIM(COALESCE(:additional_info, '')))
                        )
                      LIMIT 1";
            
            $stmt = $conn->prepare($query);
            
            // Bind des paramètres
            $stmt->bindValue(':street', $street, PDO::PARAM_STR);
            $stmt->bindValue(':city', $city, PDO::PARAM_STR);
            $stmt->bindValue(':postal_code', $postalCode, PDO::PARAM_STR);
            $stmt->bindValue(':country', $country, PDO::PARAM_STR);
            $stmt->bindValue(':additional_info', empty($additionalInfo) ? null : $additionalInfo, PDO::PARAM_STR);
            
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result ? (int)$result['address_id'] : null;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return null;
        }
    }

    /**
     * Crée une nouvelle adresse de livraison dans la base de données.
     * 
     * Valide les données de l'adresse, vérifie la présence des champs requis,
     * et insère une nouvelle adresse.
     *
     * @param array $addressData Données de l'adresse (street, city, postal_code, country, additional_info).
     * @return int|null ID de l'adresse créée, ou null en cas d'erreur ou de données invalides.
     */
    public function createAddress(array $addressData): ?int {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant toute opération
            if (!$conn) {
                return null;
            }

            // Normalisation des données
            $street = trim($addressData['street'] ?? '');
            $city = trim($addressData['city'] ?? '');
            $postalCode = trim($addressData['postal_code'] ?? '');
            $country = trim($addressData['country'] ?? 'France');
            $additionalInfo = trim($addressData['additional_info'] ?? '');

            // Validation de la présence des champs obligatoires
            if (empty($street) || empty($city) || empty($postalCode)) {
                return null;
            }

            // Vérification qu'une adresse identique n'existe pas déjà
            $existingAddressId = $this->findAddressByData($addressData);
            if ($existingAddressId !== null) {
                return $existingAddressId;
            }

            // Préparation de la requête INSERT avec paramètres nommés pour éviter les injections SQL
            $query = "INSERT INTO delivery_address (street, city, postal_code, country, additional_info) 
                      VALUES (:street, :city, :postal_code, :country, :additional_info)";
            
            $stmt = $conn->prepare($query);
            
            // Bind des paramètres avec types appropriés
            $stmt->bindValue(':street', $street, PDO::PARAM_STR);
            $stmt->bindValue(':city', $city, PDO::PARAM_STR);
            $stmt->bindValue(':postal_code', $postalCode, PDO::PARAM_STR);
            $stmt->bindValue(':country', $country, PDO::PARAM_STR);
            $stmt->bindValue(':additional_info', empty($additionalInfo) ? null : $additionalInfo, PDO::PARAM_STR);
            
            if (!$stmt->execute()) {
                $conn = null;
                $database = null;
                return null;
            }

            // Récupération de l'ID de l'adresse créée
            $addressId = (int)$conn->lastInsertId();
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $addressId;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return null;
        }
    }

    /**
     * Recherche ou crée une adresse de livraison.
     * 
     * Vérifie d'abord si une adresse identique existe déjà.
     * Si elle existe, retourne son ID. Sinon, crée une nouvelle adresse et retourne son ID.
     *
     * @param array $addressData Données de l'adresse (street, city, postal_code, country, additional_info).
     * @return int|null ID de l'adresse (existante ou nouvellement créée), ou null en cas d'erreur.
     */
    public function findOrCreateAddress(array $addressData): ?int {
        // Recherche d'une adresse existante
        $existingAddressId = $this->findAddressByData($addressData);
        
        if ($existingAddressId !== null) {
            return $existingAddressId;
        }
        
        // Création d'une nouvelle adresse si elle n'existe pas
        return $this->createAddress($addressData);
    }

    /**
     * Récupère une adresse de livraison par son ID.
     * 
     * @param int $addressId ID de l'adresse.
     * @return array|null Données de l'adresse, ou null si l'adresse n'existe pas.
     */
    public function getAddressById(int $addressId): ?array {
        // Initialisation de la connexion à la base de données
        $database = new Database();
        $conn = $database->getConnection();
        
        try {
            // Vérification de la connexion avant la requête
            if (!$conn) {
                return null;
            }

            $query = "SELECT address_id, street, city, postal_code, country, additional_info
                      FROM delivery_address
                      WHERE address_id = :address_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':address_id', $addressId, PDO::PARAM_INT);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Fermeture de la connexion
            $conn = null;
            $database = null;
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            // Fermeture de la connexion en cas d'erreur
            $conn = null;
            $database = null;
            return null;
        }
    }
}

