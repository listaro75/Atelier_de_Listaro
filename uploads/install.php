<?php
/**
 * Script d'installation de la base de données pour Atelier de Listaro
 * 
 * Ce script permet d'installer automatiquement la base de données
 * et de configurer les données de base nécessaires au fonctionnement du site.
 */

// Configuration de l'affichage des erreurs
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configuration de la base de données
// Modifiez ces valeurs selon votre configuration d'hébergement
$db_config = [
    'host' => 'sql302.infinityfree.com',  // Votre serveur InfinityFree
    'dbname' => 'if0_39368207_atelier_de_listaro',   // Changez 'atelier' par le nom de votre base
    'username' => 'if0_39368207',         // Votre nom d'utilisateur
    'password' => 'HqYnwuxOm3Po',         // Votre mot de passe
    'charset' => 'utf8mb4'
];

/**
 * Fonction pour se connecter à MySQL sans spécifier de base de données
 */
function connectToMySQL($config) {
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};charset={$config['charset']}", 
            $config['username'], 
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}"
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        die("Erreur de connexion MySQL : " . $e->getMessage());
    }
}

/**
 * Fonction pour se connecter à une base de données spécifique
 */
function connectToDatabase($config) {
    try {
        // Essayer d'abord avec le port par défaut
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", 
            $config['username'], 
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
                PDO::ATTR_TIMEOUT => 30
            ]
        );
        return $pdo;
    } catch (PDOException $e) {
        // Si échec, essayer avec le port 3306
        try {
            $pdo = new PDO(
                "mysql:host={$config['host']};port=3306;dbname={$config['dbname']};charset={$config['charset']}", 
                $config['username'], 
                $config['password'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']}",
                    PDO::ATTR_TIMEOUT => 30
                ]
            );
            return $pdo;
        } catch (PDOException $e2) {
            die("❌ Erreur de connexion à la base de données :\n" . 
                "   Erreur 1: " . $e->getMessage() . "\n" .
                "   Erreur 2: " . $e2->getMessage() . "\n\n" .
                "🔧 Vérifiez vos paramètres de connexion dans le script :\n" .
                "   - Host: {$config['host']}\n" .
                "   - Database: {$config['dbname']}\n" .
                "   - Username: {$config['username']}\n");
        }
    }
}

/**
 * Fonction pour créer la base de données si elle n'existe pas
 */
function createDatabase($config) {
    echo "🔄 Création de la base de données '{$config['dbname']}'...\n";
    
    $pdo = connectToMySQL($config);
    
    // Créer la base de données si elle n'existe pas
    $sql = "CREATE DATABASE IF NOT EXISTS `{$config['dbname']}` 
            CHARACTER SET {$config['charset']} 
            COLLATE {$config['charset']}_unicode_ci";
    
    try {
        $pdo->exec($sql);
        echo "✅ Base de données '{$config['dbname']}' créée avec succès.\n";
    } catch (PDOException $e) {
        die("❌ Erreur lors de la création de la base de données : " . $e->getMessage());
    }
}

/**
 * Fonction pour exécuter le script SQL
 */
function executeSQLScript($config, $sqlFile) {
    if (!file_exists($sqlFile)) {
        die("❌ Le fichier SQL '$sqlFile' n'existe pas.");
    }
    
    echo "🔄 Exécution du script SQL...\n";
    
    $pdo = connectToDatabase($config);
    
    // Lire le contenu du fichier SQL
    $sql = file_get_contents($sqlFile);
    
    // Diviser le script en instructions séparées
    $statements = explode(';', $sql);
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || substr($statement, 0, 2) === '--') {
            continue;
        }
        
        try {
            $pdo->exec($statement);
            $success_count++;
        } catch (PDOException $e) {
            $error_count++;
            echo "⚠️  Erreur sur instruction : " . substr($statement, 0, 50) . "...\n";
            echo "   Détail : " . $e->getMessage() . "\n";
        }
    }
    
    echo "✅ Script SQL exécuté : $success_count instructions réussies, $error_count erreurs.\n";
}

