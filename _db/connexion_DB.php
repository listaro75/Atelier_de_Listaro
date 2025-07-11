<?php
require_once(__DIR__ . '/../_config/env.php');

// Classe de connexion à la base de données
class ConnexionDB {
    private $host;
    private $dbname;
    private $user;
    private $pass;
    private $connexion;

    function __construct(){
        // Utiliser $_ENV en priorité, puis getenv() en fallback, puis valeurs par défaut InfinityFree
        $this->host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'sql100.infinityfree.com';
        $this->dbname = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'if0_39368207_atelier_de_listaro';
        $this->user = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?? 'if0_39368207';
        $this->pass = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?? 'HqYnwuxOm3Po';

        try {
            $this->connexion = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->user,
                $this->pass,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8MB4'
                )
            );
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public function DB(){
        return $this->connexion;
    }
}

// Initialisation
$DBB = new ConnexionDB();
$DB = $DBB->DB();
?>
