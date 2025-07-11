<?php
// Vérification et création d'un utilisateur admin
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔐 Vérification et création administrateur</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    
    // Vérifier la structure de la table user
    echo "<h2>Structure de la table user :</h2>";
    $stmt = $DB->query("DESCRIBE user");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier les utilisateurs existants
    echo "<h2>Utilisateurs existants :</h2>";
    $stmt = $DB->query("SELECT id, pseudo, email, role FROM user");
    $users = $stmt->fetchAll();
    
    if (count($users) > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Pseudo</th><th>Email</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td>" . htmlspecialchars($user['pseudo']) . "</td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun utilisateur trouvé.</p>";
    }
    
    // Vérifier s'il y a un admin
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user WHERE role = 'admin' OR role = '3'");
    $admin_count = $stmt->fetch();
    
    echo "<h2>Nombre d'administrateurs : " . $admin_count['count'] . "</h2>";
    
    if ($admin_count['count'] == 0) {
        echo "<h2 style='color: orange;'>🚨 Aucun administrateur trouvé - Création d'un admin</h2>";
        
        // Créer un utilisateur admin
        $admin_pseudo = 'admin';
        $admin_email = 'admin@atelierdelistaro.fr';
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
        $admin_role = 'admin';
        
        $stmt = $DB->prepare("INSERT INTO user (pseudo, email, password, role) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$admin_pseudo, $admin_email, $admin_password, $admin_role])) {
            echo "<p style='color: green;'>✅ Administrateur créé avec succès !</p>";
            echo "<div style='background: #e6ffe6; border: 1px solid green; padding: 10px; margin: 10px 0;'>";
            echo "<strong>Identifiants administrateur :</strong><br>";
            echo "Pseudo : <strong>admin</strong><br>";
            echo "Mot de passe : <strong>admin123</strong><br>";
            echo "Email : <strong>admin@atelierdelistaro.fr</strong>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Erreur lors de la création de l'administrateur</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Au moins un administrateur existe déjà</p>";
    }
    
    // Test de connexion admin
    echo "<h2>Test de connexion admin</h2>";
    $stmt = $DB->prepare("SELECT id, pseudo, role FROM user WHERE role = 'admin' OR role = '3' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<p>✅ Admin trouvé : " . htmlspecialchars($admin['pseudo']) . " (ID: " . $admin['id'] . ", Role: " . htmlspecialchars($admin['role']) . ")</p>";
        
        // Créer un lien de connexion automatique
        echo "<h3>🔗 Liens de test :</h3>";
        echo "<a href='connexion.php' style='background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Se connecter manuellement</a><br><br>";
        
        // Créer un script de connexion automatique pour test
        echo "<form method='POST' action='test_auto_login.php' style='margin: 10px 0;'>";
        echo "<input type='hidden' name='admin_id' value='" . $admin['id'] . "'>";
        echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer;'>Test connexion automatique</button>";
        echo "</form>";
    }
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur :</h1>";
    echo "<p style='color: red;'>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
body { font-family: Arial, sans-serif; margin: 20px; }
</style>
