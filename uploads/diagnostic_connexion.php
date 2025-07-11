<?php
/**
 * DIAGNOSTIC AVANCÉ DE CONNEXION
 * 
 * Ce script teste plusieurs configurations pour identifier le problème
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configurations à tester
$configs = [
    'config1' => [
        'host' => 'sql100.infinityfree.com',
        'dbname' => 'if0_39368207_atelier',
        'username' => 'if0_39368207',
        'password' => 'HqYnwuxOm3Po',
        'port' => null
    ],
    'config2' => [
        'host' => 'sql100.infinityfree.com',
        'dbname' => 'if0_39368207_atelier',
        'username' => 'if0_39368207',
        'password' => 'HqYnwuxOm3Po',
        'port' => 3306
    ],
    'config3' => [
        'host' => 'localhost',
        'dbname' => 'if0_39368207_atelier',
        'username' => 'if0_39368207',
        'password' => 'HqYnwuxOm3Po',
        'port' => 3306
    ],
    'config4' => [
        'host' => '127.0.0.1',
        'dbname' => 'if0_39368207_atelier',
        'username' => 'if0_39368207',
        'password' => 'HqYnwuxOm3Po',
        'port' => 3306
    ]
];

function testConnection($config, $name) {
    echo "<div style='border: 1px solid #ddd; margin: 10px 0; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🔍 Test: $name</h3>";
    
    // Construire la chaîne de connexion
    $dsn = "mysql:host={$config['host']}";
    if ($config['port']) {
        $dsn .= ";port={$config['port']}";
    }
    $dsn .= ";dbname={$config['dbname']};charset=utf8mb4";
    
    echo "<strong>DSN:</strong> $dsn<br>";
    echo "<strong>Username:</strong> {$config['username']}<br>";
    echo "<strong>Password:</strong> " . str_repeat('*', strlen($config['password'])) . "<br><br>";
    
    try {
        $start_time = microtime(true);
        
        $pdo = new PDO(
            $dsn,
            $config['username'],
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 10,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        $end_time = microtime(true);
        $duration = round(($end_time - $start_time) * 1000, 2);
        
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 3px;'>";
        echo "✅ <strong>CONNEXION RÉUSSIE!</strong><br>";
        echo "Temps de connexion: {$duration}ms<br>";
        
        // Test d'une requête simple
        try {
            $stmt = $pdo->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
            $result = $stmt->fetch();
            echo "Base de données actuelle: {$result['current_db']}<br>";
            echo "Version MySQL: {$result['mysql_version']}<br>";
            
            // Compter les tables
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll();
            echo "Nombre de tables: " . count($tables);
            
        } catch (Exception $e) {
            echo "⚠️ Connexion OK mais erreur de requête: " . $e->getMessage();
        }
        echo "</div>";
        
        return true;
        
    } catch (PDOException $e) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; border-radius: 3px;'>";
        echo "❌ <strong>ÉCHEC DE CONNEXION</strong><br>";
        echo "Code d'erreur: " . $e->getCode() . "<br>";
        echo "Message: " . $e->getMessage() . "<br>";
        
        // Analyser le type d'erreur
        $error_code = $e->getCode();
        switch ($error_code) {
            case 2002:
                echo "<br>💡 <strong>Diagnostic:</strong> Serveur non accessible<br>";
                echo "- Vérifiez l'adresse du serveur<br>";
                echo "- Vérifiez votre connexion internet<br>";
                echo "- Le serveur MySQL est peut-être down<br>";
                break;
            case 1045:
                echo "<br>💡 <strong>Diagnostic:</strong> Authentification échouée<br>";
                echo "- Vérifiez votre nom d'utilisateur<br>";
                echo "- Vérifiez votre mot de passe<br>";
                break;
            case 1044:
                echo "<br>💡 <strong>Diagnostic:</strong> Accès refusé à la base<br>";
                echo "- La base de données n'existe peut-être pas<br>";
                echo "- Vérifiez le nom de la base<br>";
                break;
            case 1049:
                echo "<br>💡 <strong>Diagnostic:</strong> Base de données inconnue<br>";
                echo "- Créez la base de données dans votre panel<br>";
                break;
        }
        echo "</div>";
        
        return false;
    }
    
    echo "</div>";
}

function checkPHPConfig() {
    echo "<div style='background: #e2e3e5; padding: 15px; border-radius: 5px; margin-bottom: 20px;'>";
    echo "<h3>🔧 Configuration PHP</h3>";
    
    echo "<strong>Version PHP:</strong> " . PHP_VERSION . "<br>";
    echo "<strong>Extensions PDO:</strong> " . (extension_loaded('pdo') ? '✅ Activée' : '❌ Manquante') . "<br>";
    echo "<strong>Driver MySQL:</strong> " . (extension_loaded('pdo_mysql') ? '✅ Activé' : '❌ Manquant') . "<br>";
    echo "<strong>Allow URL fopen:</strong> " . (ini_get('allow_url_fopen') ? '✅ Activé' : '❌ Désactivé') . "<br>";
    echo "<strong>Max execution time:</strong> " . ini_get('max_execution_time') . "s<br>";
    
    if (extension_loaded('pdo_mysql')) {
        echo "<strong>Drivers PDO disponibles:</strong> " . implode(', ', PDO::getAvailableDrivers()) . "<br>";
    }
    
    echo "</div>";
}

function pingHost($host, $port = 3306) {
    echo "<div style='background: #fff3cd; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
    echo "<h4>🏓 Test de ping vers $host:$port</h4>";
    
    $start_time = microtime(true);
    $connection = @fsockopen($host, $port, $errno, $errstr, 10);
    $end_time = microtime(true);
    
    if ($connection) {
        fclose($connection);
        $duration = round(($end_time - $start_time) * 1000, 2);
        echo "✅ Serveur accessible en {$duration}ms";
    } else {
        echo "❌ Serveur inaccessible<br>";
        echo "Erreur: $errstr (Code: $errno)";
    }
    echo "</div>";
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Connexion BDD - Atelier de Listaro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
        .warning { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Diagnostic Avancé de Connexion</h1>
        <p>Ce script teste plusieurs configurations pour identifier le problème de connexion à votre base de données InfinityFree.</p>
    </div>

    <div class="warning">
        <strong>⚠️ Important:</strong> Supprimez ce fichier après diagnostic pour des raisons de sécurité (il contient votre mot de passe).
    </div>

    <?php
    // Vérification de la configuration PHP
    checkPHPConfig();
    
    // Test de ping vers le serveur
    pingHost('sql100.infinityfree.com', 3306);
    
    // Test des différentes configurations
    foreach ($configs as $name => $config) {
        testConnection($config, $name);
    }
    ?>

    <div style="background: #e3f2fd; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <h3>💡 Étapes suivantes selon les résultats:</h3>
        
        <h4>Si aucune configuration ne fonctionne:</h4>
        <ul>
            <li>Vérifiez que votre base de données est créée dans le panel InfinityFree</li>
            <li>Attendez 10-15 minutes après création de la base</li>
            <li>Vérifiez que votre compte InfinityFree est actif</li>
            <li>Contactez le support InfinityFree</li>
        </ul>
        
        <h4>Si une configuration fonctionne:</h4>
        <ul>
            <li>Utilisez cette configuration dans vos scripts</li>
            <li>Importez le fichier SQL dans phpMyAdmin</li>
            <li>Configurez votre site avec ces paramètres</li>
        </ul>
        
        <h4>Alternatives si InfinityFree ne fonctionne pas:</h4>
        <ul>
            <li>Essayez un autre hébergeur gratuit (000webhost, Hostinger)</li>
            <li>Utilisez un serveur local (XAMPP, WAMP, MAMP)</li>
            <li>Contactez le support de votre hébergeur</li>
        </ul>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <strong>🔧 Actions de dépannage:</strong><br>
        1. Si "No such file or directory" persiste → Problème de réseau ou serveur down<br>
        2. Si "Access denied" → Mauvais identifiants<br>
        3. Si "Database unknown" → Base non créée<br>
        4. Si tout échoue → Essayez phpMyAdmin depuis votre panel d'hébergement
    </div>
</body>
</html>
