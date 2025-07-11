<?php
echo "<h1>🔧 Diagnostic Avancé - Fichier .env</h1>";
echo "<p><strong>Date :</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Vérifier l'existence du fichier .env
echo "<h2>1. Fichier .env</h2>";
if (file_exists('.env')) {
    echo "✅ Fichier .env existe<br>";
    
    // Lire le contenu du fichier .env
    $content = file_get_contents('.env');
    echo "<h3>Contenu du fichier .env :</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    echo htmlspecialchars($content);
    echo "</pre>";
    
    // Analyser ligne par ligne
    $lines = file('.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    echo "<h3>Analyse ligne par ligne :</h3>";
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) {
            echo "💬 Commentaire : " . htmlspecialchars($line) . "<br>";
            continue;
        }
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            echo "🔑 <strong>$key</strong> = " . htmlspecialchars($value) . "<br>";
        }
    }
} else {
    echo "❌ Fichier .env introuvable<br>";
}

// 2. Test de chargement manuel
echo "<h2>2. Test de chargement manuel</h2>";
try {
    // Simuler le chargement comme dans env.php
    $envFile = '.env';
    
    if (!file_exists($envFile)) {
        throw new Exception('.env file not found');
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    echo "<h3>Variables chargées :</h3>";
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (!empty($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                echo "✅ <strong>$key</strong> = " . htmlspecialchars($value) . "<br>";
            }
        }
    }
    
    echo "<h3>Test des variables d'environnement :</h3>";
    $testVars = ['DB_HOST', 'DB_NAME', 'DB_USERNAME', 'DB_PASSWORD'];
    foreach ($testVars as $var) {
        $value = getenv($var);
        if ($value !== false) {
            $displayValue = ($var === 'DB_PASSWORD') ? '[Masqué - ' . strlen($value) . ' caractères]' : $value;
            echo "✅ <strong>$var</strong> = $displayValue<br>";
        } else {
            echo "❌ <strong>$var</strong> = Non défini<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "<br>";
}

// 3. Test de connexion avec les variables chargées
echo "<h2>3. Test de connexion avec variables chargées</h2>";
$host = getenv('DB_HOST');
$dbname = getenv('DB_NAME');
$username = getenv('DB_USERNAME');
$password = getenv('DB_PASSWORD');

if ($host && $dbname && $username && $password) {
    echo "<p><strong>Tentative de connexion avec :</strong></p>";
    echo "<p>Host: $host<br>";
    echo "Base: $dbname<br>";
    echo "User: $username<br>";
    echo "Pass: [" . strlen($password) . " caractères]</p>";
    
    try {
        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $username,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_TIMEOUT => 10
            ]
        );
        
        echo "<p class='success'>🎉 <strong>CONNEXION RÉUSSIE !</strong></p>";
        
        // Test de requête
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '$dbname'");
        $result = $stmt->fetch();
        echo "<p><strong>Tables dans la base :</strong> " . $result['count'] . "</p>";
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ <strong>Erreur de connexion :</strong> " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Variables manquantes pour la connexion</p>";
}

echo "<hr>";
echo "<p><small>Diagnostic terminé. Supprimez ce fichier après analyse.</small></p>";
?>
