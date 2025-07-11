<?php
// Version corrigée de la section produits pour admin_panel_fixed.php
// Ne pas redémarrer la session si elle est déjà active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

// Debug de la session
error_log("Session dans products_corrected.php: " . print_r($_SESSION, true));
error_log("is_admin() result: " . (is_admin() ? 'true' : 'false'));

if (!is_admin()) {
    echo "<div style='color: red; padding: 20px; background: #ffe6e6; border: 1px solid red; border-radius: 5px;'>";
    echo "<h3>❌ Problème d'authentification</h3>";
    echo "<p>Session actuelle :</p>";
    echo "<ul>";
    foreach ($_SESSION as $key => $value) {
        echo "<li><strong>{$key}:</strong> " . htmlspecialchars($value) . "</li>";
    }
    echo "</ul>";
    echo "<p><a href='../admin_login_express.php' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Se reconnecter</a></p>";
    echo "</div>";
    return;
}

// Récupérer les produits
try {
    $stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
    
    // Récupérer les catégories
    $stmt = $DB->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll();
    
    echo "<div style='padding: 20px;'>";
    echo "<h2 style='color: #333; margin-bottom: 20px;'><i class='fas fa-box'></i> Gestion des produits</h2>";
    
    if (empty($products)) {
        echo "<div style='text-align: center; padding: 50px; background: #f8f9fa; border-radius: 8px;'>";
        echo "<i class='fas fa-box-open' style='font-size: 3em; color: #ccc; margin-bottom: 15px;'></i>";
        echo "<h3>Aucun produit trouvé</h3>";
        echo "<p>Ajoutez votre premier produit pour commencer !</p>";
        echo "</div>";
    } else {
        echo "<div style='margin-bottom: 20px;'>";
        echo "<p><strong>Nombre de produits :</strong> " . count($products) . "</p>";
        echo "<p><strong>Catégories :</strong> " . count($categories) . "</p>";
        echo "</div>";
        
        echo "<div style='display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;'>";
        
        foreach ($products as $product) {
            echo "<div style='background: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>";
            
            // Image du produit
            echo "<div style='height: 200px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; position: relative;'>";
            
            // Récupérer l'image principale
            $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
            $stmt->execute([$product['id']]);
            $main_image = $stmt->fetch();
            
            if ($main_image && file_exists(__DIR__ . '/../' . $main_image['image_path'])) {
                echo "<img src='" . htmlspecialchars($main_image['image_path']) . "' alt='" . htmlspecialchars($product['name']) . "' style='width: 100%; height: 100%; object-fit: cover;'>";
            } else {
                echo "<div style='text-align: center; color: #666;'>";
                echo "<i class='fas fa-image' style='font-size: 2em; margin-bottom: 10px;'></i>";
                echo "<p>Aucune image</p>";
                echo "</div>";
            }
            
            // Compter les images
            $stmt = $DB->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
            $stmt->execute([$product['id']]);
            $image_count = $stmt->fetchColumn();
            
            if ($image_count > 1) {
                echo "<div style='position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 12px; font-size: 12px;'>";
                echo "<i class='fas fa-images'></i> " . $image_count;
                echo "</div>";
            }
            
            echo "</div>";
            
            // Informations du produit
            echo "<div style='padding: 15px;'>";
            echo "<h4 style='margin: 0 0 10px 0; color: #333;'>" . htmlspecialchars($product['name']) . "</h4>";
            
            if ($product['description']) {
                $short_desc = strlen($product['description']) > 100 ? substr($product['description'], 0, 100) . '...' : $product['description'];
                echo "<p style='color: #666; font-size: 13px; margin-bottom: 15px;'>" . htmlspecialchars($short_desc) . "</p>";
            }
            
            echo "<div style='display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin-bottom: 15px;'>";
            echo "<span style='font-weight: bold; color: #e74c3c; font-size: 16px;'>" . number_format($product['price'], 2) . " €</span>";
            echo "<span style='background: #e9ecef; padding: 2px 8px; border-radius: 12px; font-size: 12px;'>Stock: " . $product['stock'] . "</span>";
            
            if ($product['category']) {
                echo "<span style='background: #007bff; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;'>" . htmlspecialchars($product['category']) . "</span>";
            }
            echo "</div>";
            
            // Actions
            echo "<div style='display: flex; gap: 8px; justify-content: center;'>";
            echo "<a href='admin_sections/products.php?view=" . $product['id'] . "' target='_blank' style='background: #17a2b8; color: white; padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 12px;'>";
            echo "<i class='fas fa-eye'></i> Voir";
            echo "</a>";
            echo "<button onclick='editProduct(" . $product['id'] . ")' style='background: #ffc107; color: #212529; padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;'>";
            echo "<i class='fas fa-edit'></i> Modifier";
            echo "</button>";
            echo "</div>";
            
            echo "</div>";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    echo "<div style='margin-top: 30px; text-align: center;'>";
    echo "<a href='admin_sections/products.php' target='_blank' style='background: #28a745; color: white; padding: 15px 25px; border-radius: 5px; text-decoration: none; font-size: 16px;'>";
    echo "<i class='fas fa-external-link-alt'></i> Ouvrir la gestion complète des produits";
    echo "</a>";
    echo "</div>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; padding: 20px; background: #ffe6e6; border: 1px solid red; border-radius: 5px;'>";
    echo "<h3>❌ Erreur de base de données</h3>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}
?>

<script>
function editProduct(id) {
    alert('Fonction d\'édition en cours de développement. Product ID: ' + id);
}
</script>
