<?php
// Test structure de la table prestations
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Structure de la table prestations</h1>";

try {
    require_once(__DIR__ . '/_db/connexion_DB.php');
    
    // Vérifier la structure de la table prestations
    echo "<h2>Structure de la table prestations :</h2>";
    $stmt = $DB->query("DESCRIBE prestations");
    $columns = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Colonne</th><th>Type</th><th>Null</th><th>Clé</th><th>Défaut</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($col['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Vérifier quelques prestations
    echo "<h2>Exemple de prestations :</h2>";
    $stmt = $DB->query("SELECT * FROM prestations LIMIT 3");
    $prestations = $stmt->fetchAll();
    
    if (count($prestations) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        $first_row = true;
        foreach ($prestations as $prestation) {
            if ($first_row) {
                echo "<tr>";
                foreach (array_keys($prestation) as $key) {
                    if (!is_numeric($key)) {
                        echo "<th>" . htmlspecialchars($key) . "</th>";
                    }
                }
                echo "</tr>";
                $first_row = false;
            }
            echo "<tr>";
            foreach ($prestation as $key => $value) {
                if (!is_numeric($key)) {
                    echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
                }
            }
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erreur : " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>

<style>
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
