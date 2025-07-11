<?php
// Script pour se connecter directement en admin
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Connexion Admin Express</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    
    // Chercher un utilisateur admin existant
    $stmt = $DB->query("SELECT * FROM user WHERE role = 'admin' LIMIT 1");
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "<h2>Utilisateur admin trouvé :</h2>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $admin['id'] . "</li>";
        echo "<li><strong>Pseudo:</strong> " . htmlspecialchars($admin['pseudo']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($admin['mail']) . "</li>";
        echo "<li><strong>Role:</strong> " . $admin['role'] . "</li>";
        echo "</ul>";
        
        // Créer la session admin automatiquement
        $_SESSION['id'] = $admin['id'];
        $_SESSION['pseudo'] = $admin['pseudo'];
        $_SESSION['role'] = $admin['role'];
        $_SESSION['logged'] = true;
        $_SESSION['is_admin'] = true;
        
        echo "<div style='background: #e6ffe6; border: 1px solid green; padding: 15px; margin: 10px 0;'>";
        echo "<h2 style='color: green;'>✅ Session admin créée automatiquement !</h2>";
        echo "<p>Vous êtes maintenant connecté en tant qu'administrateur.</p>";
        echo "</div>";
        
        // Mise à jour de la dernière connexion
        $update = $DB->prepare("UPDATE user SET date_last_conect = NOW() WHERE id = ?");
        $update->execute(array($admin['id']));
        
        echo "<h3>🎯 Accès au panel admin :</h3>";
        echo "<p><a href='admin_panel.php' style='background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 18px; display: inline-block; margin: 10px 0;'>🔧 Accéder au Panel Admin</a></p>";
        
        echo "<h3>📊 Informations de session :</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Clé</th><th>Valeur</th></tr>";
        foreach ($_SESSION as $key => $value) {
            echo "<tr>";
            echo "<td><strong>" . htmlspecialchars($key) . "</strong></td>";
            echo "<td>" . htmlspecialchars($value) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>🔗 Autres liens utiles :</h3>";
        echo "<ul>";
        echo "<li><a href='index.php'>🏠 Accueil du site</a></li>";
        echo "<li><a href='shop.php'>🛒 Boutique</a></li>";
        echo "<li><a href='deconnexion.php'>🚪 Se déconnecter</a></li>";
        echo "</ul>";
        
    } else {
        echo "<h2 style='color: orange;'>⚠️ Aucun administrateur trouvé</h2>";
        echo "<p>Je vais créer un compte administrateur par défaut.</p>";
        
        // Créer un admin par défaut
        $admin_data = [
            'pseudo' => 'admin',
            'nom' => 'Administrateur',
            'prenom' => 'Site',
            'mail' => 'admin@atelierdelistaro.fr',
            'mdp' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin'
        ];
        
        $stmt = $DB->prepare("INSERT INTO user (pseudo, nom, prenom, mail, mdp, role) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$admin_data['pseudo'], $admin_data['nom'], $admin_data['prenom'], $admin_data['mail'], $admin_data['mdp'], $admin_data['role']])) {
            $admin_id = $DB->lastInsertId();
            
            echo "<div style='background: #e6ffe6; border: 1px solid green; padding: 15px; margin: 10px 0;'>";
            echo "<h2 style='color: green;'>✅ Administrateur créé avec succès !</h2>";
            echo "<p><strong>Identifiants créés :</strong></p>";
            echo "<ul>";
            echo "<li>Pseudo : <strong>admin</strong></li>";
            echo "<li>Mot de passe : <strong>admin123</strong></li>";
            echo "<li>Email : <strong>admin@atelierdelistaro.fr</strong></li>";
            echo "</ul>";
            echo "</div>";
            
            // Créer la session automatiquement
            $_SESSION['id'] = $admin_id;
            $_SESSION['pseudo'] = $admin_data['pseudo'];
            $_SESSION['role'] = $admin_data['role'];
            $_SESSION['logged'] = true;
            $_SESSION['is_admin'] = true;
            
            echo "<p style='color: green;'>✅ Session admin créée automatiquement !</p>";
            echo "<p><a href='admin_panel.php' style='background: #007cba; color: white; padding: 15px 25px; text-decoration: none; border-radius: 5px; font-size: 18px;'>🔧 Accéder au Panel Admin</a></p>";
            
        } else {
            echo "<p style='color: red;'>❌ Erreur lors de la création de l'administrateur</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h1 style='color: red;'>❌ Erreur :</h1>";
    echo "<p style='color: red; background: #ffe6e6; padding: 10px; border: 1px solid red;'>";
    echo htmlspecialchars($e->getMessage());
    echo "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    line-height: 1.6;
    background: #f8f9fa;
}

h1, h2, h3 {
    color: #333;
}

table {
    border-collapse: collapse;
    width: 100%;
    margin: 10px 0;
    background: white;
}

th, td {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
}

th {
    background-color: #f2f2f2;
    font-weight: bold;
}

ul {
    margin: 10px 0;
}

li {
    margin: 5px 0;
}

a {
    color: #007cba;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
