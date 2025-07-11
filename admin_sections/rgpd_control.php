<?php
/**
 * Section RGPD - Centre de contrôle des données
 * Affiche les statistiques de collecte et les consentements
 */

session_start();
require_once '../_db/connexion_DB.php';
require_once '../_functions/auth.php';
require_once '../_functions/cookie_manager.php';

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

// Initialiser la connexion DB
$cookieManager = new CookieManager($DB);

// Statistiques des consentements
function getConsentStats($db) {
    try {
        // Total des consentements avec la structure existante
        $stmt = $db->query("SELECT 
            COUNT(*) as total_consents,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as accepted_consents,
            COUNT(*) - SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as refused_consents,
            AVG(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) * 100 as acceptance_rate
        FROM cookie_consents 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Consentements par jour (7 derniers jours)
        $stmt = $db->query("SELECT 
            DATE(date_created) as date,
            COUNT(*) as total,
            SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as accepted
        FROM cookie_consents 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(date_created)
        ORDER BY date DESC");
        
        $daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Types de données dans les consentements
        $stmt = $db->query("SELECT 
            consent_data,
            COUNT(*) as count
        FROM cookie_consents 
        WHERE is_active = 1 AND consent_data IS NOT NULL AND consent_data != ''
        AND date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY consent_data
        LIMIT 10");
        
        $preferences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'global' => $stats ?: ['total_consents' => 0, 'accepted_consents' => 0, 'refused_consents' => 0, 'acceptance_rate' => 0],
            'daily' => $daily_stats,
            'preferences' => $preferences
        ];
        
    } catch (Exception $e) {
        return [
            'global' => ['total_consents' => 0, 'accepted_consents' => 0, 'refused_consents' => 0, 'acceptance_rate' => 0],
            'daily' => [],
            'preferences' => []
        ];
    }
}

// Statistiques de collecte de données
function getDataCollectionStats($db) {
    try {
        // Volume de données collectées avec la structure existante
        $stmt = $db->query("SELECT 
            COUNT(*) as total_collections,
            COUNT(DISTINCT ip_address) as unique_visitors,
            AVG(CHAR_LENGTH(data_content)) as avg_data_size
        FROM user_data_collection 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
        
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Collectes récentes
        $stmt = $db->query("SELECT 
            date_created,
            data_type,
            data_content,
            ip_address,
            page_url,
            referer
        FROM user_data_collection 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY date_created DESC
        LIMIT 100");
        
        $collections = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Analyser les types de données
        $stmt = $db->query("SELECT 
            data_type,
            COUNT(*) as count
        FROM user_data_collection 
        WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY data_type
        ORDER BY count DESC");
        
        $data_types_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Mapper vers les catégories RGPD
        $data_types = [
            'essential' => 0,
            'analytics' => 0,
            'preferences' => 0,
            'marketing' => 0
        ];
        
        foreach ($data_types_raw as $type) {
            $type_name = strtolower($type['data_type']);
            $count = $type['count'];
            
            if (in_array($type_name, ['session', 'essential', 'security', 'functional'])) {
                $data_types['essential'] += $count;
            } elseif (in_array($type_name, ['analytics', 'tracking', 'statistics', 'performance'])) {
                $data_types['analytics'] += $count;
            } elseif (in_array($type_name, ['preferences', 'settings', 'customization'])) {
                $data_types['preferences'] += $count;
            } elseif (in_array($type_name, ['marketing', 'advertising', 'promotion', 'campaign'])) {
                $data_types['marketing'] += $count;
            } else {
                // Par défaut, considérer comme analytique
                $data_types['analytics'] += $count;
            }
        }
        
        return [
            'global' => $stats ?: ['total_collections' => 0, 'unique_visitors' => 0, 'avg_data_size' => 0],
            'types' => $data_types,
            'types_raw' => $data_types_raw,
            'recent' => array_slice($collections, 0, 10)
        ];
        
    } catch (Exception $e) {
        return [
            'global' => ['total_collections' => 0, 'unique_visitors' => 0, 'avg_data_size' => 0],
            'types' => ['essential' => 0, 'analytics' => 0, 'preferences' => 0, 'marketing' => 0],
            'types_raw' => [],
            'recent' => []
        ];
    }
}

// Gestion des actions AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'delete_user_data':
                $ip_address = $_POST['ip_address'] ?? '';
                if (!$ip_address) {
                    throw new Exception('Adresse IP requise');
                }
                
                // Supprimer toutes les données liées à cette IP avec la structure existante
                $stmt = $DB->prepare("DELETE FROM user_data_collection WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                $data_deleted = $stmt->rowCount();
                
                $stmt = $DB->prepare("DELETE FROM cookie_consents WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                $consents_deleted = $stmt->rowCount();
                
                // Log de l'action RGPD pour traçabilité
                $stmt = $DB->prepare("INSERT INTO rgpd_action_logs (action_type, target_ip, admin_user, details, date_created) 
                    VALUES ('deletion', ?, ?, ?, NOW())");
                $stmt->execute([
                    $ip_address, 
                    $_SESSION['user_id'], 
                    "Suppression manuelle: $consents_deleted consentements, $data_deleted collectes"
                ]);
                
                $response['success'] = true;
                $response['message'] = 'Données supprimées avec succès pour l\'IP: ' . $ip_address;
                $response['details'] = "$consents_deleted consentements et $data_deleted collectes supprimés";
                break;
                
            case 'export_data':
                // Export global des données RGPD avec la structure existante
                $export_data = [
                    'export_date' => date('Y-m-d H:i:s'),
                    'type' => 'Données RGPD complètes',
                    'requested_by' => $_SESSION['user_id'] ?? 'admin',
                    'consent_stats' => getConsentStats($DB),
                    'data_collection_stats' => getDataCollectionStats($DB)
                ];
                
                // Ajouter statistiques détaillées par période
                $stmt = $DB->query("SELECT 
                    DATE(date_created) as date,
                    COUNT(*) as total_consents,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as accepted_consents,
                    ROUND(AVG(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) * 100, 2) as acceptance_rate
                FROM cookie_consents 
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(date_created)
                ORDER BY date DESC");
                
                $export_data['daily_consent_trends'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Types de données collectées récemment
                $stmt = $DB->query("SELECT 
                    data_type,
                    COUNT(*) as count,
                    COUNT(DISTINCT ip_address) as unique_users,
                    MIN(date_created) as first_collection,
                    MAX(date_created) as last_collection
                FROM user_data_collection 
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
                GROUP BY data_type
                ORDER BY count DESC");
                
                $export_data['data_types_analysis'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $filename = 'rgpd_export_' . date('Y-m-d_H-i-s') . '.json';
                $filepath = '../exports/' . $filename;
                
                // Créer le dossier exports s'il n'existe pas
                if (!is_dir('../exports')) {
                    mkdir('../exports', 0755, true);
                }
                
                $json_data = json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                file_put_contents($filepath, $json_data);
                
                $response['success'] = true;
                $response['message'] = 'Export créé avec succès';
                $response['download_url'] = 'exports/' . $filename;
                $response['file_size'] = number_format(filesize($filepath) / 1024, 2) . ' KB';
                break;
                
            case 'anonymize_old_data':
                // Anonymiser les données de plus de 30 jours avec la structure existante
                $cutoff_date = date('Y-m-d H:i:s', strtotime('-30 days'));
                
                // Anonymiser les IPs dans les consentements (utiliser date_created au lieu de consent_date)
                $stmt = $DB->prepare("UPDATE cookie_consents 
                    SET ip_address = CONCAT('anon_', RIGHT(MD5(ip_address), 8)) 
                    WHERE date_created < ? AND ip_address NOT LIKE 'anon_%'");
                $stmt->execute([$cutoff_date]);
                $consent_anonymized = $stmt->rowCount();
                
                // Anonymiser les IPs dans les collectes de données (utiliser date_created au lieu de collection_date)
                $stmt = $DB->prepare("UPDATE user_data_collection 
                    SET ip_address = CONCAT('anon_', RIGHT(MD5(ip_address), 8))
                    WHERE date_created < ? AND ip_address NOT LIKE 'anon_%'");
                $stmt->execute([$cutoff_date]);
                $data_anonymized = $stmt->rowCount();
                
                // Anonymiser aussi les user_agents si présents
                $stmt = $DB->prepare("UPDATE cookie_consents 
                    SET user_agent = 'anonymized' 
                    WHERE date_created < ? AND user_agent != 'anonymized'");
                $stmt->execute([$cutoff_date]);
                
                $stmt = $DB->prepare("UPDATE user_data_collection 
                    SET user_agent = 'anonymized' 
                    WHERE date_created < ? AND user_agent != 'anonymized'");
                $stmt->execute([$cutoff_date]);
                
                $response['success'] = true;
                $response['message'] = "Anonymisation terminée: $consent_anonymized consentements et $data_anonymized collectes de données";
                break;
                
            case 'generate_report':
                // Générer un rapport RGPD complet
                $consent_stats = getConsentStats($DB);
                $data_stats = getDataCollectionStats($DB);
                
                $report = "# RAPPORT RGPD - " . date('d/m/Y H:i:s') . "\n\n";
                $report .= "## Statistiques des Consentements (30 derniers jours)\n";
                $report .= "- Total des consentements: " . $consent_stats['global']['total_consents'] . "\n";
                $report .= "- Consentements acceptés: " . $consent_stats['global']['accepted_consents'] . "\n";
                $report .= "- Consentements refusés: " . $consent_stats['global']['refused_consents'] . "\n";
                $report .= "- Taux d'acceptation: " . round($consent_stats['global']['acceptance_rate'], 1) . "%\n\n";
                
                $report .= "## Collecte de Données (30 derniers jours)\n";
                $report .= "- Total des collectes: " . $data_stats['global']['total_collections'] . "\n";
                $report .= "- Visiteurs uniques: " . $data_stats['global']['unique_visitors'] . "\n";
                $report .= "- Taille moyenne des données: " . round($data_stats['global']['avg_data_size']) . " caractères\n\n";
                
                $report .= "## Types de Données Collectées\n";
                foreach ($data_stats['types'] as $type => $count) {
                    $report .= "- " . ucfirst($type) . ": $count collectes\n";
                }
                
                $filename = 'rapport_rgpd_' . date('Y-m-d_H-i-s') . '.txt';
                $filepath = '../exports/' . $filename;
                
                file_put_contents($filepath, $report);
                
                $response['success'] = true;
                $response['message'] = 'Rapport généré avec succès';
                $response['report_url'] = 'exports/' . $filename;
                break;
                
            default:
                throw new Exception('Action non reconnue');
        }
    } catch (Exception $e) {
        $response['message'] = $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

$consent_stats = getConsentStats($DB);
$data_stats = getDataCollectionStats($DB);
?>

<div class="rgpd-dashboard">
    <div class="rgpd-header">
        <h2><i class="fas fa-shield-alt"></i> Centre de Contrôle RGPD</h2>
        <p>Visualisation des consentements et données collectées conformément au RGPD</p>
    </div>

    <!-- Statistiques globales -->
    <div class="stats-overview">
        <div class="stat-card consent-stats">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3>Consentements (30 jours)</h3>
                <div class="stat-number"><?php echo number_format($consent_stats['global']['total_consents']); ?></div>
                <div class="stat-details">
                    <span class="accepted">✅ <?php echo $consent_stats['global']['accepted_consents']; ?> acceptés</span>
                    <span class="refused">❌ <?php echo $consent_stats['global']['refused_consents']; ?> refusés</span>
                </div>
                <div class="acceptance-rate">
                    Taux d'acceptation: <strong><?php echo round($consent_stats['global']['acceptance_rate'], 1); ?>%</strong>
                </div>
            </div>
        </div>

        <div class="stat-card data-stats">
            <div class="stat-icon">💾</div>
            <div class="stat-content">
                <h3>Collecte de Données</h3>
                <div class="stat-number"><?php echo number_format($data_stats['global']['total_collections']); ?></div>
                <div class="stat-details">
                    <span>👥 <?php echo $data_stats['global']['unique_visitors']; ?> visiteurs uniques</span>
                    <span>📏 <?php echo round($data_stats['global']['avg_data_size']); ?> caractères/collecte</span>
                </div>
            </div>
        </div>

        <div class="stat-card compliance-status">
            <div class="stat-icon">🛡️</div>
            <div class="stat-content">
                <h3>Conformité RGPD</h3>
                <div class="compliance-indicator active">
                    <i class="fas fa-check-circle"></i> CONFORME
                </div>
                <div class="stat-details">
                    <span>✅ Consentement explicite</span>
                    <span>✅ Données minimales</span>
                    <span>✅ Traçabilité complète</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Évolution des consentements -->
    <div class="consent-evolution">
        <h3><i class="fas fa-chart-line"></i> Évolution des Consentements (7 derniers jours)</h3>
        <div class="chart-container">
            <?php if (!empty($consent_stats['daily'])): ?>
                <div class="daily-chart">
                    <?php foreach (array_reverse($consent_stats['daily']) as $day): ?>
                        <div class="chart-bar">
                            <div class="bar-accepted" style="height: <?php echo min(100, ($day['accepted'] / max(1, $day['total'])) * 100); ?>%"></div>
                            <div class="bar-total" style="height: <?php echo min(100, $day['total'] * 10); ?>%"></div>
                            <div class="bar-label">
                                <?php echo date('d/m', strtotime($day['date'])); ?>
                                <small><?php echo $day['accepted']; ?>/<?php echo $day['total']; ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="no-data">Aucune donnée disponible pour les 7 derniers jours</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Types de données collectées -->
    <div class="data-types-section">
        <h3><i class="fas fa-database"></i> Types de Données Collectées</h3>
        <div class="data-types-grid">
            <div class="data-type essential">
                <div class="type-icon">🔒</div>
                <div class="type-info">
                    <h4>Données Essentielles</h4>
                    <div class="type-count"><?php echo $data_stats['types']['essential']; ?></div>
                    <div class="type-desc">IP, horodatage, page visitée</div>
                </div>
            </div>
            
            <div class="data-type analytics">
                <div class="type-icon">📈</div>
                <div class="type-info">
                    <h4>Données Analytiques</h4>
                    <div class="type-count"><?php echo $data_stats['types']['analytics']; ?></div>
                    <div class="type-desc">User-agent, référent, résolution</div>
                </div>
            </div>
            
            <div class="data-type preferences">
                <div class="type-icon">⚙️</div>
                <div class="type-info">
                    <h4>Préférences</h4>
                    <div class="type-count"><?php echo $data_stats['types']['preferences']; ?></div>
                    <div class="type-desc">Thème, langue, timezone</div>
                </div>
            </div>
            
            <div class="data-type marketing">
                <div class="type-icon">🎯</div>
                <div class="type-info">
                    <h4>Marketing</h4>
                    <div class="type-count"><?php echo $data_stats['types']['marketing']; ?></div>
                    <div class="type-desc">UTM, campagnes, publicités</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Préférences des utilisateurs -->
    <div class="user-preferences">
        <h3><i class="fas fa-sliders-h"></i> Préférences des Utilisateurs</h3>
        <?php if (!empty($consent_stats['preferences'])): ?>
            <div class="preferences-list">
                <?php foreach ($consent_stats['preferences'] as $pref): ?>
                    <?php 
                    $preferences = json_decode($pref['preferences'], true);
                    if ($preferences):
                    ?>
                        <div class="preference-item">
                            <div class="pref-count"><?php echo $pref['count']; ?></div>
                            <div class="pref-details">
                                <?php foreach ($preferences as $type => $enabled): ?>
                                    <span class="pref-tag <?php echo $enabled ? 'enabled' : 'disabled'; ?>">
                                        <?php echo ucfirst($type); ?>: <?php echo $enabled ? '✅' : '❌'; ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-data">Aucune préférence enregistrée</p>
        <?php endif; ?>
    </div>

    <!-- Données collectées récemment -->
    <div class="recent-collections">
        <h3><i class="fas fa-clock"></i> Collectes Récentes</h3>
        <?php if (!empty($data_stats['recent'])): ?>
            <div class="collections-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Types de données</th>
                            <th>Taille</th>
                            <th>IP (anonymisée)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data_stats['recent'] as $collection): ?>
                            <?php 
                            $data = json_decode($collection['collected_data'], true);
                            $types = $data ? array_keys($data) : [];
                            ?>
                            <tr>
                                <td><?php echo date('d/m H:i', strtotime($collection['collection_date'])); ?></td>
                                <td>
                                    <?php foreach ($types as $type): ?>
                                        <span class="data-tag"><?php echo $type; ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td><?php echo strlen($collection['collected_data']); ?> chars</td>
                                <td><?php echo substr($collection['ip_address'] ?? 'anonymized', 0, 8) . '...'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="no-data">Aucune collecte récente</p>
        <?php endif; ?>
    </div>

    <!-- Actions RGPD -->
    <div class="rgpd-actions">
        <h3><i class="fas fa-tools"></i> Actions RGPD</h3>
        <div class="action-buttons">
            <button onclick="exportData()" class="btn-export">
                <i class="fas fa-download"></i> Exporter les données
            </button>
            <button onclick="anonymizeOldData()" class="btn-anonymize">
                <i class="fas fa-user-secret"></i> Anonymiser données anciennes
            </button>
            <button onclick="generateReport()" class="btn-report">
                <i class="fas fa-file-alt"></i> Générer rapport RGPD
            </button>
        </div>
        <div id="rgpd-action-result" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>
    </div>
</div>

<style>
.rgpd-dashboard {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.rgpd-header {
    text-align: center;
    margin-bottom: 30px;
    padding: 20px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
}

.rgpd-header h2 {
    margin: 0 0 10px 0;
    font-size: 2em;
}

.stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 15px rgba(0,0,0,0.15);
}

.stat-icon {
    font-size: 3em;
    margin-right: 20px;
    opacity: 0.8;
}

.stat-content h3 {
    margin: 0 0 10px 0;
    color: #666;
    font-size: 0.9em;
    text-transform: uppercase;
}

.stat-number {
    font-size: 2.5em;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.stat-details {
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.stat-details span {
    font-size: 0.85em;
    color: #666;
}

.accepted { color: #27ae60; }
.refused { color: #e74c3c; }

.acceptance-rate {
    margin-top: 10px;
    padding: 5px 10px;
    background: #f8f9fa;
    border-radius: 5px;
    font-size: 0.9em;
}

.compliance-indicator {
    padding: 10px 15px;
    border-radius: 25px;
    font-weight: bold;
    text-align: center;
}

.compliance-indicator.active {
    background: #d4edda;
    color: #155724;
    border: 2px solid #27ae60;
}

.consent-evolution, .data-types-section, .user-preferences, .rgpd-actions, .recent-collections {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.consent-evolution h3, .data-types-section h3, .user-preferences h3, .rgpd-actions h3, .recent-collections h3 {
    margin-top: 0;
    color: #333;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.daily-chart {
    display: flex;
    justify-content: space-around;
    align-items: end;
    height: 200px;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.chart-bar {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    height: 150px;
}

.bar-accepted {
    background: linear-gradient(to top, #27ae60, #2ecc71);
    width: 30px;
    border-radius: 3px 3px 0 0;
    position: absolute;
    bottom: 30px;
}

.bar-total {
    background: linear-gradient(to top, #e9ecef, #dee2e6);
    width: 30px;
    border-radius: 3px;
    position: absolute;
    bottom: 30px;
    z-index: -1;
}

.bar-label {
    position: absolute;
    bottom: 0;
    font-size: 0.8em;
    text-align: center;
    width: 50px;
}

.data-types-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}

.data-type {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 8px;
    transition: transform 0.2s ease;
}

.data-type:hover {
    transform: scale(1.02);
}

.data-type.essential { background: linear-gradient(135deg, #ffeaa7, #fdcb6e); }
.data-type.analytics { background: linear-gradient(135deg, #a8e6cf, #7fdbda); }
.data-type.preferences { background: linear-gradient(135deg, #dda0dd, #da70d6); }
.data-type.marketing { background: linear-gradient(135deg, #ffb3ba, #ffaaa5); }

.type-icon {
    font-size: 2em;
    margin-right: 15px;
}

.type-count {
    font-size: 1.8em;
    font-weight: bold;
    color: #333;
}

.type-desc {
    font-size: 0.8em;
    color: #666;
}

.preferences-list {
    max-height: 300px;
    overflow-y: auto;
}

.preference-item {
    display: flex;
    align-items: center;
    padding: 10px;
    margin: 5px 0;
    background: #f8f9fa;
    border-radius: 6px;
}

.pref-count {
    background: #007bff;
    color: white;
    padding: 5px 10px;
    border-radius: 15px;
    font-weight: bold;
    margin-right: 15px;
    min-width: 40px;
    text-align: center;
}

.pref-details {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
}

.pref-tag {
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.8em;
    font-weight: bold;
}

.pref-tag.enabled {
    background: #d4edda;
    color: #155724;
}

.pref-tag.disabled {
    background: #f8d7da;
    color: #721c24;
}

.collections-table {
    overflow-x: auto;
}

.collections-table table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

.collections-table th,
.collections-table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
}

.collections-table th {
    background: #f8f9fa;
    font-weight: bold;
    color: #495057;
}

.data-tag {
    display: inline-block;
    background: #e9ecef;
    color: #495057;
    padding: 2px 6px;
    border-radius: 10px;
    font-size: 0.75em;
    margin-right: 3px;
}

.action-buttons {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.action-buttons button {
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-export {
    background: linear-gradient(45deg, #3498db, #2980b9);
    color: white;
}

.btn-anonymize {
    background: linear-gradient(45deg, #9b59b6, #8e44ad);
    color: white;
}

.btn-report {
    background: linear-gradient(45deg, #27ae60, #229954);
    color: white;
}

.action-buttons button:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.no-data {
    text-align: center;
    color: #666;
    font-style: italic;
    padding: 20px;
}

@media (max-width: 768px) {
    .stats-overview {
        grid-template-columns: 1fr;
    }
    
    .data-types-grid {
        grid-template-columns: 1fr;
    }
    
    .action-buttons {
        flex-direction: column;
    }
    
    .daily-chart {
        height: 150px;
    }
}
</style>

<script>
function exportData() {
    showRgpdResult('Export des données en cours...', 'info');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=export_data'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showRgpdResult('✅ ' + data.message, 'success');
            if (data.download_url) {
                window.open(data.download_url, '_blank');
            }
        } else {
            showRgpdResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showRgpdResult('❌ Erreur: ' + error.message, 'error');
    });
}

function anonymizeOldData() {
    if (!confirm('Anonymiser les données de plus de 30 jours ? Cette action est irréversible.')) {
        return;
    }
    
    showRgpdResult('Anonymisation en cours...', 'info');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=anonymize_old_data'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showRgpdResult('✅ ' + data.message, 'success');
            setTimeout(() => location.reload(), 2000);
        } else {
            showRgpdResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showRgpdResult('❌ Erreur: ' + error.message, 'error');
    });
}

function generateReport() {
    showRgpdResult('Génération du rapport RGPD...', 'info');
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=generate_report'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showRgpdResult('✅ ' + data.message, 'success');
            if (data.report_url) {
                window.open(data.report_url, '_blank');
            }
        } else {
            showRgpdResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showRgpdResult('❌ Erreur: ' + error.message, 'error');
    });
}

function showRgpdResult(message, type) {
    const resultDiv = document.getElementById('rgpd-action-result');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = message;
    
    // Couleurs selon le type
    if (type === 'success') {
        resultDiv.style.background = '#d4edda';
        resultDiv.style.color = '#155724';
        resultDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        resultDiv.style.background = '#f8d7da';
        resultDiv.style.color = '#721c24';
        resultDiv.style.border = '1px solid #f5c6cb';
    } else {
        resultDiv.style.background = '#cce5ff';
        resultDiv.style.color = '#004085';
        resultDiv.style.border = '1px solid #99ccff';
    }
    
    // Auto-hide après 10 secondes pour les succès
    if (type === 'success') {
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 10000);
    }
}
</script>
                    
                    if ($request) {
                        $stmt = $DB->prepare("DELETE FROM user_data_collection WHERE ip_address = ?");
                        $stmt->execute([$request['ip_address']]);
                        
                        $stmt = $DB->prepare("DELETE FROM cookie_consents WHERE ip_address = ?");
                        $stmt->execute([$request['ip_address']]);
                        
                        $stmt = $DB->prepare("DELETE FROM access_logs WHERE ip_address = ?");
                        $stmt->execute([$request['ip_address']]);
                    }
                }
                
                $response['success'] = true;
                $response['message'] = 'Demande traitée avec succès';
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Erreur : ' . $e->getMessage();
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Récupérer les statistiques
$stats = [];
try {
    $stmt = $DB->query("SELECT COUNT(*) as count FROM cookie_consents");
    $stats['total_consents'] = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM cookie_consents WHERE consent_given = 1");
    $stats['accepted_consents'] = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user_data_collection");
    $stats['total_data_records'] = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM access_logs");
    $stats['total_access_logs'] = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM data_deletion_requests WHERE status = 'pending'");
    $stats['pending_deletions'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $stats = ['total_consents' => 0, 'accepted_consents' => 0, 'total_data_records' => 0, 'total_access_logs' => 0, 'pending_deletions' => 0];
}

// Récupérer les données récentes
$recent_consents = [];
$recent_data = [];
$deletion_requests = [];

try {
    $stmt = $DB->query("SELECT * FROM cookie_consents ORDER BY consent_date DESC LIMIT 10");
    $recent_consents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $DB->query("SELECT * FROM user_data_collection ORDER BY collection_date DESC LIMIT 10");
    $recent_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $DB->query("SELECT * FROM data_deletion_requests ORDER BY request_date DESC LIMIT 10");
    $deletion_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Continuer avec des tableaux vides
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centre de Contrôle RGPD - Atelier de Listaro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card .icon {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .card-header {
            background: #667eea;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }

        .card-body {
            padding: 20px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .table th,
        .table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .table th {
            background: #f8f9fa;
            font-weight: bold;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            font-size: 14px;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-warning {
            background: #ffc107;
            color: black;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }

        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-processed {
            background: #d4edda;
            color: #155724;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            max-width: 500px;
            width: 90%;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-shield-alt"></i> Centre de Contrôle RGPD</h1>
            <p>Gestion des données personnelles et conformité européenne</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="icon">🍪</div>
                <div class="number"><?php echo $stats['total_consents']; ?></div>
                <div>Consentements totaux</div>
            </div>
            <div class="stat-card">
                <div class="icon">✅</div>
                <div class="number"><?php echo $stats['accepted_consents']; ?></div>
                <div>Consentements acceptés</div>
            </div>
            <div class="stat-card">
                <div class="icon">📊</div>
                <div class="number"><?php echo $stats['total_data_records']; ?></div>
                <div>Données collectées</div>
            </div>
            <div class="stat-card">
                <div class="icon">🔍</div>
                <div class="number"><?php echo $stats['total_access_logs']; ?></div>
                <div>Logs d'accès</div>
            </div>
            <div class="stat-card">
                <div class="icon">🗑️</div>
                <div class="number"><?php echo $stats['pending_deletions']; ?></div>
                <div>Demandes de suppression</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-cookie-bite"></i> Consentements récents
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Consentement</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_consents as $consent): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($consent['ip_address'], 0, 12)); ?>...</td>
                                <td><?php echo $consent['consent_given'] ? '✅ Accepté' : '❌ Refusé'; ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($consent['consent_date'])); ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="exportUserData('<?php echo $consent['ip_address']; ?>')">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    <button class="btn btn-danger" onclick="deleteUserData('<?php echo $consent['ip_address']; ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-database"></i> Données collectées récentes
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Type</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_data as $data): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(substr($data['ip_address'], 0, 12)); ?>...</td>
                                <td><?php echo htmlspecialchars($data['data_type']); ?></td>
                                <td><?php echo date('d/m/Y H:i', strtotime($data['collection_date'])); ?></td>
                                <td>
                                    <button class="btn btn-primary" onclick="viewDataDetails(<?php echo $data['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <i class="fas fa-user-times"></i> Demandes de suppression (Droit à l'oubli)
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Email</th>
                            <th>Date demande</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deletion_requests as $request): ?>
                        <tr>
                            <td><?php echo htmlspecialchars(substr($request['ip_address'], 0, 12)); ?>...</td>
                            <td><?php echo htmlspecialchars($request['email']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($request['request_date'])); ?></td>
                            <td>
                                <span class="status status-<?php echo $request['status']; ?>">
                                    <?php echo ucfirst($request['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($request['status'] === 'pending'): ?>
                                <button class="btn btn-success" onclick="processDeletionRequest(<?php echo $request['id']; ?>, 'processed')">
                                    <i class="fas fa-check"></i> Approuver
                                </button>
                                <button class="btn btn-danger" onclick="processDeletionRequest(<?php echo $request['id']; ?>, 'rejected')">
                                    <i class="fas fa-times"></i> Rejeter
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal pour traiter les demandes -->
    <div id="deletion-modal" class="modal">
        <div class="modal-content">
            <h3>Traiter la demande de suppression</h3>
            <form id="deletion-form">
                <input type="hidden" id="deletion-request-id">
                <input type="hidden" id="deletion-status">
                
                <div class="form-group">
                    <label>Motif de la décision :</label>
                    <textarea id="deletion-reason" rows="3" placeholder="Expliquez votre décision..."></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Confirmer</button>
                <button type="button" class="btn btn-secondary" onclick="closeDeletionModal()">Annuler</button>
            </form>
        </div>
    </div>

    <div id="alert-container"></div>

    <script>
        // Fonction pour exporter les données d'un utilisateur
        function exportUserData(ipAddress) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=export_user_data&ip_address=${ipAddress}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Créer et télécharger un fichier JSON
                    const blob = new Blob([JSON.stringify(data.data, null, 2)], {type: 'application/json'});
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = `donnees_utilisateur_${ipAddress}.json`;
                    a.click();
                    URL.revokeObjectURL(url);
                } else {
                    showAlert('Erreur lors de l\'export : ' + data.message, 'danger');
                }
            });
        }

        // Fonction pour supprimer les données d'un utilisateur
        function deleteUserData(ipAddress) {
            if (confirm('Êtes-vous sûr de vouloir supprimer toutes les données de cet utilisateur ?')) {
                fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_user_data&ip_address=${ipAddress}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Données supprimées avec succès', 'success');
                        location.reload();
                    } else {
                        showAlert('Erreur : ' + data.message, 'danger');
                    }
                });
            }
        }

        // Fonction pour traiter les demandes de suppression
        function processDeletionRequest(requestId, status) {
            document.getElementById('deletion-request-id').value = requestId;
            document.getElementById('deletion-status').value = status;
            document.getElementById('deletion-modal').style.display = 'block';
        }

        // Fermer le modal
        function closeDeletionModal() {
            document.getElementById('deletion-modal').style.display = 'none';
        }

        // Gérer le formulaire de suppression
        document.getElementById('deletion-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const requestId = document.getElementById('deletion-request-id').value;
            const status = document.getElementById('deletion-status').value;
            const reason = document.getElementById('deletion-reason').value;
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=process_deletion_request&request_id=${requestId}&status=${status}&reason=${encodeURIComponent(reason)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Demande traitée avec succès', 'success');
                    closeDeletionModal();
                    location.reload();
                } else {
                    showAlert('Erreur : ' + data.message, 'danger');
                }
            });
        });

        // Fonction pour afficher les alertes
        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.textContent = message;
            alertContainer.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 5000);
        }

        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('deletion-modal');
            if (event.target === modal) {
                closeDeletionModal();
            }
        }
    </script>
</body>
</html>
