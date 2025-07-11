<?php
// Section système pour le tableau de bord admin
include_once(__DIR__ . '/../_functions/raspi_stats.php');

// Récupérer les statistiques
$stats = RaspberryPiStats::getAllStats();
?>

<div class="system-stats-container">
    <h2>📊 État du Système Raspberry Pi</h2>
    
    <div class="stats-grid">
        <!-- Température -->
        <div class="stat-card temperature">
            <div class="stat-icon">🌡️</div>
            <div class="stat-content">
                <h3>Température CPU</h3>
                <?php if ($stats['temperature'] !== null): ?>
                    <div class="stat-value <?php echo $stats['temperature'] > 70 ? 'warning' : ($stats['temperature'] > 60 ? 'caution' : 'good'); ?>">
                        <?php echo $stats['temperature']; ?>°C
                    </div>
                    <div class="stat-status">
                        <?php 
                        if ($stats['temperature'] > 70) echo "🔥 Chaud";
                        elseif ($stats['temperature'] > 60) echo "⚠️ Tiède";
                        else echo "✅ Normal";
                        ?>
                    </div>
                <?php else: ?>
                    <div class="stat-value unavailable">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- CPU -->
        <div class="stat-card cpu">
            <div class="stat-icon">🖥️</div>
            <div class="stat-content">
                <h3>Charge CPU</h3>
                <?php if ($stats['cpu'] !== null): ?>
                    <div class="stat-value">
                        <?php echo $stats['cpu']['1min']; ?>%
                    </div>
                    <div class="stat-details">
                        1m: <?php echo $stats['cpu']['1min']; ?>% | 
                        5m: <?php echo $stats['cpu']['5min']; ?>% | 
                        15m: <?php echo $stats['cpu']['15min']; ?>%
                    </div>
                <?php else: ?>
                    <div class="stat-value unavailable">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RAM -->
        <div class="stat-card memory">
            <div class="stat-icon">🧠</div>
            <div class="stat-content">
                <h3>Mémoire RAM</h3>
                <?php if ($stats['memory'] !== null): ?>
                    <div class="stat-value <?php echo $stats['memory']['percent'] > 80 ? 'warning' : ($stats['memory']['percent'] > 60 ? 'caution' : 'good'); ?>">
                        <?php echo $stats['memory']['percent']; ?>%
                    </div>
                    <div class="stat-details">
                        <?php echo $stats['memory']['used']; ?> MB / <?php echo $stats['memory']['total']; ?> MB
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $stats['memory']['percent']; ?>%"></div>
                    </div>
                <?php else: ?>
                    <div class="stat-value unavailable">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stockage -->
        <div class="stat-card storage">
            <div class="stat-icon">💾</div>
            <div class="stat-content">
                <h3>Stockage</h3>
                <?php if ($stats['disk'] !== null): ?>
                    <div class="stat-value <?php echo $stats['disk']['percent'] > 90 ? 'warning' : ($stats['disk']['percent'] > 75 ? 'caution' : 'good'); ?>">
                        <?php echo $stats['disk']['percent']; ?>%
                    </div>
                    <div class="stat-details">
                        <?php echo $stats['disk']['used']; ?> / <?php echo $stats['disk']['total']; ?>
                        <br>Libre: <?php echo $stats['disk']['free']; ?>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $stats['disk']['percent']; ?>%"></div>
                    </div>
                <?php else: ?>
                    <div class="stat-value unavailable">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Uptime -->
        <div class="stat-card uptime">
            <div class="stat-icon">⏱️</div>
            <div class="stat-content">
                <h3>Temps de fonctionnement</h3>
                <?php if ($stats['uptime'] !== null): ?>
                    <div class="stat-value good">
                        <?php echo $stats['uptime']; ?>
                    </div>
                    <div class="stat-status">Actif</div>
                <?php else: ?>
                    <div class="stat-value unavailable">N/A</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Réseau -->
        <div class="stat-card network">
            <div class="stat-icon">🌐</div>
            <div class="stat-content">
                <h3>Réseau</h3>
                <?php if (!empty($stats['network'])): ?>
                    <div class="stat-value good">
                        <?php echo $stats['network']['local_ip']; ?>
                    </div>
                    <div class="stat-details">
                        IP Locale: <?php echo $stats['network']['local_ip']; ?>
                    </div>
                <?php else: ?>
                    <div class="stat-value unavailable">N/A</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Informations système détaillées -->
    <div class="system-details">
        <h3>🔧 Informations Système</h3>
        <div class="details-grid">
            <?php if (!empty($stats['system'])): ?>
                <?php if (isset($stats['system']['pi_model'])): ?>
                    <div class="detail-item">
                        <strong>Modèle:</strong> <?php echo htmlspecialchars($stats['system']['pi_model']); ?>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <strong>Hostname:</strong> <?php echo htmlspecialchars($stats['system']['hostname']); ?>
                </div>
                <div class="detail-item">
                    <strong>Architecture:</strong> <?php echo htmlspecialchars($stats['system']['architecture']); ?>
                </div>
                <div class="detail-item">
                    <strong>Kernel:</strong> <?php echo htmlspecialchars($stats['system']['kernel']); ?>
                </div>
                <div class="detail-item">
                    <strong>PHP:</strong> <?php echo htmlspecialchars($stats['system']['php_version']); ?>
                </div>
            <?php endif; ?>
            <div class="detail-item">
                <strong>Dernière mise à jour:</strong> <?php echo date('d/m/Y H:i:s', $stats['timestamp']); ?>
            </div>
        </div>
    </div>

    <!-- Bouton de rafraîchissement -->
    <div class="refresh-section">
        <button onclick="refreshSystemStats()" class="refresh-btn">
            🔄 Actualiser les statistiques
        </button>
        <small>Mise à jour automatique toutes les 30 secondes</small>
    </div>
