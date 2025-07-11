<?php
// Test spécifique pour diagnostiquer les problèmes admin
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic Admin Panel - Base de données</h1>";

try {
    // Inclure la configuration
    require_once(__DIR__ . '/_db/connexion_DB.php');
    echo "<p>✅ Connexion DB établie</p>";
    
    // Test des requêtes principales utilisées dans l'admin
    echo "<h2>📊 Tests des tables principales :</h2>";
    
    // Test table user
    echo "<h3>Table 'user' :</h3>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user");
    $count = $stmt->fetch();
    echo "<p>✅ Nombre d'utilisateurs : " . $count['count'] . "</p>";
    
    // Test table products
    echo "<h3>Table 'products' :</h3>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch();
    echo "<p>✅ Nombre de produits : " . $count['count'] . "</p>";
    
    // Test table prestations
    echo "<h3>Table 'prestations' :</h3>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM prestations");
    $count = $stmt->fetch();
    echo "<p>✅ Nombre de prestations : " . $count['count'] . "</p>";
    
    // Test table orders
    echo "<h3>Table 'orders' :</h3>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM orders");
    $count = $stmt->fetch();
    echo "<p>✅ Nombre de commandes : " . $count['count'] . "</p>";
    
    // Test des requêtes spécifiques admin
    echo "<h2>🔧 Tests des requêtes admin spécifiques :</h2>";
    
    // Test requête produits avec catégories (utilisée dans admin)
    echo "<h3>Requête produits admin :</h3>";
    $stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 5");
    $products = $stmt->fetchAll();
    echo "<p>✅ Récupération des produits : " . count($products) . " produits trouvés</p>";
    
    if (count($products) > 0) {
        echo "<ul>";
        foreach ($products as $product) {
            echo "<li>ID: " . $product['id'] . " - " . htmlspecialchars($product['name']) . " - " . $product['price'] . "€</li>";
        }
        echo "</ul>";
    }
    
    // Test requête catégories
    echo "<h3>Requête catégories :</h3>";
    $stmt = $DB->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll();
    echo "<p>✅ Catégories trouvées : " . count($categories) . "</p>";
    
    if (count($categories) > 0) {
        echo "<ul>";
        foreach ($categories as $cat) {
            echo "<li>" . htmlspecialchars($cat['category']) . "</li>";
        }
        echo "</ul>";
    }
    
    // Test images produits
    echo "<h3>Test images produits :</h3>";
    $stmt = $DB->query("SELECT COUNT(*) as count FROM product_images");
    $count = $stmt->fetch();
    echo "<p>✅ Nombre d'images produits : " . $count['count'] . "</p>";
    
    // Test prestations avec images
    echo "<h3>Test prestations avec images :</h3>";
    $stmt = $DB->query("SELECT p.id, p.title, COUNT(pi.id) as image_count 
                       FROM prestations p 
                       LEFT JOIN prestation_images pi ON p.id = pi.prestation_id 
                       GROUP BY p.id 
                       LIMIT 5");
    $prestations = $stmt->fetchAll();
    echo "<p>✅ Prestations avec comptage d'images : " . count($prestations) . "</p>";
    
    if (count($prestations) > 0) {
        echo "<ul>";
        foreach ($prestations as $prestation) {
            echo "<li>ID: " . $prestation['id'] . " - " . htmlspecialchars($prestation['title']) . " (" . $prestation['image_count'] . " images)</li>";
        }
        echo "</ul>";
    }
    
    // Test de la session (important pour l'admin)
    echo "<h2>🔐 Test de session :</h2>";
    session_start();
    if (isset($_SESSION['user_id'])) {
        echo "<p>✅ Session utilisateur active : ID " . $_SESSION['user_id'] . "</p>";
        if (isset($_SESSION['is_admin'])) {
            echo "<p>✅ Droits admin : " . ($_SESSION['is_admin'] ? 'OUI' : 'NON') . "</p>";
        }
    } else {
        echo "<p>⚠️ Aucune session utilisateur active</p>";
    }
    
    echo "<h1 style='color: green;'>🎉 Diagnostic terminé avec succès !</h1>";
    echo "<p><strong>Conclusion :</strong> La base de données est accessible et les requêtes principales fonctionnent.</p>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur dans le diagnostic :</h1>";
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border: 1px solid red; border-radius: 5px;'>";
    echo "<strong>Type d'erreur :</strong> " . get_class($e) . "<br>";
    echo "<strong>Message :</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "<strong>Fichier :</strong> " . $e->getFile() . "<br>";
    echo "<strong>Ligne :</strong> " . $e->getLine();
    echo "</div>";
}
?>
