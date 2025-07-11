<?php
// Script pour vérifier la structure des tables existantes
require_once '_db/connexion_DB.php';

echo "<h1>🔍 Vérification de la Structure des Tables</h1>";

// Lister toutes les tables
try {
    $stmt = $DB->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h2>Tables existantes :</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Vérifier si les tables RGPD existent
    $rgpd_tables = ['cookie_consents', 'user_data_collection', 'data_deletion_requests', 'rgpd_action_logs'];
    
    echo "<h2>Tables RGPD :</h2>";
    foreach ($rgpd_tables as $table) {
        if (in_array($table, $tables)) {
            echo "<h3>✅ Table '$table' existe - Structure :</h3>";
            $stmt = $DB->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th></tr>";
            foreach ($columns as $column) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>❌ Table '$table' n'existe pas</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>

<p><a href="admin_panel.php">← Retour au panel</a></p>
