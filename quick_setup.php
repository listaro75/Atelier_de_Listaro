<?php
/**
 * Script rapide pour ajouter des produits avec images de test
 */

include '_db/connexion_DB.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Setup Boutique</title></head><body>";
echo "<h1>🛒 Setup Boutique Rapide</h1>";

try {
    // Supprimer les anciens produits de test s'ils existent
    $DB->exec("DELETE FROM product_images WHERE product_id IN (SELECT id FROM products WHERE name LIKE '%Test%' OR name LIKE '%Création%' OR name LIKE '%Œuvre%' OR name LIKE '%Design%')");
    $DB->exec("DELETE FROM products WHERE name LIKE '%Test%' OR name LIKE '%Création%' OR name LIKE '%Œuvre%' OR name LIKE '%Design%'");
    
    echo "<p>✅ Anciennes données de test supprimées</p>";
    
    // Créer de nouveaux produits avec images
    $products = [
        [
            'name' => 'Création Artistique Moderne',
            'description' => 'Une magnifique création artistique moderne, réalisée à la main avec passion et créativité.',
            'price' => 89.99,
            'category' => 'Art',
            'image' => 'uploads/products/test-product-1.svg'
        ],
        [
            'name' => 'Œuvre Originale Bleue',
            'description' => 'Pièce unique aux tons bleus, parfaite pour décorer votre intérieur avec élégance.',
            'price' => 129.50,
            'category' => 'Art',
            'image' => 'uploads/products/test-product-2.svg'
        ],
        [
            'name' => 'Design Contemporain',
            'description' => 'Design moderne et contemporain qui s\'intègre parfaitement dans tous les espaces.',
            'price' => 67.00,
            'category' => 'Design',
            'image' => 'uploads/products/test-product-3.svg'
        ]
    ];
    
    foreach ($products as $product) {
        // Insérer le produit
        $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([
            $product['name'],
            $product['description'], 
            $product['price'],
            $product['category']
        ]);
        
        $productId = $DB->lastInsertId();
        
        // Ajouter l'image si elle existe
        if (file_exists($product['image'])) {
            $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 1)");
            $stmt->execute([$productId, $product['image']]);
            echo "<p>✅ Produit créé: <strong>{$product['name']}</strong> avec image</p>";
        } else {
            echo "<p>⚠️ Produit créé: <strong>{$product['name']}</strong> SANS image (fichier manquant)</p>";
        }
    }
    
    // Statistiques
    $stmt = $DB->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) FROM product_images");
    $totalImages = $stmt->fetchColumn();
    
    echo "<div style='background: #f0f8ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>📊 Résumé</h2>";
    echo "<p><strong>Total produits:</strong> $totalProducts</p>";
    echo "<p><strong>Total images:</strong> $totalImages</p>";
    echo "</div>";
    
    // Liens de test
    echo "<div style='background: #f9f9f9; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>🔗 Tests</h2>";
    echo "<p><a href='shop.php' style='color: #007bff; text-decoration: none; font-weight: bold;'>🛒 Voir la Boutique</a></p>";
    echo "<p><a href='debug_shop.php' style='color: #28a745; text-decoration: none;'>🔍 Debug Shop</a></p>";
    echo "<p><a href='test_images_complet.php' style='color: #ffc107; text-decoration: none;'>📋 Test Images Complet</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "</body></html>";
?>
