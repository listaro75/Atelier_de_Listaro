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

/**
 * Supprime un produit et toutes ses images associées de manière sécurisée
 */
function deleteProductWithImages($product_id, $DB) {
    try {
        $DB->beginTransaction();
        
        // 1. Récupérer toutes les images du produit
        $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $images = $stmt->fetchAll();
        
        $deleted_files = [];
        $failed_files = [];
        
        // 2. Supprimer les fichiers images du serveur
        foreach ($images as $image) {
            $file_path = __DIR__ . '/../' . $image['image_path'];
            if (file_exists($file_path)) {
                if (unlink($file_path)) {
                    $deleted_files[] = $image['image_path'];
                    error_log("✅ Image supprimée : " . $file_path);
                } else {
                    $failed_files[] = $image['image_path'];
                    error_log("❌ Échec suppression : " . $file_path);
                }
            } else {
                // Fichier déjà absent, mais on le note
                error_log("⚠️ Fichier déjà absent : " . $file_path);
            }
        }
        
        // 3. Supprimer les entrées d'images de la base de données
        $stmt = $DB->prepare("DELETE FROM product_images WHERE product_id = ?");
        $stmt->execute([$product_id]);
        
        // 4. Supprimer les likes du produit (si table existe)
        try {
            $stmt = $DB->prepare("DELETE FROM product_likes WHERE product_id = ?");
            $stmt->execute([$product_id]);
        } catch (Exception $e) {
            // Table product_likes pourrait ne pas exister
            error_log("Note: table product_likes non trouvée ou erreur : " . $e->getMessage());
        }
        
        // 5. Supprimer les éléments de panier (si table existe)
        try {
            $stmt = $DB->prepare("DELETE FROM cart WHERE product_id = ?");
            $stmt->execute([$product_id]);
        } catch (Exception $e) {
            // Table cart pourrait ne pas exister
            error_log("Note: table cart non trouvée ou erreur : " . $e->getMessage());
        }
        
        // 6. Supprimer le produit lui-même
        $stmt = $DB->prepare("DELETE FROM products WHERE id = ?");
        if (!$stmt->execute([$product_id])) {
            throw new Exception("Erreur lors de la suppression du produit en base");
        }
        
        $DB->commit();
        
        return [
            'success' => true,
            'message' => 'Produit supprimé avec succès',
            'deleted_files' => $deleted_files,
            'failed_files' => $failed_files,
            'total_images' => count($images)
        ];
        
    } catch (Exception $e) {
        $DB->rollback();
        error_log("❌ Erreur lors de la suppression du produit $product_id : " . $e->getMessage());
        
        return [
            'success' => false,
            'message' => 'Erreur lors de la suppression : ' . $e->getMessage(),
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Obtient l'URL de l'image avec fallback
 */
function getImageUrl($imagePath, $defaultImage = null) {
    if (empty($imagePath)) {
        return $defaultImage ?: createPlaceholderImageUrl();
    }
    
    // Vérifier si le fichier existe
    $fullPath = __DIR__ . '/../' . $imagePath;
    if (!file_exists($fullPath)) {
        return $defaultImage ?: createPlaceholderImageUrl();
    }
    
    return $imagePath;
}

/**
 * Génère le HTML pour une image de produit avec fallback
 */
function renderProductImage($imagePath, $alt = 'Image produit', $class = 'product-image') {
    if (empty($imagePath) || !file_exists(__DIR__ . '/../' . $imagePath)) {
        return '<div class="product-image-placeholder">Aucune image</div>';
    }
    
    return '<img src="' . htmlspecialchars($imagePath) . '" 
                 alt="' . htmlspecialchars($alt) . '" 
                 class="' . htmlspecialchars($class) . '" 
                 onerror="this.style.display=\'none\'; this.nextElementSibling.style.display=\'flex\';" 
                 loading="lazy">
            <div class="product-image-placeholder" style="display: none;">Image non disponible</div>';
}

/**
 * Vérifie si le dossier d'upload existe et le crée si nécessaire
 */
function ensureUploadDir($dir = 'uploads/products') {
    $fullPath = __DIR__ . '/../' . $dir;
    
    if (!is_dir($fullPath)) {
        if (!mkdir($fullPath, 0755, true)) {
            return false;
        }
    }
    
    // Vérifier les permissions
    if (!is_writable($fullPath)) {
        chmod($fullPath, 0755);
    }
    
    return true;
}

/**
 * Crée une URL d'image placeholder SVG
 */
function createPlaceholderImageUrl($width = 300, $height = 200, $text = 'No Image') {
    $svg = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="#f0f0f0"/>
    <text x="50%" y="50%" font-family="Arial, sans-serif" font-size="16" fill="#666" text-anchor="middle" dy=".3em">' . $text . '</text>
    <circle cx="' . ($width/2 - 20) . '" cy="' . ($height/2 - 20) . '" r="15" fill="#ddd"/>
    <path d="M' . ($width/2 - 25) . ',' . ($height/2 - 15) . ' L' . ($width/2 - 15) . ',' . ($height/2 - 25) . ' L' . ($width/2 - 10) . ',' . ($height/2 - 20) . ' L' . ($width/2 - 5) . ',' . ($height/2 - 10) . ' L' . ($width/2 + 5) . ',' . ($height/2) . ' L' . ($width/2 - 25) . ',' . ($height/2) . ' Z" fill="#bbb"/>
</svg>';
    
    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}
?>
