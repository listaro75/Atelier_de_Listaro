<?php
include '_db/connexion_DB.php';

echo "=== DIAGNOSTIC IMAGES ===\n\n";

// Vérifier les produits
$stmt = $DB->query('SELECT COUNT(*) FROM products');
$productCount = $stmt->fetchColumn();
echo "Nombre de produits en base: $productCount\n";

// Vérifier les images
$stmt = $DB->query('SELECT COUNT(*) FROM product_images');
$imageCount = $stmt->fetchColumn();
echo "Nombre d'images en base: $imageCount\n";

// Vérifier quelques chemins d'images
echo "\n=== CHEMINS D'IMAGES ===\n";
$stmt = $DB->query('SELECT image_path FROM product_images LIMIT 5');
$images = $stmt->fetchAll();

if (empty($images)) {
    echo "Aucune image trouvée en base de données\n";
} else {
    foreach ($images as $image) {
        $path = $image['image_path'];
        $fullPath = __DIR__ . '/' . $path;
        $exists = file_exists($fullPath) ? 'OUI' : 'NON';
        echo "Chemin: $path -> Existe: $exists\n";
    }
}

// Vérifier le dossier uploads/products
$uploadDir = __DIR__ . '/uploads/products';
echo "\n=== DOSSIER UPLOADS ===\n";
echo "Dossier: $uploadDir\n";
echo "Existe: " . (is_dir($uploadDir) ? 'OUI' : 'NON') . "\n";
echo "Permissions écriture: " . (is_writable($uploadDir) ? 'OUI' : 'NON') . "\n";

// Lister les fichiers dans uploads/products
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    $files = array_filter($files, function($file) { return $file !== '.' && $file !== '..'; });
    echo "Fichiers présents: " . count($files) . "\n";
    if (count($files) > 0) {
        echo "Liste:\n";
        foreach ($files as $file) {
            echo "  - $file\n";
        }
    }
}
?>
