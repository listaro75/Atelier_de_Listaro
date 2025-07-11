<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');
include_once('_functions/image_utils.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    header('Location: connexion.php?error=admin_required');
    exit();
}

echo "<h1>🗑️ Test de Suppression de Produits avec Images</h1>";

// Afficher les produits existants
$stmt = $DB->query("
    SELECT 
        p.id,
        p.name,
        COUNT(pi.id) as image_count
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    GROUP BY p.id, p.name
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();

if (empty($products)) {
    echo "<p style='color: orange;'>⚠️ Aucun produit trouvé dans la base de données</p>";
} else {
    echo "<h2>📦 Produits disponibles :</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>
            <th>ID</th>
            <th>Nom du Produit</th>
            <th>Nombre d'Images</th>
            <th>Images</th>
            <th>Action</th>
          </tr>";
    
    foreach ($products as $product) {
        // Récupérer les chemins des images pour ce produit
        $stmt_images = $DB->prepare("SELECT image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
        $stmt_images->execute([$product['id']]);
        $images = $stmt_images->fetchAll();
        
        $images_display = "";
        foreach ($images as $img) {
            $badge = $img['is_main'] ? " 🌟" : "";
            $exists = file_exists(__DIR__ . '/' . $img['image_path']) ? "✅" : "❌";
            $images_display .= "<div style='font-size: 12px; margin: 2px 0;'>";
            $images_display .= $exists . " " . basename($img['image_path']) . $badge;
            $images_display .= "</div>";
        }
        if (empty($images_display)) {
            $images_display = "<em>Aucune image</em>";
        }
        
        echo "<tr>
                <td>{$product['id']}</td>
                <td><strong>{$product['name']}</strong></td>
                <td style='text-align: center;'>{$product['image_count']}</td>
                <td style='font-family: monospace; font-size: 11px;'>{$images_display}</td>
                <td style='text-align: center;'>
                    <button onclick='testDeleteProduct({$product['id']})' 
                            style='background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;'>
                        🗑️ Tester Suppression
                    </button>
                </td>
              </tr>";
    }
    echo "</table>";
}

// Traitement de la suppression de test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_delete'])) {
    $product_id = intval($_POST['product_id']);
    
    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
    echo "<h3>🧪 Résultat du test de suppression pour le produit ID: $product_id</h3>";
    
    $result = deleteProductWithImages($product_id, $DB);
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ <strong>Succès !</strong> {$result['message']}</p>";
        echo "<p>📊 <strong>Statistiques :</strong></p>";
        echo "<ul>";
        echo "<li>Nombre total d'images : {$result['total_images']}</li>";
        echo "<li>Fichiers supprimés : " . count($result['deleted_files']) . "</li>";
        echo "<li>Échecs de suppression : " . count($result['failed_files']) . "</li>";
        echo "</ul>";
        
        if (!empty($result['deleted_files'])) {
            echo "<p><strong>Fichiers supprimés :</strong></p><ul>";
            foreach ($result['deleted_files'] as $file) {
                echo "<li style='color: green;'>✅ $file</li>";
            }
            echo "</ul>";
        }
        
        if (!empty($result['failed_files'])) {
            echo "<p><strong>Échecs de suppression :</strong></p><ul>";
            foreach ($result['failed_files'] as $file) {
                echo "<li style='color: red;'>❌ $file</li>";
            }
            echo "</ul>";
        }
        
        echo "<script>setTimeout(() => location.reload(), 2000);</script>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Erreur :</strong> {$result['message']}</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
echo "<h3>ℹ️ Comment ça marche :</h3>";
echo "<ol>";
echo "<li><strong>Cliquez sur 'Tester Suppression'</strong> pour un produit</li>";
echo "<li><strong>La fonction vérifie :</strong> Base de données + fichiers sur le serveur</li>";
echo "<li><strong>Supprime dans l'ordre :</strong> Fichiers images → Entrées DB images → Likes → Produit</li>";
echo "<li><strong>Rollback automatique</strong> en cas d'erreur</li>";
echo "<li><strong>Logs détaillés</strong> pour le debugging</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='admin_panel.php'>← Retour au panel d'administration</a></p>";
?>

<script>
function testDeleteProduct(productId) {
    if (confirm(`⚠️ ATTENTION : Êtes-vous sûr de vouloir SUPPRIMER DÉFINITIVEMENT le produit ID ${productId} et toutes ses images ?\n\nCette action est IRRÉVERSIBLE !`)) {
        // Créer un formulaire et le soumettre
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="test_delete" value="1">
            <input type="hidden" name="product_id" value="${productId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<style>
table { font-family: Arial, sans-serif; }
th, td { padding: 8px; text-align: left; }
th { font-weight: bold; }
tr:nth-child(even) { background-color: #f9f9f9; }
button:hover { opacity: 0.8; }
</style>
