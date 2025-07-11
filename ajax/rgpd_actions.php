<?php
/**
 * Actions RGPD - Export, anonymisation, rapports
 * Fichier: ajax/rgpd_actions.php
 */

session_start();
require_once '../_functions/auth.php';
require_once '../_db/connexion_DB.php';

// Vérifier que l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'export_data':
            handleDataExport();
            break;
            
        case 'anonymize_old_data':
            handleDataAnonymization();
            break;
            
        case 'generate_report':
            handleReportGeneration();
            break;
            
        case 'delete_user_data':
            handleUserDataDeletion();
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleDataExport() {
    global $DB;
    
    // Créer le dossier exports s'il n'existe pas
    $exports_dir = '../exports';
    if (!is_dir($exports_dir)) {
        mkdir($exports_dir, 0755, true);
    }
    
    // Collecter toutes les données RGPD
    $export_data = [
        'export_info' => [
            'date' => date('Y-m-d H:i:s'),
            'type' => 'Données RGPD complètes',
            'requested_by' => $_SESSION['user_id']
        ]
    ];
    
    // Consentements des cookies
    $stmt = $DB->query("SELECT 
        DATE(consent_date) as date,
        COUNT(*) as total_consents,
        SUM(consent_given) as accepted_consents,
        AVG(consent_given) * 100 as acceptance_rate
    FROM cookie_consents 
    WHERE consent_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY DATE(consent_date)
    ORDER BY date DESC");
    
    $export_data['consent_statistics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Collectes de données (anonymisées)
    $stmt = $DB->query("SELECT 
        DATE(collection_date) as date,
        COUNT(*) as total_collections,
        COUNT(DISTINCT ip_address) as unique_visitors,
        AVG(CHAR_LENGTH(collected_data)) as avg_data_size
    FROM user_data_collection 
    WHERE collection_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY DATE(collection_date)
    ORDER BY date DESC");
    
    $export_data['collection_statistics'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Types de données collectées
    $stmt = $DB->query("SELECT collected_data FROM user_data_collection 
        WHERE collection_date >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
        LIMIT 1000");
    
    $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $data_types_stats = [
        'essential' => 0,
        'analytics' => 0,
        'preferences' => 0,
        'marketing' => 0
    ];
    
    foreach ($collections as $collection) {
        $data = json_decode($collection['collected_data'], true);
        if ($data) {
            foreach ($data_types_stats as $type => $count) {
                if (isset($data[$type])) {
                    $data_types_stats[$type]++;
                }
            }
        }
    }
    
    $export_data['data_types_statistics'] = $data_types_stats;
    
    // Préférences utilisateurs (anonymisées)
    $stmt = $DB->query("SELECT 
        preferences,
        COUNT(*) as count
    FROM cookie_consents 
    WHERE consent_given = 1 AND preferences != '[]'
    AND consent_date >= DATE_SUB(NOW(), INTERVAL 90 DAY)
    GROUP BY preferences
    ORDER BY count DESC");
    
    $export_data['user_preferences'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Générer le fichier JSON
    $filename = 'rgpd_export_' . date('Y-m-d_H-i-s') . '.json';
    $filepath = $exports_dir . '/' . $filename;
    
    $json_data = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents($filepath, $json_data);
    
    // Log de l'action
    error_log("RGPD Export created by user ID: " . $_SESSION['user_id'] . " - File: " . $filename);
    
    echo json_encode([
        'success' => true,
        'message' => 'Export créé avec succès',
        'download_url' => 'exports/' . $filename,
        'file_size' => formatBytes(filesize($filepath))
    ]);
}

function handleDataAnonymization() {
    global $DB;
    
    $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
    
    try {
        $DB->beginTransaction();
        
        // Anonymiser les IPs dans les consentements
        $stmt = $DB->prepare("UPDATE cookie_consents 
            SET ip_address = CONCAT('anon_', RIGHT(MD5(ip_address), 8)) 
            WHERE consent_date < ? AND ip_address NOT LIKE 'anon_%'");
        $stmt->execute([$cutoff_date]);
        $consent_anonymized = $stmt->rowCount();
        
        // Anonymiser les IPs dans les collectes de données
        $stmt = $DB->prepare("UPDATE user_data_collection 
            SET ip_address = CONCAT('anon_', RIGHT(MD5(ip_address), 8))
            WHERE collection_date < ? AND ip_address NOT LIKE 'anon_%'");
        $stmt->execute([$cutoff_date]);
        $data_anonymized = $stmt->rowCount();
        
        // Supprimer les données personnelles sensibles dans les collectes
        $stmt = $DB->prepare("UPDATE user_data_collection 
            SET collected_data = JSON_REMOVE(
                JSON_REMOVE(collected_data, '$.analytics.user_agent'), 
                '$.marketing'
            )
            WHERE collection_date < ?");
        $stmt->execute([$cutoff_date]);
        
        $DB->commit();
        
        // Log de l'action
        error_log("RGPD Anonymization performed by user ID: " . $_SESSION['user_id'] . " - Consent: $consent_anonymized, Data: $data_anonymized");
        
        echo json_encode([
            'success' => true,
            'message' => "Anonymisation terminée avec succès",
            'details' => "$consent_anonymized consentements et $data_anonymized collectes anonymisés"
        ]);
        
    } catch (Exception $e) {
        $DB->rollBack();
        throw new Exception("Erreur lors de l'anonymisation: " . $e->getMessage());
    }
}

function handleReportGeneration() {
    global $DB;
    
    // Créer le dossier exports s'il n'existe pas
    $exports_dir = '../exports';
    if (!is_dir($exports_dir)) {
        mkdir($exports_dir, 0755, true);
    }
    
    // Collecter les statistiques pour le rapport
    $report_data = generateRgpdStatistics($DB);
    
    // Générer le rapport en format texte
    $report = generateTextReport($report_data);
    
    $filename = 'rapport_rgpd_' . date('Y-m-d_H-i-s') . '.txt';
    $filepath = $exports_dir . '/' . $filename;
    
    file_put_contents($filepath, $report);
    
    // Générer aussi une version HTML
    $html_report = generateHtmlReport($report_data);
    $html_filename = 'rapport_rgpd_' . date('Y-m-d_H-i-s') . '.html';
    $html_filepath = $exports_dir . '/' . $html_filename;
    
    file_put_contents($html_filepath, $html_report);
    
    // Log de l'action
    error_log("RGPD Report generated by user ID: " . $_SESSION['user_id'] . " - Files: " . $filename . ", " . $html_filename);
    
    echo json_encode([
        'success' => true,
        'message' => 'Rapport généré avec succès',
        'report_url' => 'exports/' . $html_filename,
        'text_url' => 'exports/' . $filename
    ]);
}

function handleUserDataDeletion() {
    global $DB;
    
    $ip_address = $_POST['ip_address'] ?? '';
    if (!$ip_address) {
        throw new Exception('Adresse IP requise');
    }
    
    try {
        $DB->beginTransaction();
        
        // Supprimer les consentements
        $stmt = $DB->prepare("DELETE FROM cookie_consents WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        $consents_deleted = $stmt->rowCount();
        
        // Supprimer les collectes de données
        $stmt = $DB->prepare("DELETE FROM user_data_collection WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        $data_deleted = $stmt->rowCount();
        
        $DB->commit();
        
        // Log de l'action
        error_log("RGPD User data deletion by user ID: " . $_SESSION['user_id'] . " - IP: $ip_address, Consents: $consents_deleted, Data: $data_deleted");
        
        echo json_encode([
            'success' => true,
            'message' => 'Données utilisateur supprimées',
            'details' => "$consents_deleted consentements et $data_deleted collectes supprimés pour $ip_address"
        ]);
        
    } catch (Exception $e) {
        $DB->rollBack();
        throw new Exception("Erreur lors de la suppression: " . $e->getMessage());
    }
}

function generateRgpdStatistics($db) {
    // Statistiques des 30 derniers jours
    $stats = [];
    
    // Consentements
    $stmt = $db->query("SELECT 
        COUNT(*) as total_consents,
        SUM(consent_given) as accepted_consents,
        COUNT(*) - SUM(consent_given) as refused_consents,
        AVG(consent_given) * 100 as acceptance_rate
    FROM cookie_consents 
    WHERE consent_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    $stats['consents'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Collectes de données
    $stmt = $db->query("SELECT 
        COUNT(*) as total_collections,
        COUNT(DISTINCT ip_address) as unique_visitors,
        AVG(CHAR_LENGTH(collected_data)) as avg_data_size
    FROM user_data_collection 
    WHERE collection_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    $stats['collections'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Évolution sur 7 jours
    $stmt = $db->query("SELECT 
        DATE(consent_date) as date,
        COUNT(*) as total,
        SUM(consent_given) as accepted
    FROM cookie_consents 
    WHERE consent_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(consent_date)
    ORDER BY date DESC");
    
    $stats['daily_evolution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    return $stats;
}

function generateTextReport($data) {
    $report = "==========================================\n";
    $report .= "         RAPPORT RGPD DÉTAILLÉ\n";
    $report .= "         " . date('d/m/Y à H:i:s') . "\n";
    $report .= "==========================================\n\n";
    
    $report .= "## CONFORMITÉ RGPD\n";
    $report .= "✅ Consentement explicite mis en place\n";
    $report .= "✅ Données collectées avec accord utilisateur\n";
    $report .= "✅ Possibilité de suppression des données\n";
    $report .= "✅ Anonymisation automatique des données anciennes\n\n";
    
    $report .= "## STATISTIQUES DES CONSENTEMENTS (30 derniers jours)\n";
    $report .= "Total des consentements: " . ($data['consents']['total_consents'] ?? 0) . "\n";
    $report .= "Consentements acceptés: " . ($data['consents']['accepted_consents'] ?? 0) . "\n";
    $report .= "Consentements refusés: " . ($data['consents']['refused_consents'] ?? 0) . "\n";
    $report .= "Taux d'acceptation: " . round($data['consents']['acceptance_rate'] ?? 0, 1) . "%\n\n";
    
    $report .= "## COLLECTE DE DONNÉES (30 derniers jours)\n";
    $report .= "Total des collectes: " . ($data['collections']['total_collections'] ?? 0) . "\n";
    $report .= "Visiteurs uniques: " . ($data['collections']['unique_visitors'] ?? 0) . "\n";
    $report .= "Taille moyenne des données: " . round($data['collections']['avg_data_size'] ?? 0) . " caractères\n\n";
    
    if (!empty($data['daily_evolution'])) {
        $report .= "## ÉVOLUTION SUR 7 JOURS\n";
        foreach ($data['daily_evolution'] as $day) {
            $report .= date('d/m', strtotime($day['date'])) . " : " . $day['accepted'] . "/" . $day['total'] . " consentements\n";
        }
        $report .= "\n";
    }
    
    $report .= "## RECOMMANDATIONS\n";
    $report .= "- Continuer la surveillance des taux de consentement\n";
    $report .= "- Effectuer des anonymisations régulières\n";
    $report .= "- Maintenir la transparence sur la collecte de données\n";
    $report .= "- Réviser la politique de confidentialité périodiquement\n\n";
    
    $report .= "Rapport généré automatiquement par le système RGPD d'Atelier de Listaro\n";
    
    return $report;
}

function generateHtmlReport($data) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="fr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rapport RGPD - <?php echo date('d/m/Y'); ?></title>
        <style>
            body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
            .header { text-align: center; background: #667eea; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
            .section { background: #f8f9fa; padding: 15px; border-radius: 6px; margin-bottom: 15px; }
            .stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; }
            .stat-card { background: white; padding: 15px; border-radius: 6px; text-align: center; }
            .stat-number { font-size: 2em; font-weight: bold; color: #333; }
            .compliance { background: #d4edda; border-left: 4px solid #27ae60; }
            .evolution-table { width: 100%; border-collapse: collapse; }
            .evolution-table th, .evolution-table td { padding: 8px; border-bottom: 1px solid #dee2e6; }
            .evolution-table th { background: #e9ecef; }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Rapport RGPD Détaillé</h1>
            <p>Généré le <?php echo date('d/m/Y à H:i:s'); ?></p>
        </div>

        <div class="section compliance">
            <h2>✅ Statut de Conformité RGPD</h2>
            <ul>
                <li>Consentement explicite mis en place</li>
                <li>Données collectées avec accord utilisateur</li>
                <li>Possibilité de suppression des données</li>
                <li>Anonymisation automatique des données anciennes</li>
            </ul>
        </div>

        <div class="section">
            <h2>📊 Statistiques des Consentements (30 derniers jours)</h2>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($data['consents']['total_consents'] ?? 0); ?></div>
                    <div>Total consentements</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($data['consents']['accepted_consents'] ?? 0); ?></div>
                    <div>Acceptés</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($data['consents']['acceptance_rate'] ?? 0, 1); ?>%</div>
                    <div>Taux d'acceptation</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>💾 Collecte de Données (30 derniers jours)</h2>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($data['collections']['total_collections'] ?? 0); ?></div>
                    <div>Total collectes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($data['collections']['unique_visitors'] ?? 0); ?></div>
                    <div>Visiteurs uniques</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo round($data['collections']['avg_data_size'] ?? 0); ?></div>
                    <div>Caractères/collecte</div>
                </div>
            </div>
        </div>

        <?php if (!empty($data['daily_evolution'])): ?>
        <div class="section">
            <h2>📈 Évolution sur 7 jours</h2>
            <table class="evolution-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Total consentements</th>
                        <th>Acceptés</th>
                        <th>Taux</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($data['daily_evolution'] as $day): ?>
                    <tr>
                        <td><?php echo date('d/m/Y', strtotime($day['date'])); ?></td>
                        <td><?php echo $day['total']; ?></td>
                        <td><?php echo $day['accepted']; ?></td>
                        <td><?php echo round(($day['accepted'] / max(1, $day['total'])) * 100, 1); ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>💡 Recommandations</h2>
            <ul>
                <li>Continuer la surveillance des taux de consentement</li>
                <li>Effectuer des anonymisations régulières</li>
                <li>Maintenir la transparence sur la collecte de données</li>
                <li>Réviser la politique de confidentialité périodiquement</li>
            </ul>
        </div>

        <footer style="text-align: center; margin-top: 30px; color: #666; font-size: 0.9em;">
            Rapport généré automatiquement par le système RGPD d'Atelier de Listaro
        </footer>
    </body>
    </html>
    <?php
    return ob_get_clean();
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}
?>
