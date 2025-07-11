<?php
// Test de connexion à la base de données
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test de connexion à la base de données</h1>";

try {
    // Inclure la configuration
    require_once(__DIR__ . '/_config/env.php');
    echo "<p>✅ Configuration env.php chargée</p>";
    
    // Afficher les variables d'environnement
    echo "<h2>Variables d'environnement détectées :</h2>";
    echo "<ul>";
    echo "<li>DB_HOST: " . ($_ENV['DB_HOST'] ?? 'NON DÉFINI') . "</li>";
    echo "<li>DB_NAME: " . ($_ENV['DB_NAME'] ?? 'NON DÉFINI') . "</li>";
    echo "<li>DB_USERNAME: " . ($_ENV['DB_USERNAME'] ?? 'NON DÉFINI') . "</li>";
    echo "<li>DB_PASSWORD: " . (isset($_ENV['DB_PASSWORD']) ? '***MASQUÉ***' : 'NON DÉFINI') . "</li>";
    echo "</ul>";
    
    // Tester la connexion directe
    echo "<h2>Test de connexion directe :</h2>";
    $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
    $dbname = $_ENV['DB_NAME'] ?? 'atelier_de_listaro';
    $user = $_ENV['DB_USERNAME'] ?? 'root';
    $pass = $_ENV['DB_PASSWORD'] ?? 'H@klo6539ftgJu';
    
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $user,
        $pass,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8MB4'
        )
    );
    
    echo "<p>✅ Connexion PDO directe réussie</p>";
    
    // Tester via la classe ConnexionDB
    echo "<h2>Test via la classe ConnexionDB :</h2>";
    require_once(__DIR__ . '/_db/connexion_DB.php');
    echo "<p>✅ Classe ConnexionDB chargée</p>";
    
    // Tester une requête simple
    echo "<h2>Test de requête :</h2>";
    $stmt = $DB->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "<p>✅ Requête test réussie : " . $result['test'] . "</p>";
    
    // Lister les tables
    echo "<h2>Tables disponibles :</h2>";
    $stmt = $DB->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table) . "</li>";
    }
    echo "</ul>";
    
    // Tester la table users
    echo "<h2>Test de la table users :</h2>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM users");
    $count = $stmt->fetch();
    echo "<p>Nombre d'utilisateurs : " . $count['count'] . "</p>";
    
    // Tester la table produits
    echo "<h2>Test de la table produits :</h2>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM produits");
    $count = $stmt->fetch();
    echo "<p>Nombre de produits : " . $count['count'] . "</p>";
    
    echo "<h1 style='color: green;'>🎉 Tous les tests sont passés avec succès !</h1>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur détectée :</h1>";
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid red;'>";
    echo "<strong>Type d'erreur :</strong> " . get_class($e) . "<br>";
    echo "<strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier :</strong> " . $e->getFile() . "<br>";
    echo "<strong>Ligne :</strong> " . $e->getLine();
    echo "</p>";
    
    echo "<h2>Stack trace :</h2>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    echo htmlspecialchars($e->getTraceAsString());
    echo "</pre>";
}
?>
