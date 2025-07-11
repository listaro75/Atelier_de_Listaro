<?php
// Test simple du shop
include_once('_db/connexion_DB.php');

echo "<!DOCTYPE html>";
echo "<html><head><title>Test Shop Simple</title></head><body>";
echo "<h1>Test Shop Simple</h1>";

try {
    // Récupération simple des produits
    $stmt = $DB->query("SELECT * FROM products LIMIT 5");
    $products = $stmt->fetchAll();
    
    echo "<p>Produits trouvés: " . count($products) . "</p>";
    
    if (empty($products)) {
        echo "<p>Aucun produit en base de données</p>";
        echo "<p><a href='create_test_data.php'>Créer des données de test</a></p>";
    } else {
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
        
        foreach ($products as $product) {
            echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
            echo "<h3>" . htmlspecialchars($product['name']) . "</h3>";
            echo "<p>Prix: " . number_format($product['price'], 2) . " €</p>";
            
            // Chercher l'image
            $stmt2 = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1");
            $stmt2->execute([$product['id']]);
            $image = $stmt2->fetchColumn();
            
            if ($image) {
                if (file_exists($image)) {
                    echo "<img src='$image' style='width: 100%; height: 150px; object-fit: cover; border-radius: 4px;' alt='" . htmlspecialchars($product['name']) . "'>";
                } else {
                    echo "<div style='width: 100%; height: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border-radius: 4px; color: #666;'>Image manquante<br><small>$image</small></div>";
                }
            } else {
                echo "<div style='width: 100%; height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border-radius: 4px; color: #999;'>📷<br>Aucune image</div>";
            }
            
            echo "</div>";
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur: " . $e->getMessage() . "</p>";
}

echo "<p><a href='shop.php'>→ Shop complet</a> | <a href='debug_shop.php'>🔍 Debug détaillé</a></p>";
echo "</body></html>";
?>
