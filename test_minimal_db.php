<?php
// Test minimal de connexion DB pour production
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Test Minimal DB - Production</h1>";

// Afficher l'environnement
echo "<h2>Environnement serveur :</h2>";
echo "<ul>";
echo "<li>PHP Version: " . PHP_VERSION . "</li>";
echo "<li>Server: " . ($_SERVER['SERVER_NAME'] ?? 'Unknown') . "</li>";
echo "<li>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</li>";
echo "<li>Script Path: " . __FILE__ . "</li>";
echo "</ul>";

try {
    // Test 1: Chargement des fichiers de config
    echo "<h2>Test 1: Chargement configuration</h2>";
    $config_path = __DIR__ . '/_config/env.php';
    echo "<p>Chemin config: " . $config_path . "</p>";
    
    if (file_exists($config_path)) {
        echo "<p>✅ Fichier env.php trouvé</p>";
        require_once($config_path);
        echo "<p>✅ Configuration chargée</p>";
    } else {
        throw new Exception("Fichier env.php non trouvé");
    }
    
    // Test 2: Variables d'environnement
    echo "<h2>Test 2: Variables d'environnement</h2>";
    $host = $_ENV['DB_HOST'] ?? 'NON DEFINI';
    $dbname = $_ENV['DB_NAME'] ?? 'NON DEFINI';
    $user = $_ENV['DB_USERNAME'] ?? 'NON DEFINI';
    $pass_set = isset($_ENV['DB_PASSWORD']) ? 'DEFINI' : 'NON DEFINI';
    
    echo "<ul>";
    echo "<li>DB_HOST: " . $host . "</li>";
    echo "<li>DB_NAME: " . $dbname . "</li>";
    echo "<li>DB_USERNAME: " . $user . "</li>";
    echo "<li>DB_PASSWORD: " . $pass_set . "</li>";
    echo "</ul>";
    
    // Test 3: Connexion directe
    echo "<h2>Test 3: Connexion PDO directe</h2>";
    $pass = $_ENV['DB_PASSWORD'] ?? '';
    
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 10
        ]
    );
    echo "<p>✅ Connexion PDO réussie</p>";
    
    // Test 4: Requête simple
    echo "<h2>Test 4: Requête de test</h2>";
    $stmt = $pdo->query("SELECT 1 as test, NOW() as timestamp");
    $result = $stmt->fetch();
    echo "<p>✅ Requête test: " . $result['test'] . " à " . $result['timestamp'] . "</p>";
    
    // Test 5: Vérification des tables critiques
    echo "<h2>Test 5: Vérification tables</h2>";
    $tables_to_check = ['user', 'products', 'prestations', 'orders'];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetch();
            echo "<p>✅ Table '{$table}': " . $count['count'] . " enregistrements</p>";
        } catch (Exception $e) {
            echo "<p>❌ Table '{$table}': " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<h1 style='color: green;'>🎉 Base de données opérationnelle !</h1>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ PROBLÈME DÉTECTÉ</h1>";
    echo "<div style='background: #ffe6e6; border: 2px solid red; padding: 15px; margin: 10px 0;'>";
    echo "<h3>Erreur:</h3>";
    echo "<p><strong>Type:</strong> " . get_class($e) . "</p>";
    echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Code:</strong> " . $e->getCode() . "</p>";
    echo "</div>";
    
    // Solutions suggérées
    echo "<h2>🔧 Solutions possibles:</h2>";
    echo "<ol>";
    echo "<li>Vérifier que le service MySQL/MariaDB est démarré</li>";
    echo "<li>Vérifier les paramètres de connexion dans le .env</li>";
    echo "<li>Vérifier les droits d'accès à la base</li>";
    echo "<li>Vérifier que la base 'atelier_de_listaro' existe</li>";
    echo "</ol>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    line-height: 1.6;
    margin: 20px;
    background: #f5f5f5;
}
h1, h2 { color: #333; }
.error { background: #ffe6e6; border: 1px solid red; padding: 10px; }
.success { background: #e6ffe6; border: 1px solid green; padding: 10px; }
</style>
