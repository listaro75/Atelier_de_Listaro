<?php
// Test spécifique admin panel
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Test spécifique Admin Panel</h1>";

try {
    // Test 1: Chargement des dépendances
    echo "<h2>Test 1: Chargement des dépendances</h2>";
    
    $files_to_check = [
        '_db/connexion_DB.php',
        '_functions/auth.php',
        'admin_sections/products.php',
        'admin_sections/prestations.php',
        'admin_sections/orders.php',
        'admin_sections/users.php'
    ];
    
    foreach ($files_to_check as $file) {
        $path = __DIR__ . '/' . $file;
        if (file_exists($path)) {
            echo "<p>✅ " . $file . " - trouvé</p>";
        } else {
            echo "<p>❌ " . $file . " - MANQUANT</p>";
        }
    }
    
    // Test 2: Connexion DB
    echo "<h2>Test 2: Connexion base de données</h2>";
    require_once(__DIR__ . '/_db/connexion_DB.php');
    echo "<p>✅ Connexion DB établie</p>";
    
    // Test 3: Fonctions d'authentification
    echo "<h2>Test 3: Fonctions d'authentification</h2>";
    require_once(__DIR__ . '/_functions/auth.php');
    echo "<p>✅ Fonctions auth chargées</p>";
    
    // Test de session
    echo "<h3>État de la session :</h3>";
    echo "<ul>";
    echo "<li>Session ID: " . session_id() . "</li>";
    echo "<li>User ID: " . (isset($_SESSION['id']) ? $_SESSION['id'] : 'NON DÉFINI') . "</li>";
    echo "<li>Logged: " . (isset($_SESSION['logged']) ? ($_SESSION['logged'] ? 'OUI' : 'NON') : 'NON DÉFINI') . "</li>";
    echo "<li>Role: " . (isset($_SESSION['role']) ? $_SESSION['role'] : 'NON DÉFINI') . "</li>";
    echo "<li>Is Admin: " . (function_exists('is_admin') ? (is_admin() ? 'OUI' : 'NON') : 'FONCTION NON DISPONIBLE') . "</li>";
    echo "</ul>";
    
    // Test 4: Simulation de requêtes admin
    echo "<h2>Test 4: Simulation requêtes admin</h2>";
    
    // Test requête produits (comme dans admin_sections/products.php)
    echo "<h3>Test requête produits :</h3>";
    $stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 3");
    $products = $stmt->fetchAll();
    echo "<p>✅ " . count($products) . " produits récupérés</p>";
    
    // Test requête prestations (comme dans admin_sections/prestations.php)
    echo "<h3>Test requête prestations :</h3>";
    $stmt = $DB->query("SELECT * FROM prestations ORDER BY created_at DESC LIMIT 3");
    $prestations = $stmt->fetchAll();
    echo "<p>✅ " . count($prestations) . " prestations récupérées</p>";
    
    // Test requête utilisateurs
    echo "<h3>Test requête utilisateurs :</h3>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user");
    $user_count = $stmt->fetch();
    echo "<p>✅ " . $user_count['count'] . " utilisateurs dans la base</p>";
    
    // Test 5: Simulation d'accès aux sections admin
    echo "<h2>Test 5: Accès aux sections admin</h2>";
    
    $admin_sections = ['products', 'prestations', 'orders', 'users'];
    
    foreach ($admin_sections as $section) {
        echo "<h4>Section: {$section}</h4>";
        $section_file = __DIR__ . "/admin_sections/{$section}.php";
        
        if (file_exists($section_file)) {
            echo "<p>✅ Fichier {$section}.php existe</p>";
            
            // Vérifier le début du fichier pour détecter les erreurs évidentes
            $content = file_get_contents($section_file, false, null, 0, 500);
            if (strpos($content, '<?php') === 0) {
                echo "<p>✅ Fichier {$section}.php commence correctement par PHP</p>";
            } else {
                echo "<p>❌ Fichier {$section}.php ne commence pas par PHP</p>";
            }
            
            if (strpos($content, 'include_once(__DIR__ . \'/../_db/connexion_DB.php\')') !== false) {
                echo "<p>✅ Fichier {$section}.php inclut la connexion DB</p>";
            } else {
                echo "<p>⚠️ Fichier {$section}.php pourrait ne pas inclure la connexion DB</p>";
            }
        } else {
            echo "<p>❌ Fichier {$section}.php MANQUANT</p>";
        }
    }
    
    echo "<h1 style='color: green;'>🎉 Tests admin panel terminés</h1>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur dans les tests admin :</h1>";
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid red;'>";
    echo "<strong>Erreur :</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier :</strong> " . $e->getFile() . "<br>";
    echo "<strong>Ligne :</strong> " . $e->getLine();
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
