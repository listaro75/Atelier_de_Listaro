<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

echo "<!DOCTYPE html><html><head><title>Diagnostic Produits</title>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.warning { color: orange; }
.info { color: blue; }
table { border-collapse: collapse; margin: 10px 0; width: 100%; }
td, th { border: 1px solid #ccc; padding: 8px; text-align: left; }
th { background: #f5f5f5; }
pre { background: #f0f0f0; padding: 10px; border: 1px solid #ccc; overflow-x: auto; }
.btn { padding: 10px 15px; margin: 5px; border: none; border-radius: 5px; cursor: pointer; }
.btn-primary { background: #3498db; color: white; }
.btn-success { background: #27ae60; color: white; }
.btn-danger { background: #e74c3c; color: white; }
</style></head><body>";

echo "<h1>🔧 Diagnostic Produits</h1>";

try {
    // Test connexion base de données
    $DB->query("SELECT 1");
    echo "<p class='success'>✅ Connexion base de données OK</p>";
    
    // Vérifier les tables
    $stmt = $DB->query("SHOW TABLES LIKE 'products'");
    if ($stmt->fetch()) {
        echo "<p class='success'>✅ Table 'products' existe</p>";
        
        // Compter les produits
        $stmt = $DB->query("SELECT COUNT(*) as count FROM products");
        $count = $stmt->fetchColumn();
        echo "<p class='info'>📊 Nombre de produits: $count</p>";
        
        if ($count > 0) {
            // Afficher les premiers produits
            $stmt = $DB->query("SELECT id, name, price, stock, created_at FROM products ORDER BY id ASC LIMIT 5");
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h2>📋 Premiers produits:</h2>";
            echo "<table>";
            echo "<tr><th>ID</th><th>Nom</th><th>Prix</th><th>Stock</th><th>Date</th></tr>";
            
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($product['id']) . "</td>";
                echo "<td>" . htmlspecialchars($product['name']) . "</td>";
                echo "<td>" . htmlspecialchars($product['price']) . " €</td>";
                echo "<td>" . htmlspecialchars($product['stock']) . "</td>";
                echo "<td>" . htmlspecialchars($product['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='warning'>⚠️ Aucun produit en base</p>";
            echo "<button class='btn btn-success' onclick='addTestProducts()'>Ajouter des produits de test</button>";
        }
    } else {
        echo "<p class='error'>❌ Table 'products' n'existe pas</p>";
    }
    
    // Vérifier la table des images
    $stmt = $DB->query("SHOW TABLES LIKE 'product_images'");
    if ($stmt->fetch()) {
        echo "<p class='success'>✅ Table 'product_images' existe</p>";
        
        $stmt = $DB->query("SELECT COUNT(*) as count FROM product_images");
        $count = $stmt->fetchColumn();
        echo "<p class='info'>📊 Nombre d'images: $count</p>";
    } else {
        echo "<p class='error'>❌ Table 'product_images' n'existe pas</p>";
    }
    
    // Test API get_product
    echo "<h2>🧪 Test API get_product.php</h2>";
    $stmt = $DB->query("SELECT id FROM products LIMIT 1");
    $first_product = $stmt->fetch();
    
    if ($first_product) {
        $test_id = $first_product['id'];
        echo "<p>Test avec ID: $test_id</p>";
        
        // Test avec cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['REQUEST_URI']) . '/get_product.php?id=' . $test_id);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>Code HTTP: $http_code</p>";
        
        if ($http_code == 200) {
            $data = json_decode($response, true);
            if ($data && !isset($data['error'])) {
                echo "<p class='success'>✅ API get_product.php fonctionne</p>";
                echo "<p>Produit: " . htmlspecialchars($data['name']) . "</p>";
            } else {
                echo "<p class='error'>❌ Erreur API: " . htmlspecialchars($data['error'] ?? 'JSON invalide') . "</p>";
            }
        } else {
            echo "<p class='error'>❌ Erreur HTTP: $http_code</p>";
        }
        
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    } else {
        echo "<p class='warning'>⚠️ Aucun produit pour tester l'API</p>";
    }
    
    // Vérifier les dossiers uploads
    echo "<h2>📁 Vérification dossiers uploads</h2>";
    $upload_dir = __DIR__ . '/../uploads';
    $products_dir = $upload_dir . '/products';
    
    if (is_dir($upload_dir)) {
        echo "<p class='success'>✅ Dossier uploads existe</p>";
        if (is_writable($upload_dir)) {
            echo "<p class='success'>✅ Dossier uploads accessible en écriture</p>";
        } else {
            echo "<p class='error'>❌ Dossier uploads non accessible en écriture</p>";
        }
    } else {
        echo "<p class='error'>❌ Dossier uploads n'existe pas</p>";
    }
    
    if (is_dir($products_dir)) {
        echo "<p class='success'>✅ Dossier uploads/products existe</p>";
        $files = scandir($products_dir);
        $file_count = count($files) - 2; // Enlever . et ..
        echo "<p class='info'>📂 Nombre de fichiers: $file_count</p>";
    } else {
        echo "<p class='warning'>⚠️ Dossier uploads/products n'existe pas</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<div>";
echo "<button class='btn btn-primary' onclick='window.close()'>Fermer</button>";
echo "<button class='btn btn-primary' onclick='location.reload()'>Actualiser</button>";
echo "<a href='../admin_panel.php' class='btn btn-primary'>← Retour admin</a>";
echo "</div>";

echo "<script>
function addTestProducts() {
    if (confirm('Ajouter des produits de test ?')) {
        fetch('../add_test_products.php')
            .then(response => response.text())
            .then(data => {
                alert('Produits de test ajoutés !');
                location.reload();
            })
            .catch(error => {
                alert('Erreur: ' + error.message);
            });
    }
}
</script>";

echo "</body></html>";
?>
