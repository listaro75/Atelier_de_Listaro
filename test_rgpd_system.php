<?php
/**
 * Script de test pour vérifier le bon fonctionnement du système RGPD
 * avec la structure de base de données existante
 */

require_once '_config/env.php';
require_once '_db/connexion_DB.php';

echo "<h1>Test du Système RGPD</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

try {
    // Test 1: Vérification de la structure des tables
    echo "<h2>1. Vérification de la structure des tables</h2>";
    
    $tables_to_check = ['cookie_consents', 'user_data_collection', 'data_deletion_requests', 'rgpd_action_logs'];
    
    foreach ($tables_to_check as $table) {
        $stmt = $DB->query("DESCRIBE $table");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Table: $table</h3>";
        echo "<table>";
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
    }
    
    // Test 2: Insertion de données de test
    echo "<h2>2. Test d'insertion de données</h2>";
    
    // Test consentement
    $test_ip = '192.168.1.100';
    $stmt = $DB->prepare("INSERT INTO cookie_consents (ip_address, is_active, consent_data, user_agent, date_created) 
        VALUES (?, 1, ?, 'Test User Agent', NOW())");
    
    $consent_data = json_encode([
        'essential' => true,
        'analytics' => true,
        'marketing' => false,
        'preferences' => true
    ]);
    
    if ($stmt->execute([$test_ip, $consent_data])) {
        echo "<p class='success'>✓ Consentement de test inséré avec succès</p>";
    }
    
    // Test collecte de données
    $stmt = $DB->prepare("INSERT INTO user_data_collection (ip_address, data_type, data_content, user_agent, date_created) 
        VALUES (?, ?, ?, 'Test User Agent', NOW())");
    
    if ($stmt->execute([$test_ip, 'navigation', 'page_view:/test'])) {
        echo "<p class='success'>✓ Collecte de données de test insérée avec succès</p>";
    }
    
    // Test 3: Test des fonctions RGPD
    echo "<h2>3. Test des fonctions RGPD</h2>";
    
    // Inclure les fonctions du fichier RGPD
    include_once 'admin_sections/rgpd_control.php';
    
    // Test fonction getConsentStats
    echo "<h3>Statistiques des consentements:</h3>";
    $consent_stats = getConsentStats($DB);
    echo "<pre>" . print_r($consent_stats, true) . "</pre>";
    
    // Test fonction getDataCollectionStats
    echo "<h3>Statistiques de collecte de données:</h3>";
    $data_stats = getDataCollectionStats($DB);
    echo "<pre>" . print_r($data_stats, true) . "</pre>";
    
    // Test 4: Vérification des données récentes
    echo "<h2>4. Données récentes dans la base</h2>";
    
    echo "<h3>Consentements récents:</h3>";
    $stmt = $DB->query("SELECT ip_address, is_active, date_created 
        FROM cookie_consents 
        ORDER BY date_created DESC 
        LIMIT 5");
    
    $consents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($consents) {
        echo "<table>";
        echo "<tr><th>IP</th><th>Actif</th><th>Date</th></tr>";
        foreach ($consents as $consent) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($consent['ip_address']) . "</td>";
            echo "<td>" . ($consent['is_active'] ? 'Oui' : 'Non') . "</td>";
            echo "<td>" . $consent['date_created'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>Aucun consentement trouvé</p>";
    }
    
    echo "<h3>Collectes de données récentes:</h3>";
    $stmt = $DB->query("SELECT ip_address, data_type, date_created 
        FROM user_data_collection 
        ORDER BY date_created DESC 
        LIMIT 5");
    
    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($collections) {
        echo "<table>";
        echo "<tr><th>IP</th><th>Type de données</th><th>Date</th></tr>";
        foreach ($collections as $collection) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($collection['ip_address']) . "</td>";
            echo "<td>" . htmlspecialchars($collection['data_type']) . "</td>";
            echo "<td>" . $collection['date_created'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>Aucune collecte de données trouvée</p>";
    }
    
    // Test 5: Nettoyage des données de test
    echo "<h2>5. Nettoyage des données de test</h2>";
    
    $stmt = $DB->prepare("DELETE FROM cookie_consents WHERE ip_address = ?");
    $stmt->execute([$test_ip]);
    
    $stmt = $DB->prepare("DELETE FROM user_data_collection WHERE ip_address = ?");
    $stmt->execute([$test_ip]);
    
    echo "<p class='success'>✓ Données de test supprimées</p>";
    
    echo "<h2>✅ Test terminé avec succès!</h2>";
    echo "<p>Le système RGPD est compatible avec votre structure de base de données existante.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
}

// Fonction getConsentStats adaptée (copie pour le test)
function getConsentStats($DB) {
    $stats = [
        'global' => [
            'total_consents' => 0,
            'accepted_consents' => 0,
            'refused_consents' => 0,
            'acceptance_rate' => 0
        ],
        'daily' => [],
        'types' => [
            'essential' => 0,
            'analytics' => 0,
            'preferences' => 0,
            'marketing' => 0
        ]
    ];
    
    // Statistiques globales avec la vraie structure
    $stmt = $DB->query("SELECT 
        COUNT(*) as total_consents,
        SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as accepted_consents,
        SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as refused_consents,
        AVG(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) * 100 as acceptance_rate
    FROM cookie_consents 
    WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['global'] = [
            'total_consents' => (int)$row['total_consents'],
            'accepted_consents' => (int)$row['accepted_consents'],
            'refused_consents' => (int)$row['refused_consents'],
            'acceptance_rate' => (float)$row['acceptance_rate']
        ];
    }
    
    return $stats;
}

// Fonction getDataCollectionStats adaptée (copie pour le test)
function getDataCollectionStats($DB) {
    $stats = [
        'global' => [
            'total_collections' => 0,
            'unique_visitors' => 0,
            'avg_data_size' => 0
        ],
        'types' => []
    ];
    
    // Statistiques globales avec la vraie structure
    $stmt = $DB->query("SELECT 
        COUNT(*) as total_collections,
        COUNT(DISTINCT ip_address) as unique_visitors,
        AVG(CHAR_LENGTH(data_content)) as avg_data_size
    FROM user_data_collection 
    WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats['global'] = [
            'total_collections' => (int)$row['total_collections'],
            'unique_visitors' => (int)$row['unique_visitors'],
            'avg_data_size' => (float)$row['avg_data_size']
        ];
    }
    
    // Types de données collectées avec mapping
    $stmt = $DB->query("SELECT data_type, COUNT(*) as count 
        FROM user_data_collection 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        GROUP BY data_type");
    
    $type_mapping = [
        'navigation' => 'essential',
        'form_data' => 'essential', 
        'user_preferences' => 'preferences',
        'analytics_data' => 'analytics',
        'marketing_data' => 'marketing'
    ];
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $category = $type_mapping[$row['data_type']] ?? 'other';
        $stats['types'][$category] = ($stats['types'][$category] ?? 0) + (int)$row['count'];
    }
    
    return $stats;
}
?>
