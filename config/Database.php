<?php

namespace Config;

use PDO;
use PDOException;
use Dotenv\Dotenv;

/**
 * Classe Database
 * Gère la connexion à la base de données en utilisant PDO.
 */
class Database
{
    /**
     * @var string L'hôte de la base de données.
     */
    private string $host;

    /**
     * @var string Le nom de la base de données.
     */
    private string $db_name;

    /**
     * @var string Le nom d'utilisateur de la base de données.
     */
    private string $username;

    /**
     * @var string Le mot de passe de la base de données.
     */
    private string $password;

    /**
     * @var PDO|null L'objet de connexion PDO.
     */
    public ?PDO $conn = null;

    /**
     * Constructeur de la classe Database.
     * Charge les variables d'environnement et initialise les paramètres de connexion à la base de données.
     */
    public function __construct()
    {
        $dotenv = Dotenv::createImmutable(__DIR__ . '/..');
        $dotenv->load();

        $this->host = $_ENV['DB_HOST'];
        $this->db_name = $_ENV['DB_DATABASE'];
        $this->username = $_ENV['DB_USER'];
        $this->password = $_ENV['DB_PASSWORD'];
    }

    /**
     * Établit et retourne une connexion PDO à la base de données.
     *
     * @return PDO|null L'objet de connexion PDO, ou null si la connexion échoue.
     */
    public function getConnection(): ?PDO
    {
        $this->conn = null;

        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->exec("set names utf8");
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }

    /**
     * Méthode statique pour obtenir une connexion PDO sans instancier la classe.
     * Utile lorsque le constructeur est privé (pattern singleton).
     *
     * @return PDO|null L'objet de connexion PDO, ou null si la connexion échoue.
     */
    public static function getStaticConnection(): ?PDO
    {
        $instance = new self();
        return $instance->getConnection();
    }
}