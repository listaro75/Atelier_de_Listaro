<?php
/**
 * Script pour créer des liens entre produits et images de test
 */

include '_db/connexion_DB.php';

echo "=== AJOUT D'IMAGES AUX PRODUITS ===\n\n";

try {
    // Récupérer les produits existants
    $stmt = $DB->query("SELECT id, name FROM products");
    $products = $stmt->fetchAll();
    
    echo "Produits trouvés: " . count($products) . "\n";
    
    $testImages = [
        'uploads/products/test-product-1.svg',
        'uploads/products/test-product-2.svg', 
        'uploads/products/test-product-3.svg'
    ];
    
    foreach ($products as $index => $product) {
        // Vérifier si le produit a déjà des images
        $stmt = $DB->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
        $stmt->execute([$product['id']]);
        $hasImages = $stmt->fetchColumn() > 0;
        
        if (!$hasImages) {
            // Attribuer une image de test
            $imageIndex = $index % count($testImages);
            $imagePath = $testImages[$imageIndex];
            
            // Vérifier que le fichier existe
            if (file_exists($imagePath)) {
                $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 1)");
                $stmt->execute([$product['id'], $imagePath]);
                
                echo "✅ Image ajoutée au produit '{$product['name']}' (ID: {$product['id']}): $imagePath\n";
            } else {
                echo "⚠️ Fichier image non trouvé: $imagePath\n";
            }
        } else {
            echo "ℹ️ Produit '{$product['name']}' a déjà des images\n";
        }
    }
    
    // Si on n'a qu'un seul produit, créons-en d'autres
    if (count($products) < 3) {
        echo "\nCréation de produits supplémentaires...\n";
        
        $newProducts = [
            ['name' => 'Création Artistique 1', 'description' => 'Belle création artistique unique', 'price' => 25.99, 'category' => 'Art'],
            ['name' => 'Œuvre Originale 2', 'description' => 'Pièce originale faite main', 'price' => 45.50, 'category' => 'Art'],
            ['name' => 'Design Moderne 3', 'description' => 'Design moderne et élégant', 'price' => 35.00, 'category' => 'Design']
        ];
        
        foreach ($newProducts as $i => $newProduct) {
            $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $newProduct['name'], 
                $newProduct['description'], 
                $newProduct['price'], 
                $newProduct['category']
            ]);
            
            $productId = $DB->lastInsertId();
            
            // Ajouter l'image correspondante
            $imagePath = $testImages[$i % count($testImages)];
            if (file_exists($imagePath)) {
                $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 1)");
                $stmt->execute([$productId, $imagePath]);
                
                echo "✅ Nouveau produit créé: '{$newProduct['name']}' avec image $imagePath\n";
            }
        }
    }
    
    // Statistiques finales
    $stmt = $DB->query("SELECT COUNT(*) FROM products");
    $totalProducts = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) FROM product_images");
    $totalImages = $stmt->fetchColumn();
    
    echo "\n=== RÉSUMÉ ===\n";
    echo "Total produits: $totalProducts\n";
    echo "Total images: $totalImages\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\n<a href='shop.php'>🛒 Voir la boutique</a> | <a href='debug_shop.php'>🔍 Debug</a>\n";
?>
