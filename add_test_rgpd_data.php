<?php
/**
 * Script pour ajouter des données de test RGPD réalistes
 */

require_once '_config/env.php';
require_once '_db/connexion_DB.php';

echo "<h1>Ajout de Données de Test RGPD</h1>";
echo "<style>
body { font-family: Arial, sans-serif; margin: 20px; }
.success { color: green; }
.error { color: red; }
.info { color: blue; }
</style>";

try {
    // Générer plusieurs sessions de test avec des données variées
    $test_data = [
        [
            'session_id' => 'demo_session_001',
            'ip' => '192.168.1.101',
            'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            'consent_data' => json_encode([
                'essential' => true,
                'analytics' => true,
                'marketing' => false,
                'preferences' => true
            ]),
            'collections' => [
                ['type' => 'navigation', 'content' => 'page_view:/shop', 'page' => '/shop', 'consent' => 1],
                ['type' => 'analytics_data', 'content' => 'time_on_page:45', 'page' => '/shop', 'consent' => 1],
                ['type' => 'user_preferences', 'content' => 'theme:dark', 'page' => '/profile', 'consent' => 1],
            ]
        ],
        [
            'session_id' => 'demo_session_002',
            'ip' => '192.168.1.102',
            'user_agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
            'consent_data' => json_encode([
                'essential' => true,
                'analytics' => false,
                'marketing' => false,
                'preferences' => false
            ]),
            'collections' => [
                ['type' => 'navigation', 'content' => 'page_view:/portfolio', 'page' => '/portfolio', 'consent' => 1],
                ['type' => 'form_data', 'content' => 'contact_form_submitted', 'page' => '/contact', 'consent' => 1],
            ]
        ],
        [
            'session_id' => 'demo_session_003',
            'ip' => '192.168.1.103',
            'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 15_0 like Mac OS X) AppleWebKit/605.1.15',
            'consent_data' => json_encode([
                'essential' => true,
                'analytics' => true,
                'marketing' => true,
                'preferences' => true
            ]),
            'collections' => [
                ['type' => 'navigation', 'content' => 'page_view:/prestations', 'page' => '/prestations', 'consent' => 1],
                ['type' => 'analytics_data', 'content' => 'scroll_depth:80%', 'page' => '/prestations', 'consent' => 1],
                ['type' => 'marketing_data', 'content' => 'ad_click:banner_1', 'page' => '/prestations', 'consent' => 1],
                ['type' => 'user_preferences', 'content' => 'language:fr', 'page' => '/prestations', 'consent' => 1],
            ]
        ]
    ];
    
    $total_consents = 0;
    $total_collections = 0;
    
    foreach ($test_data as $session) {
        // Insérer le consentement
        $stmt = $DB->prepare("INSERT INTO cookie_consents 
            (session_id, consent_data, ip_address, user_agent, is_active, date_created) 
            VALUES (?, ?, ?, ?, 1, DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 7) DAY))");
        
        if ($stmt->execute([
            $session['session_id'], 
            $session['consent_data'], 
            $session['ip'], 
            $session['user_agent']
        ])) {
            $total_consents++;
            echo "<p class='success'>✓ Consentement ajouté pour session {$session['session_id']}</p>";
        }
        
        // Insérer les collectes de données
        foreach ($session['collections'] as $collection) {
            $stmt = $DB->prepare("INSERT INTO user_data_collection 
                (session_id, data_type, data_content, ip_address, user_agent, page_url, consent_given, date_created) 
                VALUES (?, ?, ?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 7) DAY))");
            
            if ($stmt->execute([
                $session['session_id'],
                $collection['type'],
                $collection['content'],
                $session['ip'],
                $session['user_agent'],
                $collection['page'],
                $collection['consent']
            ])) {
                $total_collections++;
            }
        }
    }
    
    echo "<p class='success'>✅ Données de test ajoutées avec succès!</p>";
    echo "<p class='info'>$total_consents consentements et $total_collections collectes de données ajoutés</p>";
    
    // Ajouter quelques demandes de suppression de test
    $deletion_requests = [
        ['email' => 'test1@example.com', 'type' => 'complete_deletion'],
        ['email' => 'test2@example.com', 'type' => 'data_export']
    ];
    
    foreach ($deletion_requests as $request) {
        $stmt = $DB->prepare("INSERT INTO data_deletion_requests 
            (email, request_type, request_data, status, date_created) 
            VALUES (?, ?, ?, 'pending', DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 3) DAY))");
        
        $request_data = json_encode([
            'reason' => 'Test de suppression RGPD',
            'user_ip' => '192.168.1.' . rand(100, 200)
        ]);
        
        if ($stmt->execute([$request['email'], $request['type'], $request_data])) {
            echo "<p class='success'>✓ Demande de suppression ajoutée pour {$request['email']}</p>";
        }
    }
    
    // Ajouter quelques logs d'actions RGPD
    $actions = [
        ['type' => 'export', 'target' => '192.168.1.101', 'details' => 'Export automatique de données'],
        ['type' => 'anonymization', 'target' => '192.168.1.200', 'details' => 'Anonymisation données anciennes']
    ];
    
    foreach ($actions as $action) {
        $stmt = $DB->prepare("INSERT INTO rgpd_action_logs 
            (action_type, target_ip, action_details, action_date) 
            VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 2) DAY))");
        
        if ($stmt->execute([$action['type'], $action['target'], $action['details']])) {
            echo "<p class='success'>✓ Log d'action RGPD ajouté ({$action['type']})</p>";
        }
    }
    
    echo "<hr>";
    echo "<h2>Statistiques actuelles</h2>";
    
    // Afficher quelques statistiques
    $stmt = $DB->query("SELECT COUNT(*) as count FROM cookie_consents WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $consent_count = $stmt->fetchColumn();
    echo "<p>Consentements (30 derniers jours): <strong>$consent_count</strong></p>";
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user_data_collection WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $collection_count = $stmt->fetchColumn();
    echo "<p>Collectes de données (30 derniers jours): <strong>$collection_count</strong></p>";
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM data_deletion_requests WHERE status = 'pending'");
    $pending_count = $stmt->fetchColumn();
    echo "<p>Demandes de suppression en attente: <strong>$pending_count</strong></p>";
    
    echo "<hr>";
    echo "<h2>Actions disponibles</h2>";
    echo "<p><a href='admin_sections/rgpd_control.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🛡️ Accéder au Centre RGPD</a></p>";
    echo "<p><a href='test_rgpd_system.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🧪 Lancer les Tests RGPD</a></p>";
    
    echo "<br><p class='info'>Les données de test peuvent être supprimées via le centre de contrôle RGPD.</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
}
?>
