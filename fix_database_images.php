<?php
/**
 * Script pour corriger les chemins d'images dans la base de données
 */

include '_db/connexion_DB.php';

echo "<!DOCTYPE html>";
echo "<html><head><meta charset='UTF-8'><title>Correction BDD Images</title></head><body>";
echo "<h1>🔧 Correction Base de Données - Images</h1>";

try {
    // 1. Analyser l'état actuel
    echo "<h2>📊 État Actuel</h2>";
    
    $stmt = $DB->query("SELECT COUNT(*) FROM products");
    $productCount = $stmt->fetchColumn();
    echo "<p>Produits en base: <strong>$productCount</strong></p>";
    
    $stmt = $DB->query("SELECT COUNT(*) FROM product_images");
    $imageCount = $stmt->fetchColumn();
    echo "<p>Images en base: <strong>$imageCount</strong></p>";
    
    // 2. Lister tous les produits
    echo "<h2>📋 Détail des Produits</h2>";
    $stmt = $DB->query("SELECT id, name, description, price, category FROM products ORDER BY id");
    $products = $stmt->fetchAll();
    
    if (empty($products)) {
        echo "<p style='color: orange;'>⚠️ Aucun produit trouvé</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Nom</th><th>Prix</th><th>Catégorie</th><th>Images</th></tr>";
        
        foreach ($products as $product) {
            // Chercher les images pour ce produit
            $stmt2 = $DB->prepare("SELECT id, image_path, is_main FROM product_images WHERE product_id = ?");
            $stmt2->execute([$product['id']]);
            $images = $stmt2->fetchAll();
            
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>" . htmlspecialchars($product['name']) . "</td>";
            echo "<td>{$product['price']} €</td>";
            echo "<td>" . htmlspecialchars($product['category']) . "</td>";
            echo "<td>";
            
            if (empty($images)) {
                echo "<span style='color: red;'>❌ Aucune image</span>";
            } else {
                foreach ($images as $img) {
                    $exists = file_exists($img['image_path']) ? '✅' : '❌';
                    $main = $img['is_main'] ? '(★)' : '';
                    echo "<div>$exists " . htmlspecialchars($img['image_path']) . " $main</div>";
                }
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // 3. Vérifier les fichiers physiques
    echo "<h2>📁 Fichiers Physiques</h2>";
    $uploadDir = __DIR__ . '/uploads/products';
    
    if (!is_dir($uploadDir)) {
        echo "<p style='color: red;'>❌ Dossier uploads/products n'existe pas</p>";
        echo "<p>Création du dossier...</p>";
        
        if (mkdir($uploadDir, 0755, true)) {
            echo "<p style='color: green;'>✅ Dossier créé</p>";
        } else {
            echo "<p style='color: red;'>❌ Erreur création dossier</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Dossier uploads/products existe</p>";
        
        $files = array_diff(scandir($uploadDir), ['.', '..']);
        if (empty($files)) {
            echo "<p>📂 Dossier vide</p>";
        } else {
            echo "<p>📂 Fichiers trouvés:</p>";
            echo "<ul>";
            foreach ($files as $file) {
                $size = filesize($uploadDir . '/' . $file);
                echo "<li>$file ($size bytes)</li>";
            }
            echo "</ul>";
        }
    }
    
    // 4. Actions de correction
    echo "<h2>🛠️ Actions de Correction</h2>";
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_test_images':
                echo "<h3>Création d'images de test</h3>";
                
                $testImages = [
                    'test-product-1.svg' => '#ff6b6b',
                    'test-product-2.svg' => '#4ecdc4',
                    'test-product-3.svg' => '#45b7d1'
                ];
                
                foreach ($testImages as $filename => $color) {
                    $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="400" height="300" viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="' . $color . '"/>
    <rect x="20" y="20" width="360" height="260" fill="white" opacity="0.9"/>
    <circle cx="200" cy="120" r="40" fill="' . $color . '"/>
    <text x="200" y="180" font-family="Arial, sans-serif" font-size="18" fill="#333" text-anchor="middle">Image Test</text>
    <text x="200" y="200" font-family="Arial, sans-serif" font-size="14" fill="#666" text-anchor="middle">' . $filename . '</text>
</svg>';
                    
                    $filepath = $uploadDir . '/' . $filename;
                    if (file_put_contents($filepath, $svg)) {
                        echo "<p>✅ Créé: $filename</p>";
                    } else {
                        echo "<p>❌ Erreur: $filename</p>";
                    }
                }
                break;
                
            case 'link_images_to_products':
                echo "<h3>Association images ↔ produits</h3>";
                
                // Supprimer les anciennes associations
                $DB->exec("DELETE FROM product_images");
                echo "<p>🗑️ Anciennes associations supprimées</p>";
                
                // Créer de nouvelles associations
                $testImages = ['test-product-1.svg', 'test-product-2.svg', 'test-product-3.svg'];
                $stmt = $DB->query("SELECT id FROM products ORDER BY id");
                $productIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($productIds as $index => $productId) {
                    $imageIndex = $index % count($testImages);
                    $imagePath = 'uploads/products/' . $testImages[$imageIndex];
                    
                    if (file_exists($imagePath)) {
                        $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, 1)");
                        $stmt->execute([$productId, $imagePath]);
                        echo "<p>✅ Produit $productId ↔ $imagePath</p>";
                    }
                }
                break;
                
            case 'create_sample_products':
                echo "<h3>Création de produits d'exemple</h3>";
                
                $sampleProducts = [
                    ['name' => 'Création Artistique Rouge', 'description' => 'Belle création aux tons rouges', 'price' => 89.99, 'category' => 'Art'],
                    ['name' => 'Design Moderne Bleu', 'description' => 'Design contemporain bleu et blanc', 'price' => 129.50, 'category' => 'Design'],
                    ['name' => 'Œuvre Originale Verte', 'description' => 'Pièce unique aux tons verts', 'price' => 67.00, 'category' => 'Art']
                ];
                
                foreach ($sampleProducts as $product) {
                    $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, created_at) VALUES (?, ?, ?, ?, NOW())");
                    $stmt->execute([$product['name'], $product['description'], $product['price'], $product['category']]);
                    echo "<p>✅ Créé: {$product['name']}</p>";
                }
                break;
        }
        
        echo "<p><a href='debug_shop.php'>🔍 Voir le debug</a> | <a href='shop.php'>🛒 Voir la boutique</a></p>";
        echo "<hr>";
    }
    
    // Formulaires d'action
    echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0;'>";
    
    echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
    echo "<h4>📁 Créer Images Test</h4>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='create_test_images'>";
    echo "<button type='submit' style='background: #007bff; color: white; border: none; padding: 10px 15px; border-radius: 4px;'>Créer Images SVG</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
    echo "<h4>🔗 Lier Images ↔ Produits</h4>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='link_images_to_products'>";
    echo "<button type='submit' style='background: #28a745; color: white; border: none; padding: 10px 15px; border-radius: 4px;'>Associer Images</button>";
    echo "</form>";
    echo "</div>";
    
    echo "<div style='border: 1px solid #ddd; padding: 15px; border-radius: 8px;'>";
    echo "<h4>🛍️ Créer Produits Exemple</h4>";
    echo "<form method='post'>";
    echo "<input type='hidden' name='action' value='create_sample_products'>";
    echo "<button type='submit' style='background: #ffc107; color: black; border: none; padding: 10px 15px; border-radius: 4px;'>Créer Produits</button>";
    echo "</form>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; background: #ffe6e6; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "<h3>❌ Erreur</h3>";
    echo "<p><strong>Message:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Fichier:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Ligne:</strong> " . $e->getLine() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>
