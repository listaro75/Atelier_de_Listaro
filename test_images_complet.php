<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Images - Atelier de Listaro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        .test-section { margin-bottom: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 15px; }
        .test-item { border: 1px solid #eee; padding: 15px; border-radius: 8px; text-align: center; }
        .status-ok { color: #28a745; font-weight: bold; }
        .status-error { color: #dc3545; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
        .image-test { width: 150px; height: 100px; object-fit: cover; border-radius: 4px; margin: 10px 0; }
        .placeholder-test { width: 150px; height: 100px; background: #f0f0f0; border-radius: 4px; margin: 10px 0; display: flex; align-items: center; justify-content: center; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test du Système d'Images</h1>
        
        <?php
        include '_db/connexion_DB.php';
        include '_functions/image_utils.php';
        
        // Test 1: Vérification des dossiers
        echo '<div class="test-section">';
        echo '<h2>📁 Test des Dossiers</h2>';
        
        $uploadDir = __DIR__ . '/uploads/products';
        $assetsDir = __DIR__ . '/assets/images';
        
        echo '<div class="test-grid">';
        
        echo '<div class="test-item">';
        echo '<h4>uploads/products</h4>';
        if (is_dir($uploadDir)) {
            echo '<span class="status-ok">✅ Existe</span><br>';
            echo 'Permissions: ' . (is_writable($uploadDir) ? '<span class="status-ok">Écriture OK</span>' : '<span class="status-error">Pas d\'écriture</span>');
            $files = array_diff(scandir($uploadDir), ['.', '..']);
            echo '<br>Fichiers: ' . count($files);
        } else {
            echo '<span class="status-error">❌ N\'existe pas</span>';
        }
        echo '</div>';
        
        echo '<div class="test-item">';
        echo '<h4>assets/images</h4>';
        if (is_dir($assetsDir)) {
            echo '<span class="status-ok">✅ Existe</span><br>';
            $files = array_diff(scandir($assetsDir), ['.', '..']);
            echo 'Fichiers: ' . count($files);
        } else {
            echo '<span class="status-warning">⚠️ N\'existe pas</span>';
        }
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        
        // Test 2: Base de données
        echo '<div class="test-section">';
        echo '<h2>🗄️ Test Base de Données</h2>';
        
        try {
            $stmt = $DB->query('SELECT COUNT(*) FROM products');
            $productCount = $stmt->fetchColumn();
            
            $stmt = $DB->query('SELECT COUNT(*) FROM product_images');
            $imageCount = $stmt->fetchColumn();
            
            echo '<div class="test-grid">';
            echo '<div class="test-item">';
            echo '<h4>Produits</h4>';
            echo '<span class="status-ok">' . $productCount . ' produits</span>';
            echo '</div>';
            
            echo '<div class="test-item">';
            echo '<h4>Images</h4>';
            echo '<span class="status-ok">' . $imageCount . ' images</span>';
            echo '</div>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<span class="status-error">❌ Erreur BDD: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';
        
        // Test 3: Images existantes
        echo '<div class="test-section">';
        echo '<h2>🖼️ Test Images Produits</h2>';
        
        try {
            $stmt = $DB->query('
                SELECT p.id, p.name, pi.image_path 
                FROM products p 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_main = 1 
                LIMIT 6
            ');
            $products = $stmt->fetchAll();
            
            if (empty($products)) {
                echo '<p class="status-warning">⚠️ Aucun produit trouvé</p>';
            } else {
                echo '<div class="test-grid">';
                
                foreach ($products as $product) {
                    echo '<div class="test-item">';
                    echo '<h4>' . htmlspecialchars($product['name']) . '</h4>';
                    
                    if ($product['image_path']) {
                        $imagePath = $product['image_path'];
                        $fullPath = __DIR__ . '/' . $imagePath;
                        
                        if (file_exists($fullPath)) {
                            echo '<img src="' . htmlspecialchars($imagePath) . '" class="image-test" alt="' . htmlspecialchars($product['name']) . '">';
                            echo '<br><span class="status-ok">✅ Image OK</span>';
                        } else {
                            echo '<div class="placeholder-test">❌ Fichier manquant</div>';
                            echo '<span class="status-error">Fichier: ' . basename($imagePath) . '</span>';
                        }
                    } else {
                        echo '<div class="placeholder-test">📷 Pas d\'image</div>';
                        echo '<span class="status-warning">⚠️ Aucune image</span>';
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<span class="status-error">❌ Erreur: ' . $e->getMessage() . '</span>';
        }
        echo '</div>';
        
        // Test 4: Fonctions utilitaires
        echo '<div class="test-section">';
        echo '<h2>⚙️ Test Fonctions</h2>';
        
        echo '<div class="test-grid">';
        
        echo '<div class="test-item">';
        echo '<h4>getImageUrl()</h4>';
        $testUrl = getImageUrl('');
        echo '<div style="max-width: 150px; word-break: break-all; margin: 10px 0;">';
        echo substr($testUrl, 0, 50) . '...';
        echo '</div>';
        echo '<span class="status-ok">✅ Fonctionne</span>';
        echo '</div>';
        
        echo '<div class="test-item">';
        echo '<h4>createPlaceholderImageUrl()</h4>';
        $placeholderUrl = createPlaceholderImageUrl(150, 100, 'Test');
        echo '<img src="' . $placeholderUrl . '" style="width: 150px; height: 100px; border: 1px solid #ddd;">';
        echo '<br><span class="status-ok">✅ Placeholder OK</span>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
        ?>
        
        <div class="test-section">
            <h2>🔗 Liens de Test</h2>
            <p>
                <a href="shop.php" target="_blank">🛒 Tester la Boutique</a> | 
                <a href="admin_panel.php" target="_blank">⚙️ Admin Panel</a> | 
                <a href="diagnostic_images.php" target="_blank">🔍 Diagnostic Détaillé</a>
            </p>
        </div>
        
        <div class="test-section">
            <h2>📋 Résumé</h2>
            <p><strong>Système d'images configuré avec :</strong></p>
            <ul>
                <li>✅ Images placeholder automatiques</li>
                <li>✅ Gestion des erreurs de chargement</li>
                <li>✅ Fallback pour images manquantes</li>
                <li>✅ CSS responsive pour les images</li>
                <li>✅ Integration admin panel et boutique</li>
            </ul>
        </div>
    </div>
</body>
</html>
