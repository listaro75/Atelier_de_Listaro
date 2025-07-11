<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    header('Location: connexion.php?error=admin_required');
    exit();
}

echo "<h1>🔍 Debug des Images de Produits</h1>";

// Récupérer tous les produits avec leurs images
$stmt = $DB->query("
    SELECT 
        p.id as product_id,
        p.name as product_name,
        pi.id as image_id,
        pi.image_path,
        pi.is_main,
        pi.created_at
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    ORDER BY p.id DESC, pi.is_main DESC
");
$results = $stmt->fetchAll();

if (empty($results)) {
    echo "<p style='color: red;'>❌ Aucun produit trouvé dans la base de données</p>";
} else {
    echo "<h2>📊 Résultats de la base de données :</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>ID Produit</th>
            <th>Nom Produit</th>
            <th>ID Image</th>
            <th>Chemin Image</th>
            <th>Principal</th>
            <th>Fichier Existe</th>
            <th>Test Affichage</th>
          </tr>";
    
    foreach ($results as $row) {
        $file_exists = "";
        $image_test = "";
        
        if ($row['image_path']) {
            // Vérifier si le fichier existe
            $file_path = __DIR__ . '/' . $row['image_path'];
            $file_exists = file_exists($file_path) ? "✅ OUI" : "❌ NON";
            
            // Test d'affichage
            if (file_exists($file_path)) {
                $image_test = "<img src='{$row['image_path']}' style='width: 50px; height: 50px; object-fit: cover;'>";
            } else {
                $image_test = "❌ Fichier manquant";
            }
        } else {
            $file_exists = "N/A";
            $image_test = "Pas d'image";
        }
        
        $is_main = $row['is_main'] ? "🌟 OUI" : "Non";
        
        echo "<tr>
                <td>{$row['product_id']}</td>
                <td>{$row['product_name']}</td>
                <td>{$row['image_id']}</td>
                <td style='font-family: monospace;'>{$row['image_path']}</td>
                <td>{$is_main}</td>
                <td>{$file_exists}</td>
                <td>{$image_test}</td>
              </tr>";
    }
    echo "</table>";
}

// Vérifier la structure des dossiers
echo "<h2>📁 Vérification des dossiers :</h2>";
$dirs_to_check = [
    'uploads' => __DIR__ . '/uploads',
    'uploads/products' => __DIR__ . '/uploads/products'
];

foreach ($dirs_to_check as $name => $path) {
    if (is_dir($path)) {
        $files = glob($path . '/*');
        $count = count($files);
        echo "<p>✅ <strong>{$name}</strong> : Existe ({$count} fichiers)</p>";
        
        if ($name === 'uploads/products' && $count > 0) {
            echo "<ul>";
            foreach (array_slice($files, 0, 10) as $file) {
                $filename = basename($file);
                $size = filesize($file);
                echo "<li>{$filename} ({$size} bytes)</li>";
            }
            if ($count > 10) echo "<li>... et " . ($count - 10) . " autres fichiers</li>";
            echo "</ul>";
        }
    } else {
        echo "<p>❌ <strong>{$name}</strong> : N'existe pas</p>";
    }
}

// Test d'affichage d'une image du shop
echo "<h2>🖼️ Test d'affichage depuis shop.php :</h2>";
$stmt = $DB->query("
    SELECT p.*, pi.image_path 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
    LIMIT 1
");
$test_product = $stmt->fetch();

if ($test_product) {
    echo "<div style='border: 1px solid #ccc; padding: 15px; margin: 10px 0;'>";
    echo "<h3>Produit de test : {$test_product['name']}</h3>";
    echo "<p><strong>Chemin en base :</strong> {$test_product['image_path']}</p>";
    
    if ($test_product['image_path']) {
        $full_path = __DIR__ . '/' . $test_product['image_path'];
        echo "<p><strong>Chemin complet :</strong> {$full_path}</p>";
        echo "<p><strong>Fichier existe :</strong> " . (file_exists($full_path) ? "✅ OUI" : "❌ NON") . "</p>";
        
        if (file_exists($full_path)) {
            echo "<p><strong>Test d'affichage :</strong></p>";
            echo "<img src='{$test_product['image_path']}' style='max-width: 200px; border: 1px solid #ddd;'>";
        }
    } else {
        echo "<p style='color: red;'>❌ Aucune image principale trouvée pour ce produit</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='admin_panel.php'>← Retour au panel d'administration</a></p>";
?>

<style>
table { font-family: Arial, sans-serif; }
th, td { padding: 8px; text-align: left; }
th { font-weight: bold; }
tr:nth-child(even) { background-color: #f9f9f9; }
</style>
