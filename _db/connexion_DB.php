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
        $this->host = getenv('DB_HOST');
        $this->dbname = getenv('DB_NAME');
        $this->user = getenv('DB_USER');
        $this->pass = getenv('DB_PASS');

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
