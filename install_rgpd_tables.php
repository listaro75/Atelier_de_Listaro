<?php
/**
 * Installation automatique des tables RGPD
 * Fichier: install_rgpd_tables.php
 */

require_once '_db/connexion_DB.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Installation Tables RGPD</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .info { background: #cce5ff; color: #004085; padding: 10px; border-radius: 5px; margin: 10px 0; }
        .step { background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; margin: 5px; }
        .btn:hover { background: #0056b3; }
    </style>
</head>
<body>";

echo "<h1>🛠️ Installation des Tables RGPD</h1>";

try {
    // Étape 1: Vérifier les tables existantes
    echo "<div class='step'>";
    echo "<h2>Étape 1: Vérification des tables existantes</h2>";
    
    $stmt = $DB->query("SHOW TABLES");
    $existing_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $rgpd_tables = [
        'cookie_consents' => 'Consentements des cookies',
        'user_data_collection' => 'Collecte de données utilisateur',
        'data_deletion_requests' => 'Demandes de suppression',
        'rgpd_action_logs' => 'Logs des actions RGPD'
    ];
    
    echo "<table>";
    echo "<tr><th>Table</th><th>Description</th><th>Statut</th></tr>";
    
    $tables_to_create = [];
    foreach ($rgpd_tables as $table => $description) {
        $exists = in_array($table, $existing_tables);
        echo "<tr>";
        echo "<td>$table</td>";
        echo "<td>$description</td>";
        if ($exists) {
            echo "<td class='success'>✅ Existe</td>";
        } else {
            echo "<td class='error'>❌ Manquante</td>";
            $tables_to_create[] = $table;
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // Étape 2: Créer les tables manquantes
    if (!empty($tables_to_create)) {
        echo "<div class='step'>";
        echo "<h2>Étape 2: Création des tables manquantes</h2>";
        
        // SQL pour créer les tables
        $table_definitions = [
            'cookie_consents' => "CREATE TABLE `cookie_consents` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `user_agent` text,
                `consent_given` tinyint(1) NOT NULL DEFAULT '0',
                `preferences` text,
                `consent_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ip_date` (`ip_address`, `consent_date`),
                KEY `idx_consent_date` (`consent_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            'user_data_collection' => "CREATE TABLE `user_data_collection` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `collected_data` longtext,
                `collection_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_ip_date` (`ip_address`, `collection_date`),
                KEY `idx_collection_date` (`collection_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            'data_deletion_requests' => "CREATE TABLE `data_deletion_requests` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `ip_address` varchar(45) NOT NULL,
                `email` varchar(255),
                `request_reason` text,
                `status` enum('pending','approved','rejected','processed') NOT NULL DEFAULT 'pending',
                `admin_notes` text,
                `request_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `processed_date` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_status` (`status`),
                KEY `idx_ip` (`ip_address`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            
            'rgpd_action_logs' => "CREATE TABLE `rgpd_action_logs` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `admin_user_id` int(11),
                `action_type` varchar(50) NOT NULL,
                `target_ip` varchar(45),
                `action_details` text,
                `action_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_admin_user` (`admin_user_id`),
                KEY `idx_action_type` (`action_type`),
                KEY `idx_action_date` (`action_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        ];
        
        foreach ($tables_to_create as $table) {
            if (isset($table_definitions[$table])) {
                try {
                    $DB->exec($table_definitions[$table]);
                    echo "<div class='success'>✅ Table '$table' créée avec succès</div>";
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erreur lors de la création de '$table': " . $e->getMessage() . "</div>";
                }
            }
        }
        echo "</div>";
    } else {
        echo "<div class='info'>ℹ️ Toutes les tables RGPD existent déjà</div>";
    }
    
    // Étape 3: Vérifier les données
    echo "<div class='step'>";
    echo "<h2>Étape 3: Vérification des données</h2>";
    
    echo "<table>";
    echo "<tr><th>Table</th><th>Nombre d'enregistrements</th><th>Action</th></tr>";
    
    foreach ($rgpd_tables as $table => $description) {
        try {
            $stmt = $DB->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td>$count</td>";
            if ($count == 0) {
                echo "<td><a href='?add_test_data=$table' class='btn'>Ajouter données de test</a></td>";
            } else {
                echo "<td>Données présentes</td>";
            }
            echo "</tr>";
        } catch (Exception $e) {
            echo "<tr>";
            echo "<td>$table</td>";
            echo "<td colspan='2' class='error'>Erreur: " . $e->getMessage() . "</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    echo "</div>";
    
    // Ajouter des données de test si demandé
    if (isset($_GET['add_test_data'])) {
        $table = $_GET['add_test_data'];
        echo "<div class='step'>";
        echo "<h2>Ajout de données de test pour '$table'</h2>";
        
        try {
            if ($table === 'cookie_consents') {
                $test_data = [
                    ['192.168.1.100', 'Mozilla/5.0 (Windows)', 1, '{"analytics":true,"preferences":true,"marketing":false}'],
                    ['192.168.1.101', 'Mozilla/5.0 (iPhone)', 0, '{}'],
                    ['192.168.1.102', 'Mozilla/5.0 (Macintosh)', 1, '{"analytics":true,"preferences":false,"marketing":true}']
                ];
                
                $stmt = $DB->prepare("INSERT INTO cookie_consents (ip_address, user_agent, consent_given, preferences, consent_date) VALUES (?, ?, ?, ?, NOW() - INTERVAL ? DAY)");
                
                foreach ($test_data as $index => $data) {
                    $stmt->execute([$data[0], $data[1], $data[2], $data[3], $index + 1]);
                }
                
                echo "<div class='success'>✅ " . count($test_data) . " consentements de test ajoutés</div>";
                
            } elseif ($table === 'user_data_collection') {
                $test_data = [
                    ['192.168.1.100', '{"essential":{"ip_address":"192.168.1.100","page_url":"/shop"},"analytics":{"user_agent":"Mozilla/5.0","referer":"google.com"}}'],
                    ['192.168.1.101', '{"essential":{"ip_address":"192.168.1.101","page_url":"/portfolio"}}'],
                    ['192.168.1.102', '{"essential":{"ip_address":"192.168.1.102","page_url":"/contact"},"preferences":{"theme":"dark","language":"fr"}}']
                ];
                
                $stmt = $DB->prepare("INSERT INTO user_data_collection (ip_address, collected_data, collection_date) VALUES (?, ?, NOW() - INTERVAL ? DAY)");
                
                foreach ($test_data as $index => $data) {
                    $stmt->execute([$data[0], $data[1], $index + 1]);
                }
                
                echo "<div class='success'>✅ " . count($test_data) . " collectes de données de test ajoutées</div>";
            }
            
            echo "<div class='info'>🔄 <a href='install_rgpd_tables.php'>Actualiser la page</a></div>";
            
        } catch (Exception $e) {
            echo "<div class='error'>❌ Erreur lors de l'ajout des données: " . $e->getMessage() . "</div>";
        }
        echo "</div>";
    }
    
    // Étape 4: Instructions finales
    echo "<div class='step'>";
    echo "<h2>Étape 4: Finalisation</h2>";
    echo "<div class='success'>";
    echo "<h3>✅ Installation terminée avec succès !</h3>";
    echo "<p>Vous pouvez maintenant utiliser le centre de contrôle RGPD dans votre panel d'administration.</p>";
    echo "<ul>";
    echo "<li><a href='admin_panel.php'>🔗 Accéder au panel d'administration</a></li>";
    echo "<li><a href='check_db_structure.php'>🔍 Vérifier la structure des tables</a></li>";
    echo "<li><a href='test_upload.php'>🧪 Page de test</a></li>";
    echo "</ul>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<h3>❌ Erreur lors de l'installation</h3>";
    echo "<p>Erreur: " . $e->getMessage() . "</p>";
    echo "<p>Vérifiez votre connexion à la base de données et les permissions.</p>";
    echo "</div>";
}

echo "</body></html>";
?>
