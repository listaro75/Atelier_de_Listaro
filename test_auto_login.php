<?php
// Script de connexion automatique pour test admin
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Test de connexion automatique admin</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_id'])) {
        $admin_id = intval($_POST['admin_id']);
        
        // Récupérer les infos admin
        $stmt = $DB->prepare("SELECT * FROM user WHERE id = ? AND (role = 'admin' OR role = '3')");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch();
        
        if ($admin) {
            // Créer la session admin
            $_SESSION['logged'] = true;
            $_SESSION['id'] = $admin['id'];
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['pseudo'] = $admin['pseudo'];
            $_SESSION['mail'] = $admin['mail'];
            $_SESSION['role'] = $admin['role'];
            $_SESSION['is_admin'] = true;
            
            echo "<div style='background: #e6ffe6; border: 1px solid green; padding: 15px; margin: 10px 0;'>";
            echo "<h2 style='color: green;'>✅ Connexion admin réussie !</h2>";
            echo "<p><strong>Utilisateur :</strong> " . htmlspecialchars($admin['pseudo']) . "</p>";
            echo "<p><strong>Email :</strong> " . htmlspecialchars($admin['mail']) . "</p>";
            echo "<p><strong>Role :</strong> " . htmlspecialchars($admin['role']) . "</p>";
            echo "<p><strong>Session ID :</strong> " . session_id() . "</p>";
            echo "</div>";
            
            echo "<h3>🎯 Accès admin panel :</h3>";
            echo "<a href='admin_panel.php' style='background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 16px;'>Accéder au Panel Admin</a>";
            
            echo "<h3>📊 État de la session :</h3>";
            echo "<ul>";
            foreach ($_SESSION as $key => $value) {
                echo "<li><strong>" . htmlspecialchars($key) . ":</strong> " . htmlspecialchars($value) . "</li>";
            }
            echo "</ul>";
            
        } else {
            echo "<p style='color: red;'>❌ Utilisateur admin non trouvé</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ Aucune donnée POST reçue</p>";
        echo "<a href='check_admin.php'>← Retour à la vérification admin</a>";
    }
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur :</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
h1, h2, h3 { color: #333; }
ul { margin: 10px 0; }
li { margin: 5px 0; }
</style>