/**
 * Fonction pour créer le fichier de configuration de l'environnement
 */
function createEnvFile($config) {
    $envContent = "<?php
// Configuration de l'environnement pour Atelier de Listaro
// Ce fichier contient les variables d'environnement sensibles

// Configuration de la base de données
putenv('DB_HOST={$config['host']}');
putenv('DB_NAME={$config['dbname']}');
putenv('DB_USER={$config['username']}');
putenv('DB_PASS={$config['password']}');

// Configuration du site
putenv('SITE_NAME=Atelier de Listaro');
putenv('SITE_URL=http://localhost/atelier-listaro');

// Configuration Stripe (à remplir avec vos clés)
putenv('STRIPE_PUBLIC_KEY=pk_test_...');
putenv('STRIPE_SECRET_KEY=sk_test_...');

// Configuration email (à remplir selon votre configuration)
putenv('MAIL_HOST=smtp.gmail.com');
putenv('MAIL_PORT=587');
putenv('MAIL_USERNAME=votre-email@gmail.com');
putenv('MAIL_PASSWORD=votre-mot-de-passe-app');
putenv('MAIL_FROM=votre-email@gmail.com');
putenv('MAIL_FROM_NAME=Atelier de Listaro');

// Mode debug (true en développement, false en production)
putenv('DEBUG_MODE=true');
";

    $envFile = '_config/env.php';
    
    if (!is_dir('_config')) {
        mkdir('_config', 0755, true);
    }
    
    file_put_contents($envFile, $envContent);
    echo "✅ Fichier de configuration '_config/env.php' créé.\n";
}

/**
 * Fonction pour créer les dossiers d'upload
 */
function createUploadDirectories() {
    $directories = [
        'uploads',
        'uploads/products',
        'uploads/prestations'
    ];
    
    foreach ($directories as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
            echo "✅ Dossier '$dir' créé.\n";
        }
    }
    
    // Créer un fichier .htaccess pour sécuriser les uploads
    $htaccessContent = "# Protection des fichiers uploads
Options -Indexes

# Autoriser seulement certains types de fichiers
<FilesMatch \"\\.(jpg|jpeg|png|gif|webp)$\">
    Order Allow,Deny
    Allow from all
</FilesMatch>

# Interdire l'exécution de scripts
<FilesMatch \"\\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$\">
    Order Deny,Allow
    Deny from all
</FilesMatch>";
    
    file_put_contents('uploads/.htaccess', $htaccessContent);
    echo "✅ Fichier de sécurité '.htaccess' créé dans uploads/.\n";
}

/**
 * Fonction pour vérifier la configuration
 */
function verifyInstallation($config) {
    echo "🔍 Vérification de l'installation...\n";
    
    try {
        $pdo = connectToDatabase($config);
        
        // Vérifier les tables principales
        $tables = ['user', 'products', 'prestations', 'orders'];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                echo "✅ Table '$table' trouvée.\n";
            } else {
                echo "❌ Table '$table' manquante.\n";
            }
        }
        
        // Vérifier l'utilisateur admin
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM user WHERE role = 'admin'");
        $result = $stmt->fetch();
        if ($result['count'] > 0) {
            echo "✅ Utilisateur administrateur trouvé.\n";
        } else {
            echo "❌ Aucun utilisateur administrateur trouvé.\n";
        }
        
        echo "🎉 Installation terminée avec succès !\n";
        
    } catch (PDOException $e) {
        echo "❌ Erreur lors de la vérification : " . $e->getMessage() . "\n";
    }
}

/**
 * Fonction pour tester la connexion à la base de données
 */
