<?php
/**
 * TEST DE CONNEXION À LA BASE DE DONNÉES
 * 
 * Ce fichier vous aide à tester votre connexion à la base de données
 * avant de lancer l'installation complète.
 */

// =============================================================================
// CONFIGUREZ VOS PARAMÈTRES ICI
// =============================================================================

// Pour InfinityFree avec vos paramètres réels :
$configs_test = [
    'infinityfree' => [
        'host' => 'sql100.infinityfree.com',
        'dbname' => 'if0_39368207_atelier',  // Changez 'atelier' par le nom que vous voulez
        'username' => 'if0_39368207',
        'password' => 'HqYnwuxOm3Po',
        'charset' => 'utf8mb4'
    ],
    
    'local' => [
        'host' => 'localhost',
        'dbname' => 'atelier_listaro',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    
    'autre_hebergeur' => [
        'host' => 'VOTRE_HOST',
        'dbname' => 'VOTRE_DB',
        'username' => 'VOTRE_USER',
        'password' => 'VOTRE_PASS',
        'charset' => 'utf8mb4'
    ]
];

// Choisissez quelle configuration tester
$config_actuelle = 'infinityfree'; // Changez selon votre cas

// =============================================================================
// TEST DE CONNEXION
// =============================================================================

function testConnexion($config, $nom) {
    echo "<h3>🔍 Test de connexion : $nom</h3>";
    echo "<strong>Paramètres :</strong><br>";
    echo "Host: {$config['host']}<br>";
    echo "Database: {$config['dbname']}<br>";
    echo "Username: {$config['username']}<br>";
    echo "Password: " . (empty($config['password']) ? '(vide)' : str_repeat('*', strlen($config['password']))) . "<br><br>";
    
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", 
            $config['username'], 
            $config['password'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]
        );
        
        echo "<div style='color: green; background: #e8f5e8; padding: 10px; border-radius: 5px;'>";
        echo "✅ <strong>CONNEXION RÉUSSIE !</strong><br>";
        echo "Vous pouvez utiliser ces paramètres pour l'installation.";
        echo "</div>";
        
        // Tester une requête simple
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll();
        echo "<br><strong>Tables existantes :</strong> " . count($tables) . " table(s)<br>";
        
        return true;
        
    } catch (PDOException $e) {
        echo "<div style='color: red; background: #ffe8e8; padding: 10px; border-radius: 5px;'>";
        echo "❌ <strong>ÉCHEC DE CONNEXION</strong><br>";
        echo "Erreur : " . $e->getMessage();
        echo "</div>";
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Connexion BDD - Atelier de Listaro</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .config-box { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .instructions { background: #e3f2fd; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <h1>🔧 Test de Connexion à la Base de Données</h1>
    
    <div class="instructions">
        <h3>📋 Instructions :</h3>
        <ol>
            <li>Modifiez les paramètres dans ce fichier selon votre hébergement</li>
            <li>Changez la variable <code>$config_actuelle</code> pour choisir quelle config tester</li>
            <li>Rechargez cette page pour voir le résultat</li>
            <li>Une fois que la connexion fonctionne, utilisez ces paramètres dans install.php</li>
        </ol>
    </div>

    <?php
    if (isset($configs_test[$config_actuelle])) {
        testConnexion($configs_test[$config_actuelle], $config_actuelle);
    } else {
        echo "<div style='color: red;'>❌ Configuration '$config_actuelle' non trouvée!</div>";
    }
    ?>

    <div class="config-box">
        <h3>💡 Comment trouver vos paramètres de connexion :</h3>
        
        <h4>Pour InfinityFree :</h4>
        <ul>
            <li>Connectez-vous à votre panel de contrôle</li>
            <li>Allez dans "MySQL Databases"</li>
            <li>Créez une base de données si ce n'est pas fait</li>
            <li>Notez le hostname (ex: sql108.infinityfree.com)</li>
            <li>Notez le nom de la base et l'utilisateur</li>
        </ul>
        
        <h4>Pour d'autres hébergeurs :</h4>
        <ul>
            <li>Cherchez "Base de données" ou "MySQL" dans votre panel</li>
            <li>Les paramètres sont généralement dans "Informations de connexion"</li>
        </ul>
    </div>

    <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-top: 20px;">
        <strong>⚠️ Important :</strong> Supprimez ce fichier une fois que vous avez trouvé les bons paramètres !
    </div>
</body>
</html>
