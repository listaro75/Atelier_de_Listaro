<?php
// Script de réparation automatique des sections admin
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Réparation automatique Admin Panel</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    
    $fixes_applied = [];
    $errors_found = [];
    
    // Fix 1: Vérifier et corriger les chemins d'inclusion dans les sections
    echo "<h2>Fix 1: Correction des chemins d'inclusion</h2>";
    
    $admin_sections = ['products', 'prestations', 'orders', 'users'];
    
    foreach ($admin_sections as $section) {
        $file_path = __DIR__ . "/admin_sections/{$section}.php";
        
        if (file_exists($file_path)) {
            $content = file_get_contents($file_path);
            $original_content = $content;
            
            // Corrections communes
            $corrections = [
                // Corriger les chemins relatifs
                "include_once('_db/connexion_DB.php')" => "include_once(__DIR__ . '/../_db/connexion_DB.php')",
                "include_once('_functions/auth.php')" => "include_once(__DIR__ . '/../_functions/auth.php')",
                "require_once('_db/connexion_DB.php')" => "require_once(__DIR__ . '/../_db/connexion_DB.php')",
                "require_once('_functions/auth.php')" => "require_once(__DIR__ . '/../_functions/auth.php')",
                
                // Corriger les fetch vides
                "fetch('')" => "fetch('admin_sections/{$section}.php')",
                "fetch(\"\")" => "fetch('admin_sections/{$section}.php')",
            ];
            
            foreach ($corrections as $search => $replace) {
                if (strpos($content, $search) !== false) {
                    $content = str_replace($search, $replace, $content);
                    $fixes_applied[] = "Corrigé '{$search}' dans {$section}.php";
                }
            }
            
            // Sauvegarder si des changements ont été faits
            if ($content !== $original_content) {
                file_put_contents($file_path, $content);
                echo "<p style='color: green;'>✅ {$section}.php corrigé</p>";
            } else {
                echo "<p>✅ {$section}.php déjà correct</p>";
            }
        } else {
            $errors_found[] = "Fichier {$section}.php manquant";
            echo "<p style='color: red;'>❌ {$section}.php manquant</p>";
        }
    }
    
    // Fix 2: Vérifier la structure des tables
    echo "<h2>Fix 2: Vérification structure des tables</h2>";
    
    $required_tables = ['user', 'products', 'prestations', 'orders', 'product_images', 'prestation_images'];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $DB->query("SELECT COUNT(*) FROM {$table}");
            $count = $stmt->fetchColumn();
            echo "<p>✅ Table {$table}: {$count} enregistrements</p>";
        } catch (Exception $e) {
            $errors_found[] = "Table {$table} inaccessible: " . $e->getMessage();
            echo "<p style='color: red;'>❌ Table {$table}: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Fix 3: Corriger le fichier admin_panel.php principal
    echo "<h2>Fix 3: Correction du fichier admin_panel.php</h2>";
    
    $admin_panel_path = __DIR__ . "/admin_panel.php";
    if (file_exists($admin_panel_path)) {
        $content = file_get_contents($admin_panel_path);
        $original_content = $content;
        
        // Corrections pour admin_panel.php
        $corrections = [
            // S'assurer que les sections se chargent correctement
            "loadSectionContent(section)" => "loadSectionContent(section, true)",
        ];
        
        // Vérifier la fonction loadSectionContent
        if (strpos($content, 'function loadSectionContent') !== false) {
            echo "<p>✅ Fonction loadSectionContent trouvée</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Fonction loadSectionContent non trouvée</p>";
        }
        
        // Vérifier les scripts
        if (strpos($content, 'fetch(') !== false) {
            echo "<p>✅ Appels fetch trouvés</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Aucun appel fetch trouvé</p>";
        }
        
    } else {
        $errors_found[] = "Fichier admin_panel.php manquant";
    }
    
    // Fix 4: Test des fonctions d'authentification
    echo "<h2>Fix 4: Test des fonctions d'authentification</h2>";
    
    require_once(__DIR__ . '/_functions/auth.php');
    
    if (function_exists('is_admin')) {
        echo "<p>✅ Fonction is_admin() disponible</p>";
    } else {
        $errors_found[] = "Fonction is_admin() manquante";
    }
    
    if (function_exists('is_logged')) {
        echo "<p>✅ Fonction is_logged() disponible</p>";
    } else {
        $errors_found[] = "Fonction is_logged() manquante";
    }
    
    // Fix 5: Créer un fichier de test des sections
    echo "<h2>Fix 5: Création fichier de test des sections</h2>";
    
    $test_sections_content = '<?php
// Test direct des sections admin
session_start();
require_once(__DIR__ . "/_db/connexion_DB.php");
require_once(__DIR__ . "/_functions/auth.php");

// Simuler session admin
$_SESSION["logged"] = true;
$_SESSION["id"] = 1;
$_SESSION["role"] = "admin";

$section = $_GET["section"] ?? "products";
$section_file = __DIR__ . "/admin_sections/{$section}.php";

if (file_exists($section_file)) {
    echo "<h1>Test section: {$section}</h1>";
    include($section_file);
} else {
    echo "<h1>Erreur: Section {$section} non trouvée</h1>";
}
?>';
    
    file_put_contents(__DIR__ . '/test_sections_direct.php', $test_sections_content);
    echo "<p>✅ Fichier test_sections_direct.php créé</p>";
    
    // Résumé des réparations
    echo "<h2>📋 Résumé des réparations</h2>";
    
    if (count($fixes_applied) > 0) {
        echo "<h3 style='color: green;'>✅ Corrections appliquées :</h3>";
        echo "<ul>";
        foreach ($fixes_applied as $fix) {
            echo "<li>" . htmlspecialchars($fix) . "</li>";
        }
        echo "</ul>";
    }
    
    if (count($errors_found) > 0) {
        echo "<h3 style='color: red;'>❌ Erreurs détectées :</h3>";
        echo "<ul>";
        foreach ($errors_found as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul>";
    }
    
    // Liens de test
    echo "<h2>🔗 Liens de test</h2>";
    echo "<p><a href='check_admin.php' style='background: #007cba; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>1. Vérifier admin</a></p>";
    echo "<p><a href='test_sections_direct.php?section=products' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>2. Test section produits</a></p>";
    echo "<p><a href='admin_panel.php' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>3. Admin Panel</a></p>";
    
    echo "<h1 style='color: green;'>🎉 Réparations terminées !</h1>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur lors de la réparation :</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>';
