<?php
include_once('_db/connexion_DB.php');
include_once('_functions/image_utils.php');

echo "<h1>Debug Shop</h1>";

try {
    // Test de la base de données
    echo "<h2>Test Base de Données</h2>";
    $stmt = $DB->query("SELECT COUNT(*) FROM products");
    $count = $stmt->fetchColumn();
    echo "Nombre de produits: $count<br>";
    
    if ($count > 0) {
        $stmt = $DB->query("SELECT id, name FROM products LIMIT 3");
        $products = $stmt->fetchAll();
        echo "Produits trouvés:<br>";
        foreach ($products as $product) {
            echo "- ID: {$product['id']}, Nom: {$product['name']}<br>";
        }
    }
    
    // Test des images
    echo "<h2>Test Images</h2>";
    $stmt = $DB->query("SELECT COUNT(*) FROM product_images");
    $imageCount = $stmt->fetchColumn();
    echo "Nombre d'images: $imageCount<br>";
    
    if ($imageCount > 0) {
        $stmt = $DB->query("SELECT product_id, image_path FROM product_images LIMIT 3");
        $images = $stmt->fetchAll();
        echo "Images trouvées:<br>";
        foreach ($images as $image) {
            $exists = file_exists($image['image_path']) ? 'OUI' : 'NON';
            echo "- Produit {$image['product_id']}: {$image['image_path']} (Existe: $exists)<br>";
        }
    }
    
    // Test des fonctions
    echo "<h2>Test Fonctions</h2>";
    if (function_exists('getImageUrl')) {
        echo "✅ getImageUrl() existe<br>";
        $testUrl = getImageUrl('test.jpg');
        echo "Test URL: $testUrl<br>";
    } else {
        echo "❌ getImageUrl() n'existe pas<br>";
    }
    
    if (function_exists('createPlaceholderImageUrl')) {
        echo "✅ createPlaceholderImageUrl() existe<br>";
    } else {
        echo "❌ createPlaceholderImageUrl() n'existe pas<br>";
    }
    
    // Test du dossier uploads
    echo "<h2>Test Dossiers</h2>";
    $uploadDir = __DIR__ . '/uploads/products';
    echo "Dossier uploads/products: " . (is_dir($uploadDir) ? 'EXISTE' : 'N\'EXISTE PAS') . "<br>";
    
    if (is_dir($uploadDir)) {
        $files = array_diff(scandir($uploadDir), ['.', '..']);
        echo "Fichiers dans uploads/products: " . count($files) . "<br>";
        foreach ($files as $file) {
            echo "- $file<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>ERREUR</h2>";
    echo "Message: " . $e->getMessage() . "<br>";
    echo "Fichier: " . $e->getFile() . "<br>";
    echo "Ligne: " . $e->getLine() . "<br>";
}

echo "<br><a href='shop.php'>← Retour au shop</a>";
?>
