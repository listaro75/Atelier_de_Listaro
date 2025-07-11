<?php
// Diagnostic détaillé des sections admin
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic des sections admin</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    require_once(__DIR__ . '/_functions/auth.php');
    
    // Simuler une session admin pour les tests
    session_start();
    $_SESSION['logged'] = true;
    $_SESSION['id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['is_admin'] = true;
    
    echo "<div style='background: #e6f3ff; padding: 10px; margin: 10px 0; border: 1px solid #007cba;'>";
    echo "ℹ️ Session admin simulée pour les tests";
    echo "</div>";
    
    $sections = [
        'products' => 'Produits',
        'prestations' => 'Prestations', 
        'orders' => 'Commandes',
        'users' => 'Utilisateurs',
        'settings' => 'Paramètres'
    ];
    
    foreach ($sections as $section => $title) {
        echo "<h2>📋 Test section: {$title}</h2>";
        
        $section_file = __DIR__ . "/admin_sections/{$section}.php";
        
        if (!file_exists($section_file)) {
            echo "<p style='color: red;'>❌ Fichier {$section}.php non trouvé</p>";
            continue;
        }
        
        echo "<p>✅ Fichier {$section}.php trouvé</p>";
        
        // Test de chargement de la section
        echo "<h3>Test de chargement :</h3>";
        
        // Capturer la sortie
        ob_start();
        try {
            // Simuler une requête GET pour la section
            $_GET['section'] = $section;
            
            // Include avec gestion d'erreur
            include($section_file);
            
            $output = ob_get_contents();
            ob_end_clean();
            
            if (strlen($output) > 100) {
                echo "<p style='color: green;'>✅ Section {$section} chargée avec succès (" . strlen($output) . " caractères générés)</p>";
                
                // Vérifier s'il y a des erreurs PHP dans la sortie
                if (strpos($output, 'Fatal error') !== false || strpos($output, 'Parse error') !== false) {
                    echo "<p style='color: red;'>❌ Erreurs PHP détectées dans la sortie</p>";
                    echo "<pre style='background: #ffe6e6; padding: 10px; border: 1px solid red;'>";
                    echo htmlspecialchars(substr($output, 0, 500)) . "...";
                    echo "</pre>";
                } else {
                    echo "<p style='color: green;'>✅ Aucune erreur PHP visible</p>";
                }
            } else {
                echo "<p style='color: orange;'>⚠️ Section {$section} génère peu de contenu (" . strlen($output) . " caractères)</p>";
                if (strlen($output) > 0) {
                    echo "<pre style='background: #fff3cd; padding: 10px; border: 1px solid orange;'>";
                    echo htmlspecialchars($output);
                    echo "</pre>";
                }
            }
            
        } catch (Exception $e) {
            ob_end_clean();
            echo "<p style='color: red;'>❌ Erreur lors du chargement : " . htmlspecialchars($e->getMessage()) . "</p>";
        }
        
        // Test des requêtes spécifiques à chaque section
        echo "<h3>Test des requêtes spécifiques :</h3>";
        
        switch ($section) {
            case 'products':
                try {
                    $stmt = $DB->query("SELECT COUNT(*) as count FROM products");
                    $count = $stmt->fetchColumn();
                    echo "<p>✅ Requête produits OK ({$count} produits)</p>";
                    
                    $stmt = $DB->query("SELECT * FROM products LIMIT 1");
                    $product = $stmt->fetch();
                    if ($product) {
                        echo "<p>✅ Structure produit OK (colonnes: " . implode(', ', array_keys($product)) . ")</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erreur requête produits: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                break;
                
            case 'prestations':
                try {
                    $stmt = $DB->query("SELECT COUNT(*) as count FROM prestations");
                    $count = $stmt->fetchColumn();
                    echo "<p>✅ Requête prestations OK ({$count} prestations)</p>";
                    
                    $stmt = $DB->query("SELECT * FROM prestations LIMIT 1");
                    $prestation = $stmt->fetch();
                    if ($prestation) {
                        echo "<p>✅ Structure prestation OK (colonnes: " . implode(', ', array_keys($prestation)) . ")</p>";
                    }
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erreur requête prestations: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                break;
                
            case 'orders':
                try {
                    $stmt = $DB->query("SELECT COUNT(*) as count FROM orders");
                    $count = $stmt->fetchColumn();
                    echo "<p>✅ Requête commandes OK ({$count} commandes)</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erreur requête commandes: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                break;
                
            case 'users':
                try {
                    $stmt = $DB->query("SELECT COUNT(*) as count FROM user");
                    $count = $stmt->fetchColumn();
                    echo "<p>✅ Requête utilisateurs OK ({$count} utilisateurs)</p>";
                } catch (Exception $e) {
                    echo "<p style='color: red;'>❌ Erreur requête utilisateurs: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
                break;
        }
        
        echo "<hr style='margin: 20px 0;'>";
    }
    
    echo "<h1 style='color: green;'>🎉 Diagnostic terminé</h1>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur générale :</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
pre { font-size: 12px; max-height: 200px; overflow-y: auto; }
</style>
