<?php
/**
 * Vérification et création de la colonne newsletter si nécessaire
 * Atelier de Listaro
 */

include_once('_db/connexion_DB.php');

try {
    echo "<h2>🔍 Vérification de la base de données pour la newsletter</h2>";
    
    // Vérifier si la table user existe
    $stmt = $DB->query("SHOW TABLES LIKE 'user'");
    if ($stmt->rowCount() === 0) {
        echo "<div style='color: red;'>❌ La table 'user' n'existe pas!</div>";
        exit;
    }
    echo "<div style='color: green;'>✅ Table 'user' trouvée</div>";
    
    // Vérifier si la colonne newsletter existe
    $stmt = $DB->query("SHOW COLUMNS FROM user LIKE 'newsletter'");
    if ($stmt->rowCount() === 0) {
        echo "<div style='color: orange;'>⚠️ Colonne 'newsletter' manquante dans la table 'user'</div>";
        echo "<p>Création de la colonne newsletter...</p>";
        
        // Créer la colonne newsletter
        $DB->exec("ALTER TABLE user ADD COLUMN newsletter TINYINT(1) DEFAULT 0");
        echo "<div style='color: green;'>✅ Colonne 'newsletter' créée avec succès</div>";
    } else {
        echo "<div style='color: green;'>✅ Colonne 'newsletter' trouvée</div>";
    }
    
    // Afficher la structure de la table user
    echo "<h3>📋 Structure de la table 'user' :</h3>";
    $stmt = $DB->query("DESCRIBE user");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th style='padding: 10px;'>Colonne</th>";
    echo "<th style='padding: 10px;'>Type</th>";
    echo "<th style='padding: 10px;'>Null</th>";
    echo "<th style='padding: 10px;'>Défaut</th>";
    echo "</tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td style='padding: 8px;'>" . htmlspecialchars($column['Default'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Compter les utilisateurs avec newsletter
    $stmt = $DB->query("SELECT COUNT(*) as total FROM user");
    $total_users = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as newsletter_users FROM user WHERE newsletter = 1");
    $newsletter_users = $stmt->fetchColumn();
    
    echo "<h3>📊 Statistiques utilisateurs :</h3>";
    echo "<ul>";
    echo "<li><strong>Total utilisateurs :</strong> $total_users</li>";
    echo "<li><strong>Abonnés newsletter :</strong> $newsletter_users</li>";
    echo "<li><strong>Non abonnés :</strong> " . ($total_users - $newsletter_users) . "</li>";
    echo "</ul>";
    
    // Afficher quelques utilisateurs avec leur statut newsletter
    echo "<h3>👥 Exemples d'utilisateurs :</h3>";
    $stmt = $DB->query("SELECT id, pseudo, mail, newsletter FROM user LIMIT 5");
    $sample_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($sample_users) > 0) {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Pseudo</th>";
        echo "<th style='padding: 10px;'>Email</th>";
        echo "<th style='padding: 10px;'>Newsletter</th>";
        echo "</tr>";
        
        foreach ($sample_users as $user) {
            $newsletter_status = $user['newsletter'] ? '✅ Oui' : '❌ Non';
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['pseudo']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($user['mail'] ?? 'Non défini') . "</td>";
            echo "<td style='padding: 8px;'>$newsletter_status</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Aucun utilisateur trouvé.</p>";
    }
    
    echo "<h3>🎉 Diagnostic terminé !</h3>";
    echo "<p><strong>✅ La base de données est maintenant prête pour la newsletter</strong></p>";
    echo "<p><a href='admin_panel.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Accéder au Panel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Erreur : " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
