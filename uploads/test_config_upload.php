<?php
// Test de la configuration pour l'upload multiple d'images
require_once '_config/env.php';
require_once '_db/connexion_DB.php';

echo "<h1>🔍 Test de Configuration - Upload Multiple Images</h1>";

echo "<h2>📊 Vérification de la base de données</h2>";

try {
    // Vérifier la table products
    $stmt = $DB->query("DESCRIBE products");
    $products_columns = $stmt->fetchAll();
    echo "<h3>✅ Table 'products' :</h3>";
    echo "<ul>";
    foreach ($products_columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Vérifier la table product_images
    $stmt = $DB->query("DESCRIBE product_images");
    $images_columns = $stmt->fetchAll();
    echo "<h3>✅ Table 'product_images' :</h3>";
    echo "<ul>";
    foreach ($images_columns as $column) {
        echo "<li><strong>{$column['Field']}</strong> - {$column['Type']}</li>";
    }
    echo "</ul>";
    
    // Compter les données
    $stmt = $DB->query("SELECT COUNT(*) as count FROM products");
    $product_count = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM product_images");
    $image_count = $stmt->fetchColumn();
    
    echo "<h3>📈 Statistiques :</h3>";
    echo "<ul>";
    echo "<li>Produits : <strong>$product_count</strong></li>";
    echo "<li>Images : <strong>$image_count</strong></li>";
    echo "</ul>";
    
    // Vérifier les produits avec images
    $stmt = $DB->query("
        SELECT p.id, p.name, COUNT(pi.id) as image_count 
        FROM products p 
        LEFT JOIN product_images pi ON p.id = pi.product_id 
        GROUP BY p.id, p.name 
        ORDER BY image_count DESC
    ");
    $products_with_images = $stmt->fetchAll();
    
    echo "<h3>🖼️ Produits et leurs images :</h3>";
    if (empty($products_with_images)) {
        echo "<p>Aucun produit trouvé.</p>";
    } else {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nom du produit</th><th>Nombre d'images</th></tr>";
        foreach ($products_with_images as $product) {
            echo "<tr>";
            echo "<td>{$product['id']}</td>";
            echo "<td>{$product['name']}</td>";
            echo "<td><strong>{$product['image_count']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erreur de base de données : " . $e->getMessage() . "</p>";
}

echo "<h2>📁 Vérification des dossiers</h2>";

$upload_dirs = [
    'uploads/',
    'uploads/products/',
];

foreach ($upload_dirs as $dir) {
    if (is_dir($dir)) {
        $permissions = substr(sprintf('%o', fileperms($dir)), -4);
        $writable = is_writable($dir) ? "✅ Écriture autorisée" : "❌ Pas d'écriture";
        echo "<p><strong>$dir</strong> : Existe (permissions: $permissions) - $writable</p>";
        
        // Lister les fichiers dans uploads/products/
        if ($dir === 'uploads/products/') {
            $files = glob($dir . '*');
            if (empty($files)) {
                echo "<p style='margin-left: 20px;'>📁 Dossier vide</p>";
            } else {
                echo "<p style='margin-left: 20px;'>📸 Images trouvées : " . count($files) . "</p>";
                foreach ($files as $file) {
                    $filename = basename($file);
                    $size = number_format(filesize($file) / 1024, 2);
                    echo "<p style='margin-left: 40px;'>• $filename ({$size} KB)</p>";
                }
            }
        }
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>$dir</strong> : N'existe pas - Création recommandée</p>";
        if (mkdir($dir, 0755, true)) {
            echo "<p style='color: green; margin-left: 20px;'>✅ Dossier créé avec succès</p>";
        } else {
            echo "<p style='color: red; margin-left: 20px;'>❌ Impossible de créer le dossier</p>";
        }
    }
}

echo "<h2>⚙️ Configuration PHP</h2>";

$php_config = [
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_file_uploads' => ini_get('max_file_uploads'),
    'file_uploads' => ini_get('file_uploads') ? 'Activé' : 'Désactivé',
];

echo "<ul>";
foreach ($php_config as $key => $value) {
    echo "<li><strong>$key</strong> : $value</li>";
}
echo "</ul>";

echo "<h2>🧪 Fichiers de test</h2>";

$test_files = [
    'admin_panel.php' => 'Panel d\'administration principal',
    'admin_sections/products.php' => 'Gestion des produits',
    'admin_sections/get_product_images.php' => 'API récupération images',
    'test_upload_multiple.html' => 'Page de test upload multiple',
    'GUIDE_UPLOAD_MULTIPLE.md' => 'Documentation du système',
];

foreach ($test_files as $file => $description) {
    if (file_exists($file)) {
        echo "<p>✅ <strong>$file</strong> - $description</p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>$file</strong> - Fichier manquant</p>";
    }
}

echo "<h2>🎯 Prochaines étapes</h2>";
echo "<ol>";
echo "<li>Accéder à <a href='admin_panel.php'>admin_panel.php</a> pour tester l'interface</li>";
echo "<li>Utiliser <a href='test_upload_multiple.html'>test_upload_multiple.html</a> pour les tests</li>";
echo "<li>Créer un produit de test avec plusieurs images</li>";
echo "<li>Vérifier l'affichage dans la boutique publique</li>";
echo "</ol>";

echo "<hr>";
echo "<p><small>Test effectué le : " . date('d/m/Y H:i:s') . "</small></p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #3498db; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
h3 { color: #27ae60; }
table { margin: 10px 0; }
th { background: #f8f9fa; }
</style>
