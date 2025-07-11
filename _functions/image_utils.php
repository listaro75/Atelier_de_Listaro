<?php
// Utilitaire pour gérer la limitation des images de produits

/**
 * Compte le nombre d'images d'un produit
 */
function countProductImages($product_id, $DB) {
    $stmt = $DB->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
    $stmt->execute([$product_id]);
    return $stmt->fetchColumn();
}

/**
 * Vérifie si on peut ajouter des images à un produit
 */
function canAddImages($product_id, $new_images_count, $DB, $max_images = 5) {
    $existing_count = countProductImages($product_id, $DB);
    $total = $existing_count + $new_images_count;
    
    return [
        'can_add' => $total <= $max_images,
        'existing_count' => $existing_count,
        'new_count' => $new_images_count,
        'total_count' => $total,
        'max_allowed' => $max_images,
        'remaining_slots' => max(0, $max_images - $existing_count),
        'message' => $total > $max_images 
            ? "Vous ne pouvez avoir que $max_images images maximum par produit. Vous avez déjà $existing_count images. Vous pouvez ajouter au maximum " . max(0, $max_images - $existing_count) . " nouvelles images."
            : "OK"
    ];
}

/**
 * Valide le nombre d'images lors de l'upload
 */
function validateImageUpload($files, $product_id = null, $DB = null, $max_images = 5) {
    $files_count = is_array($files['name']) ? count($files['name']) : 1;
    
    // Pour un nouveau produit
    if ($product_id === null) {
        return [
            'valid' => $files_count <= $max_images,
            'message' => $files_count > $max_images 
                ? "Vous ne pouvez ajouter que $max_images images maximum par produit"
                : "OK",
            'count' => $files_count
        ];
    }
    
    // Pour un produit existant
    if ($DB) {
        return canAddImages($product_id, $files_count, $DB, $max_images);
    }
    
    return ['valid' => false, 'message' => 'Paramètres insuffisants pour la validation'];
}

/**
 * Nettoie les fichiers orphelins (images sans produit associé)
 */
function cleanOrphanImages($DB, $upload_dir = 'uploads/products') {
    $cleaned = 0;
    
    // Récupérer toutes les images en base
    $stmt = $DB->query("SELECT image_path FROM product_images");
    $db_images = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Scanner le dossier d'upload
    $upload_path = __DIR__ . '/' . $upload_dir;
    if (is_dir($upload_path)) {
        $files = scandir($upload_path);
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..' && is_file($upload_path . '/' . $file)) {
                $file_path = $upload_dir . '/' . $file;
                
                // Si le fichier n'est pas en base, le supprimer
                if (!in_array($file_path, $db_images)) {
                    if (unlink($upload_path . '/' . $file)) {
                        $cleaned++;
                    }
                }
            }
        }
    }
    
    return $cleaned;
}

/**
 * Obtient des statistiques sur les images
 */
function getImageStats($DB) {
    $stats = [];
    
    // Nombre total d'images
    $stmt = $DB->query("SELECT COUNT(*) FROM product_images");
    $stats['total_images'] = $stmt->fetchColumn();
    
    // Nombre de produits avec images
    $stmt = $DB->query("SELECT COUNT(DISTINCT product_id) FROM product_images");
    $stats['products_with_images'] = $stmt->fetchColumn();
    
    // Nombre moyen d'images par produit
    if ($stats['products_with_images'] > 0) {
        $stats['avg_images_per_product'] = round($stats['total_images'] / $stats['products_with_images'], 2);
    } else {
        $stats['avg_images_per_product'] = 0;
    }
    
    // Produit avec le plus d'images
    $stmt = $DB->query("
        SELECT product_id, COUNT(*) as image_count 
        FROM product_images 
        GROUP BY product_id 
        ORDER BY image_count DESC 
        LIMIT 1
    ");
    $max_result = $stmt->fetch();
    $stats['max_images_product'] = $max_result ? $max_result['image_count'] : 0;
    $stats['max_images_product_id'] = $max_result ? $max_result['product_id'] : null;
    
    return $stats;
}
?>
