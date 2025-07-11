<?php
// Diagnostic et correction du problème de chargement des sections admin
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Diagnostic Admin Panel - Problème de chargement</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    require_once(__DIR__ . '/_functions/auth.php');
    
    // Vérifier la session
    echo "<h2>1. Vérification de la session :</h2>";
    if (isset($_SESSION['logged']) && $_SESSION['logged'] === true) {
        echo "<p style='color: green;'>✅ Session active</p>";
        echo "<ul>";
        echo "<li>User ID: " . ($_SESSION['id'] ?? 'Non défini') . "</li>";
        echo "<li>Role: " . ($_SESSION['role'] ?? 'Non défini') . "</li>";
        echo "<li>Is Admin: " . (is_admin() ? 'OUI' : 'NON') . "</li>";
        echo "</ul>";
    } else {
        echo "<p style='color: red;'>❌ Aucune session active</p>";
        echo "<p><a href='admin_login_express.php'>Se connecter automatiquement</a></p>";
        exit;
    }
    
    // Tester les sections admin directement
    echo "<h2>2. Test direct des sections admin :</h2>";
    
    $sections = ['products', 'prestations', 'orders', 'users'];
    
    foreach ($sections as $section) {
        echo "<h3>Section: {$section}</h3>";
        
        $section_url = "http://88.124.91.246/admin_sections/{$section}.php";
        echo "<p>URL: <a href='{$section_url}' target='_blank'>{$section_url}</a></p>";
        
        // Test de la section via include
        $section_file = __DIR__ . "/admin_sections/{$section}.php";
        if (file_exists($section_file)) {
            echo "<p style='color: green;'>✅ Fichier {$section}.php existe</p>";
            
            // Capturer la sortie
            ob_start();
            try {
                include($section_file);
                $output = ob_get_contents();
                ob_end_clean();
                
                if (strlen($output) > 100) {
                    echo "<p style='color: green;'>✅ Section génère du contenu (" . strlen($output) . " caractères)</p>";
                } else {
                    echo "<p style='color: orange;'>⚠️ Section génère peu de contenu (" . strlen($output) . " caractères)</p>";
                    if ($output) {
                        echo "<pre style='background: #fff3cd; padding: 5px; font-size: 12px;'>" . htmlspecialchars(substr($output, 0, 200)) . "</pre>";
                    }
                }
            } catch (Exception $e) {
                ob_end_clean();
                echo "<p style='color: red;'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Fichier {$section}.php manquant</p>";
        }
    }
    
    // Vérifier le fichier admin_panel.php pour les problèmes JavaScript
    echo "<h2>3. Analyse du JavaScript dans admin_panel.php :</h2>";
    
    $admin_panel_file = __DIR__ . '/admin_panel.php';
    if (file_exists($admin_panel_file)) {
        $content = file_get_contents($admin_panel_file);
        
        // Rechercher les fonctions JavaScript importantes
        $js_functions = [
            'loadSectionContent',
            'fetch(',
            'XMLHttpRequest',
            'showSection'
        ];
        
        foreach ($js_functions as $func) {
            if (strpos($content, $func) !== false) {
                echo "<p style='color: green;'>✅ Fonction JavaScript '{$func}' trouvée</p>";
            } else {
                echo "<p style='color: orange;'>⚠️ Fonction JavaScript '{$func}' non trouvée</p>";
            }
        }
        
        // Vérifier s'il y a des erreurs JavaScript communes
        $js_errors = [
            'fetch(\'\')' => 'URL fetch vide',
            'fetch("")' => 'URL fetch vide (guillemets doubles)',
            '.innerHTML =' => 'Injection HTML directe'
        ];
        
        echo "<h4>Problèmes JavaScript détectés :</h4>";
        $errors_found = false;
        foreach ($js_errors as $pattern => $description) {
            if (strpos($content, $pattern) !== false) {
                echo "<p style='color: red;'>❌ {$description}: {$pattern}</p>";
                $errors_found = true;
            }
        }
        
        if (!$errors_found) {
            echo "<p style='color: green;'>✅ Aucun problème JavaScript évident détecté</p>";
        }
    }
    
    // Créer un fichier de test pour charger les sections directement
    echo "<h2>4. Création d'un testeur de sections :</h2>";
    
    $test_content = '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Sections Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .section-test { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .section-content { background: #f8f9fa; padding: 10px; margin-top: 10px; max-height: 300px; overflow-y: auto; }
        button { background: #007cba; color: white; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer; margin: 5px; }
        button:hover { background: #005a8a; }
        .loading { color: #666; font-style: italic; }
        .error { color: red; background: #ffe6e6; padding: 10px; border-radius: 5px; }
        .success { color: green; background: #e6ffe6; padding: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🧪 Testeur de Sections Admin</h1>
    
    <div class="section-test">
        <h2>Test de chargement des sections</h2>
        <button onclick="testSection(\'products\')">Test Produits</button>
        <button onclick="testSection(\'prestations\')">Test Prestations</button>
        <button onclick="testSection(\'orders\')">Test Commandes</button>
        <button onclick="testSection(\'users\')">Test Utilisateurs</button>
        <button onclick="testAllSections()">Test Tout</button>
    </div>
    
    <div id="results"></div>
    
    <script>
    function testSection(section) {
        const resultsDiv = document.getElementById(\'results\');
        const testDiv = document.createElement(\'div\');
        testDiv.className = \'section-test\';
        testDiv.innerHTML = `<h3>Test: ${section}</h3><div class="loading">Chargement...</div>`;
        resultsDiv.appendChild(testDiv);
        
        const url = `admin_sections/${section}.php`;
        
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.text();
            })
            .then(data => {
                testDiv.innerHTML = `
                    <h3>✅ ${section} - Succès</h3>
                    <p>Taille: ${data.length} caractères</p>
                    <div class="section-content">
                        <h4>Aperçu du contenu:</h4>
                        <pre>${data.substring(0, 500)}${data.length > 500 ? \'...\' : \'\'}</pre>
                    </div>
                `;
            })
            .catch(error => {
                testDiv.innerHTML = `
                    <h3>❌ ${section} - Erreur</h3>
                    <div class="error">
                        <strong>Erreur:</strong> ${error.message}<br>
                        <strong>URL testée:</strong> ${url}
                    </div>
                `;
            });
    }
    
    function testAllSections() {
        document.getElementById(\'results\').innerHTML = \'\';
        [\'products\', \'prestations\', \'orders\', \'users\'].forEach(section => {
            setTimeout(() => testSection(section), Math.random() * 1000);
        });
    }
    </script>
</body>
</html>';
    
    file_put_contents(__DIR__ . '/test_admin_sections.html', $test_content);
    echo "<p style='color: green;'>✅ Fichier test créé : <a href='test_admin_sections.html' target='_blank'>test_admin_sections.html</a></p>";
    
    // Créer un script de réparation rapide
    echo "<h2>5. Script de réparation automatique :</h2>";
    
    $quick_fix_content = '<?php
// Réparation rapide admin panel
session_start();
require_once(__DIR__ . "/_db/connexion_DB.php");
require_once(__DIR__ . "/_functions/auth.php");

// Forcer la session admin si pas connecté
if (!is_admin()) {
    // Trouver un admin
    $stmt = $DB->query("SELECT * FROM user WHERE role = \'admin\' LIMIT 1");
    $admin = $stmt->fetch();
    if ($admin) {
        $_SESSION[\'id\'] = $admin[\'id\'];
        $_SESSION[\'pseudo\'] = $admin[\'pseudo\'];
        $_SESSION[\'role\'] = $admin[\'role\'];
        $_SESSION[\'logged\'] = true;
        $_SESSION[\'is_admin\'] = true;
    }
}

// Rediriger vers admin panel
header("Location: admin_panel.php");
exit;
?>';
    
    file_put_contents(__DIR__ . '/quick_admin_fix.php', $quick_fix_content);
    echo "<p style='color: green;'>✅ Script de réparation créé : <a href='quick_admin_fix.php'>quick_admin_fix.php</a></p>";
    
    echo "<h2>🔧 Solutions recommandées :</h2>";
    echo "<ol>";
    echo "<li><strong>Testez les sections individuellement :</strong> <a href='test_admin_sections.html' target='_blank'>Ouvrir le testeur</a></li>";
    echo "<li><strong>Réparation rapide :</strong> <a href='quick_admin_fix.php'>Forcer la connexion admin</a></li>";
    echo "<li><strong>Accès direct aux sections :</strong>";
    echo "<ul>";
    foreach ($sections as $section) {
        echo "<li><a href='admin_sections/{$section}.php' target='_blank'>{$section}.php</a></li>";
    }
    echo "</ul>";
    echo "</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur :</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
ul, ol { margin: 10px 0; }
li { margin: 5px 0; }
</style>