</div>

<style>
.system-stats-container {
    background: white;
    border-radius: 12px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 20px;
    display: flex;
    align-items: center;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 12px rgba(0,0,0,0.15);
}

.stat-icon {
    font-size: 2.5em;
    margin-right: 15px;
    opacity: 0.8;
}

.stat-content {
    flex: 1;
}

.stat-content h3 {
    margin: 0 0 8px 0;
    font-size: 0.9em;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stat-value {
    font-size: 1.8em;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-value.good { color: #28a745; }
.stat-value.caution { color: #ffc107; }
.stat-value.warning { color: #dc3545; }
.stat-value.unavailable { color: #6c757d; }

.stat-details {
    font-size: 0.85em;
    color: #666;
    line-height: 1.4;
}

.stat-status {
    font-size: 0.8em;
    font-weight: bold;
    margin-top: 5px;
}

.progress-bar {
    width: 100%;
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    margin-top: 8px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    border-radius: 3px;
    transition: width 0.3s ease;
}

.stat-card.temperature .progress-fill { background: linear-gradient(90deg, #17a2b8, #007bff); }
.stat-card.storage .progress-fill { background: linear-gradient(90deg, #6f42c1, #e83e8c); }
.stat-card.memory .progress-fill { background: linear-gradient(90deg, #fd7e14, #ffc107); }

.system-details {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
    margin-top: 25px;
}

.system-details h3 {
    margin: 0 0 15px 0;
    color: #333;
}

.details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.detail-item {
    font-size: 0.9em;
    padding: 5px 0;
}

.detail-item strong {
    color: #495057;
}

.refresh-section {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.refresh-btn {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 10px;
    display: block;
    margin: 0 auto 10px auto;
}

.refresh-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
}

.refresh-section small {
    color: #6c757d;
    display: block;
}

/* Responsive */
@media (max-width: 768px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .details-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-icon {
        font-size: 2em;
        margin-right: 12px;
    }
    
    .stat-value {
        font-size: 1.5em;
    }
}
</style>

<script>
// Fonction pour actualiser les statistiques
function refreshSystemStats() {
    const button = document.querySelector('.refresh-btn');
    button.innerHTML = '🔄 Actualisation...';
    button.disabled = true;
    
    // Recharger la section système
    fetch(window.location.href)
        .then(() => {
            window.location.reload();
        })
        .catch(() => {
            button.innerHTML = '🔄 Actualiser les statistiques';
            button.disabled = false;
            alert('Erreur lors de l\'actualisation');
        });
}

// Auto-refresh toutes les 30 secondes
setInterval(() => {
    // Actualisation silencieuse en arrière-plan
    console.log('Auto-refresh des statistiques système...');
    // Vous pouvez implémenter un refresh AJAX ici si souhaité
}, 30000);
</script>
