<?php
/**
 * Script pour créer des produits et images de test
 */

include '_db/connexion_DB.php';
include '_functions/image_utils.php';

echo "=== CRÉATION DE DONNÉES DE TEST ===\n\n";

// S'assurer que le dossier existe
ensureUploadDir();

// Créer quelques images de test
$testImages = [
    'test-product-1.svg',
    'test-product-2.svg', 
    'test-product-3.svg'
];

$uploadDir = __DIR__ . '/uploads/products/';

foreach ($testImages as $i => $imageName) {
    $imagePath = $uploadDir . $imageName;
    
    if (!file_exists($imagePath)) {
        $colors = ['#ff6b6b', '#4ecdc4', '#45b7d1'];
        $color = $colors[$i % 3];
        
        $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="300" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="' . $color . '"/>
    <rect x="20" y="20" width="360" height="260" fill="white" opacity="0.9"/>
    <circle cx="200" cy="120" r="40" fill="' . $color . '"/>
    <text x="200" y="180" font-family="Arial, sans-serif" font-size="18" fill="#333" text-anchor="middle">Produit Test ' . ($i + 1) . '</text>
    <text x="200" y="200" font-family="Arial, sans-serif" font-size="14" fill="#666" text-anchor="middle">Image de démonstration</text>
</svg>';
        
        if (file_put_contents($imagePath, $svg)) {
            echo "✅ Image de test créée : $imageName\n";
        } else {
            echo "❌ Erreur lors de la création de : $imageName\n";
        }
    }
}

// Vérifier s'il y a des produits en base
$stmt = $DB->query("SELECT COUNT(*) FROM products");
$productCount = $stmt->fetchColumn();

echo "\nNombre de produits en base : $productCount\n";

if ($productCount == 0) {
    echo "Création de produits de test...\n";
    
    $testProducts = [
        ['name' => 'Produit Test 1', 'description' => 'Description du produit test 1', 'price' => 19.99, 'category' => 'Test'],
        ['name' => 'Produit Test 2', 'description' => 'Description du produit test 2', 'price' => 29.99, 'category' => 'Test'],
        ['name' => 'Produit Test 3', 'description' => 'Description du produit test 3', 'price' => 39.99, 'category' => 'Test']
    ];
    
    foreach ($testProducts as $i => $product) {
        try {
            $stmt = $DB->prepare("INSERT INTO products (name, description, price, category) VALUES (?, ?, ?, ?)");
            $stmt->execute([$product['name'], $product['description'], $product['price'], $product['category']]);
            $productId = $DB->lastInsertId();
            
            // Ajouter l'image de test
            $imagePath = 'uploads/products/' . $testImages[$i];
            $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 1)");
            $stmt->execute([$productId, $imagePath]);
            
            echo "✅ Produit créé : " . $product['name'] . " (ID: $productId)\n";
        } catch (Exception $e) {
            echo "❌ Erreur lors de la création du produit : " . $e->getMessage() . "\n";
        }
    }
}

// Vérifier les images en base
$stmt = $DB->query("SELECT COUNT(*) FROM product_images");
$imageCount = $stmt->fetchColumn();

echo "\nNombre d'images en base : $imageCount\n";

if ($imageCount == 0) {
    echo "⚠️ Aucune image trouvée en base de données\n";
    echo "Les produits sans images afficheront un placeholder\n";
}

echo "\n=== CRÉATION TERMINÉE ===\n";
echo "Vous pouvez maintenant visiter la boutique pour voir les produits\n";
?>