function testConnection($config) {
    echo "🔍 Test de connexion à la base de données...\n";
    echo "   Host: {$config['host']}\n";
    echo "   Database: {$config['dbname']}\n";
    echo "   Username: {$config['username']}\n\n";
    
    try {
        $pdo = connectToDatabase($config);
        echo "✅ Connexion réussie à la base de données !\n\n";
        return true;
    } catch (Exception $e) {
        echo "❌ Échec de la connexion.\n";
        echo "💡 Vérifiez vos paramètres de connexion dans phpMyAdmin ou votre panel d'hébergement.\n\n";
        return false;
    }
}

/**
 * Fonction principale d'installation (adaptée pour hébergement partagé)
 */
function install($config) {
    echo "🚀 Début de l'installation d'Atelier de Listaro\n";
    echo "==========================================\n\n";
    
    // Pour l'hébergement partagé, on ne crée pas la base de données
    // elle est déjà fournie par l'hébergeur
    echo "ℹ️  Utilisation de la base de données existante '{$config['dbname']}'...\n";
    
    // Étape 1 : Exécuter le script SQL
    executeSQLScript($config, 'atelier_listaro_db.sql');
    
    // Étape 2 : Créer le fichier de configuration
    createEnvFile($config);
    
    // Étape 3 : Créer les dossiers d'upload
    createUploadDirectories();
    
    // Étape 4 : Vérifier l'installation
    verifyInstallation($config);
    
    echo "\n==========================================\n";
    echo "🎉 Installation terminée !\n\n";
    echo "ℹ️  Informations importantes :\n";
    echo "   - Utilisateur admin : admin\n";
    echo "   - Email admin : admin@atelier-listaro.com\n";
    echo "   - Mot de passe admin : Admin123!\n";
    echo "   - Modifiez le mot de passe après la première connexion\n\n";
    echo "📁 Fichiers créés :\n";
    echo "   - _config/env.php (configuration)\n";
    echo "   - uploads/ (dossiers d'images)\n\n";
    echo "⚠️  N'oubliez pas de :\n";
    echo "   - Configurer vos clés Stripe dans _config/env.php\n";
    echo "   - Configurer vos paramètres email dans _config/env.php\n";
    echo "   - Adapter les permissions des dossiers selon votre serveur\n";
}

// =============================================================================
// EXÉCUTION DU SCRIPT
// =============================================================================

// Vérifier si le script est exécuté en ligne de commande ou via navigateur
if (php_sapi_name() === 'cli') {
    // Mode ligne de commande
    echo "Installation via ligne de commande\n";
} else {
    // Mode navigateur - ajouter un minimum de sécurité
    echo "<pre>";
    echo "⚠️  ATTENTION : Ce script d'installation ne devrait être exécuté qu'une seule fois.\n";
    echo "Supprimez ce fichier après l'installation pour des raisons de sécurité.\n\n";
}

// Demander confirmation avant installation (seulement en mode navigateur)
if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'yes') {
        echo "Pour procéder à l'installation, ajoutez '?confirm=yes' à l'URL.\n";
        echo "Exemple : http://localhost/atelier-listaro/install.php?confirm=yes\n";
        exit;
    }
}

// Lancer l'installation
echo "🔧 Configuration détectée :\n";
echo "   Mode: " . (php_sapi_name() === 'cli' ? 'Ligne de commande' : 'Navigateur web') . "\n";
echo "   Host: {$db_config['host']}\n";
echo "   Database: {$db_config['dbname']}\n";
echo "   Username: {$db_config['username']}\n\n";

// Tester la connexion avant de procéder
if (testConnection($db_config)) {
    install($db_config);
} else {
    echo "❌ Installation annulée à cause de l'échec de connexion.\n\n";
    echo "🔧 Instructions pour corriger :\n";
    echo "1. Connectez-vous à votre panel d'hébergement\n";
    echo "2. Allez dans la section 'Bases de données MySQL'\n";
    echo "3. Notez les informations de connexion correctes\n";
    echo "4. Modifiez les paramètres dans ce script\n";
    echo "5. Relancez l'installation\n";
}

if (php_sapi_name() !== 'cli') {
    echo "</pre>";
}
?>
