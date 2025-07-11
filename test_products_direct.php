<?php
// Test direct de la section produits pour diagnostiquer le problème
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Forcer une session admin pour le test
$_SESSION['logged'] = true;
$_SESSION['id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['is_admin'] = true;

echo "<h1>🔧 Test direct section produits</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    require_once(__DIR__ . '/_functions/auth.php');
    
    echo "<h2>Session admin forcée pour test</h2>";
    echo "<ul>";
    echo "<li>Logged: " . ($_SESSION['logged'] ? 'true' : 'false') . "</li>";
    echo "<li>Role: " . $_SESSION['role'] . "</li>";
    echo "<li>Is Admin: " . (is_admin() ? 'true' : 'false') . "</li>";
    echo "</ul>";
    
    echo "<h2>Inclusion de la section produits :</h2>";
    
    // Vérifier que le fichier existe
    $products_file = __DIR__ . '/admin_sections/products.php';
    if (file_exists($products_file)) {
        echo "<p style='color: green;'>✅ Fichier products.php trouvé</p>";
        
        // Buffer la sortie pour capturer le contenu
        ob_start();
        include($products_file);
        $output = ob_get_contents();
        ob_end_clean();
        
        echo "<h3>Contenu généré :</h3>";
        echo "<p>Taille: " . strlen($output) . " caractères</p>";
        
        if (strlen($output) > 0) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; background: #f8f9fa; max-height: 400px; overflow-y: auto;'>";
            echo $output;
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Aucun contenu généré</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Fichier products.php non trouvé à: " . $products_file . "</p>";
    }
    
    // Test de la requête produits directement
    echo "<h2>Test requête directe :</h2>";
    $stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
    $products = $stmt->fetchAll();
    
    echo "<p>Nombre de produits trouvés: " . count($products) . "</p>";
    
    if (count($products) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nom</th><th>Prix</th><th>Stock</th><th>Catégorie</th></tr>";
        foreach ($products as $product) {
            echo "<tr>";
            echo "<td>" . $product['id'] . "</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>" . $product['price'] . "€</td>";
            echo "<td>" . $product['stock'] . "</td>";
            echo "<td>" . htmlspecialchars($product['category']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>❌ Erreur :</h2>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>🔗 Liens utiles :</h2>";
echo "<ul>";
echo "<li><a href='debug_admin_loading.php'>Retour au diagnostic</a></li>";
echo "<li><a href='admin_sections/products.php' target='_blank'>Section produits directe</a></li>";
echo "<li><a href='admin_panel.php'>Admin Panel</a></li>";
echo "</ul>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
