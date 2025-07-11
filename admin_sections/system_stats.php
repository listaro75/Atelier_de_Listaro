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

    <!-- Actions système -->
    <div class="system-actions" style="margin-top: 30px; background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h3 style="margin-top: 0; color: #333; border-bottom: 2px solid #e9ecef; padding-bottom: 10px;">
            <i class="fas fa-cogs"></i> Actions Système
        </h3>
        <div class="action-buttons" style="display: flex; gap: 15px; flex-wrap: wrap; margin-top: 20px;">
            <button onclick="restartServer()" class="btn-restart" style="
                background: linear-gradient(45deg, #e74c3c, #c0392b);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
            ">
                <i class="fas fa-power-off"></i>
                Redémarrer le serveur
            </button>
            
            <button onclick="clearCache()" class="btn-cache" style="
                background: linear-gradient(45deg, #f39c12, #e67e22);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
            ">
                <i class="fas fa-broom"></i>
                Vider le cache
            </button>
            
            <button onclick="checkDiskSpace()" class="btn-disk" style="
                background: linear-gradient(45deg, #3498db, #2980b9);
                color: white;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-weight: bold;
                transition: all 0.3s ease;
                display: flex;
                align-items: center;
                gap: 8px;
                font-size: 14px;
            ">
                <i class="fas fa-hdd"></i>
                Analyser l'espace disque
            </button>
        </div>
        
        <div id="action-result" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>
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

/* Styles pour les boutons d'action */
.btn-restart:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
}

.btn-cache:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
}

.btn-disk:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(52, 152, 219, 0.4);
}

.action-buttons button:active {
    transform: translateY(0);
}

.success-message {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error-message {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.warning-message {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeaa7;
}

.loading-message {
    background: #cce5ff;
    color: #004085;
    border: 1px solid #99ccff;
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

// Fonction pour redémarrer le serveur
function restartServer() {
    if (!confirm('⚠️ Êtes-vous sûr de vouloir redémarrer le serveur ? Cette action va interrompre temporairement le service.')) {
        return;
    }
    
    showActionResult('Redémarrage du serveur en cours...', 'loading');
    
    fetch('ajax/server_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=restart'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showActionResult('✅ Commande de redémarrage envoyée. Le serveur va redémarrer dans quelques secondes.', 'success');
        } else {
            showActionResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showActionResult('❌ Erreur de communication: ' + error.message, 'error');
    });
}

// Fonction pour vider le cache
function clearCache() {
    if (!confirm('Vider le cache du système ?')) {
        return;
    }
    
    showActionResult('Nettoyage du cache en cours...', 'loading');
    
    fetch('ajax/server_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear_cache'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showActionResult('✅ Cache vidé avec succès. ' + data.details, 'success');
        } else {
            showActionResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showActionResult('❌ Erreur: ' + error.message, 'error');
    });
}

// Fonction pour analyser l'espace disque
function checkDiskSpace() {
    showActionResult('Analyse de l\'espace disque en cours...', 'loading');
    
    fetch('ajax/server_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=check_disk'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showActionResult('📊 ' + data.details, 'success');
        } else {
            showActionResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showActionResult('❌ Erreur: ' + error.message, 'error');
    });
}

// Fonction pour afficher les résultats des actions
function showActionResult(message, type) {
    const resultDiv = document.getElementById('action-result');
    resultDiv.className = type + '-message';
    resultDiv.innerHTML = message;
    resultDiv.style.display = 'block';
    
    // Masquer automatiquement après 10 secondes pour les messages de succès
    if (type === 'success') {
        setTimeout(() => {
            resultDiv.style.display = 'none';
        }, 10000);
    }
}

// Auto-refresh toutes les 30 secondes
setInterval(() => {
    // Actualisation silencieuse en arrière-plan
    console.log('Auto-refresh des statistiques système...');
    // Vous pouvez implémenter un refresh AJAX ici si souhaité
}, 30000);
</script>
