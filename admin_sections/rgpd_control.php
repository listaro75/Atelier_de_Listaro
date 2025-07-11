<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'delete_user_data':
                $ip_address = $_POST['ip_address'];
                
                // Supprimer toutes les données liées à cette IP
                $stmt = $DB->prepare("DELETE FROM user_data_collection WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                
                $stmt = $DB->prepare("DELETE FROM cookie_consents WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                
                $stmt = $DB->prepare("DELETE FROM access_logs WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                
                $response['success'] = true;
                $response['message'] = 'Données supprimées avec succès';
                break;
                
            case 'export_user_data':
                $ip_address = $_POST['ip_address'];
                
                // Récupérer toutes les données
                $data = [];
                
                $stmt = $DB->prepare("SELECT * FROM user_data_collection WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                $data['collected_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = $DB->prepare("SELECT * FROM cookie_consents WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                $data['consents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $stmt = $DB->prepare("SELECT * FROM access_logs WHERE ip_address = ?");
                $stmt->execute([$ip_address]);
                $data['access_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response['success'] = true;
                $response['data'] = $data;
                break;
                
            case 'process_deletion_request':
                $request_id = intval($_POST['request_id']);
                $status = $_POST['status'];
                $reason = $_POST['reason'] ?? '';
                
                $stmt = $DB->prepare("UPDATE data_deletion_requests SET status = ?, reason = ?, processed_date = NOW() WHERE id = ?");
                $stmt->execute([$status, $reason, $request_id]);
                
                if ($status === 'processed') {
                    // Supprimer les données si approuvé
                    $stmt = $DB->prepare("SELECT ip_address FROM data_deletion_requests WHERE id = ?");
                    $stmt->execute([$request_id]);
                    $request = $stmt->fetch(PDO::FETCH_ASSOC);
                    
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
