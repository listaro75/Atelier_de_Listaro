<?php
// Configuration centralisée du site Atelier de Listaro
// Ce fichier centralise tous les paramètres du site

class SiteConfig {
    private static $instance = null;
    private $db;
    private $settings = [];
    
    private function __construct() {
        // Charger les paramètres depuis la base de données ou utiliser les valeurs par défaut
        $this->loadSettings();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function loadSettings() {
        try {
            // Inclure la connexion à la base de données
            include_once(__DIR__ . '/_db/connexion_DB.php');
            global $DB;
            
            if ($DB) {
                // Vérifier si la table site_settings existe
                $stmt = $DB->query("SHOW TABLES LIKE 'site_settings'");
                if ($stmt->rowCount() > 0) {
                    // Charger les paramètres depuis la base
                    $stmt = $DB->query("SELECT setting_key, setting_value FROM site_settings");
                    $dbSettings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    if (!empty($dbSettings)) {
                        $this->settings = $dbSettings;
                        return;
                    }
                }
            }
        } catch (Exception $e) {
            // En cas d'erreur, utiliser les valeurs par défaut
        }
        
        // Valeurs par défaut si la base n'est pas disponible
        $this->settings = [
            'site_name' => 'Atelier de Listaro',
            'site_description' => 'Création d\'objets décoratifs uniques',
            'contact_email' => 'contact@atelier-listaro.com',
            'site_status' => 'active',
            'currency' => 'EUR',
            'currency_symbol' => '€',
            'timezone' => 'Europe/Paris',
            'admin_email' => 'admin@atelier-listaro.com'
        ];
    }
    
    public function get($key, $default = null) {
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }
    
    public function set($key, $value) {
        $this->settings[$key] = $value;
        
        // Sauvegarder en base de données si possible
        try {
            include_once(__DIR__ . '/_db/connexion_DB.php');
            global $DB;
            
            if ($DB) {
                $stmt = $DB->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([$key, $value]);
            }
        } catch (Exception $e) {
            // Erreur silencieuse si la base n'est pas disponible
        }
    }
    
    public function getAll() {
        return $this->settings;
    }
    
    // Raccourcis pour les paramètres les plus utilisés
    public function getSiteName() {
        return $this->get('site_name', 'Atelier de Listaro');
    }
    
    public function getSiteDescription() {
        return $this->get('site_description', 'Création d\'objets décoratifs uniques');
    }
    
    public function getContactEmail() {
        return $this->get('contact_email', 'contact@atelier-listaro.com');
    }
    
    public function getCurrency() {
        return $this->get('currency', 'EUR');
    }
    
    public function getCurrencySymbol() {
        return $this->get('currency_symbol', '€');
    }
    
    public function isActive() {
        return $this->get('site_status', 'active') === 'active';
    }
    
    // Fonction pour créer la table site_settings si elle n'existe pas
    public function createSettingsTable() {
        try {
            include_once(__DIR__ . '/_db/connexion_DB.php');
            global $DB;
            
            if ($DB) {
                $sql = "CREATE TABLE IF NOT EXISTS site_settings (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    setting_key VARCHAR(255) UNIQUE NOT NULL,
                    setting_value TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                )";
                
                $DB->exec($sql);
                
                // Insérer les valeurs par défaut
                $stmt = $DB->prepare("INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                
                foreach ($this->settings as $key => $value) {
                    $stmt->execute([$key, $value]);
                }
                
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
        
        return false;
    }
}

// Fonction globale pour faciliter l'accès aux paramètres
function get_site_config($key = null, $default = null) {
    $config = SiteConfig::getInstance();
    
    if ($key === null) {
        return $config->getAll();
    }
    
    return $config->get($key, $default);
}

// Fonction pour définir un paramètre
function set_site_config($key, $value) {
    $config = SiteConfig::getInstance();
    return $config->set($key, $value);
}

// Raccourcis pour les paramètres les plus utilisés
function get_site_name() {
    return SiteConfig::getInstance()->getSiteName();
}

function get_site_description() {
    return SiteConfig::getInstance()->getSiteDescription();
}

function get_contact_email() {
    return SiteConfig::getInstance()->getContactEmail();
}

function get_currency_symbol() {
    return SiteConfig::getInstance()->getCurrencySymbol();
}

function is_site_active() {
    return SiteConfig::getInstance()->isActive();
}
?>
