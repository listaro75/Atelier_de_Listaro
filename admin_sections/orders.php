<?php
// Version corrigée pour éviter les conflits de session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

// Gestion des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = ['success' => false, 'message' => ''];
    
    try {
        switch ($_POST['action']) {
            case 'update_status':
                $order_id = intval($_POST['order_id']);
                $status = $_POST['status'];
                
                $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
                if (!in_array($status, $allowed_statuses)) {
                    $response['message'] = 'Statut invalide';
                    break;
                }
                
                $stmt = $DB->prepare("UPDATE orders SET status = ? WHERE id = ?");
                if ($stmt->execute([$status, $order_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Statut mis à jour avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la mise à jour';
                }
                break;
                
            case 'delete_order':
                $order_id = intval($_POST['order_id']);
                
                $stmt = $DB->prepare("DELETE FROM orders WHERE id = ?");
                if ($stmt->execute([$order_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Commande supprimée avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression';
                }
                break;
        }
    } catch (Exception $e) {
        $response['message'] = 'Erreur: ' . $e->getMessage();
    }
    
    // Répondre en JSON pour les requêtes AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les commandes
try {
    $stmt = $DB->query("
        SELECT o.*, u.username, u.email 
        FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id 
        ORDER BY o.created_at DESC
    ");
    $orders = $stmt->fetchAll();
} catch (Exception $e) {
    $orders = [];
}

// Calculer les statistiques
$stats = [
    'total' => count($orders),
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0,
    'total_amount' => 0
];

foreach ($orders as $order) {
    $stats[$order['status']]++;
    $stats['total_amount'] += $order['total_amount'];
}
?>

<div class="orders-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Gestion des commandes</h2>
        <div style="display: flex; gap: 10px;">
            <span class="btn" style="background: #6c757d; cursor: default;">
                <i class="fas fa-shopping-cart"></i> <?php echo $stats['total']; ?> commandes
            </span>
            <span class="btn" style="background: #28a745; cursor: default;">
                <i class="fas fa-euro-sign"></i> <?php echo number_format($stats['total_amount'], 2); ?> €
            </span>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-cards" style="margin-bottom: 20px;">
        <div class="stat-card">
            <div class="icon" style="color: #ffc107;">
                <i class="fas fa-clock"></i>
            </div>
            <div class="number"><?php echo $stats['pending']; ?></div>
            <div class="label">En attente</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #17a2b8;">
                <i class="fas fa-cog"></i>
            </div>
            <div class="number"><?php echo $stats['processing']; ?></div>
            <div class="label">En traitement</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #6f42c1;">
                <i class="fas fa-truck"></i>
            </div>
            <div class="number"><?php echo $stats['shipped']; ?></div>
            <div class="label">Expédiées</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #28a745;">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="number"><?php echo $stats['delivered']; ?></div>
            <div class="label">Livrées</div>
        </div>
        <div class="stat-card">
            <div class="icon" style="color: #dc3545;">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="number"><?php echo $stats['cancelled']; ?></div>
            <div class="label">Annulées</div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
        <input type="text" id="search-orders" placeholder="Rechercher par client ou numéro..." 
               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
        <select id="status-filter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">Tous les statuts</option>
            <option value="pending">En attente</option>
            <option value="processing">En traitement</option>
            <option value="shipped">Expédiée</option>
            <option value="delivered">Livrée</option>
            <option value="cancelled">Annulée</option>
        </select>
        <button class="btn" onclick="filterOrders()">
            <i class="fas fa-filter"></i> Filtrer
        </button>
    </div>
    
    <!-- Liste des commandes -->
    <div class="table-container">
        <table id="orders-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client</th>
                    <th>Email</th>
                    <th>Montant</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr data-status="<?php echo $order['status']; ?>" 
                        data-customer="<?php echo htmlspecialchars(strtolower($order['username'] ?? '')); ?>"
                        data-email="<?php echo htmlspecialchars(strtolower($order['email'] ?? '')); ?>"
                        data-order-id="<?php echo $order['id']; ?>">
                        <td>#<?php echo $order['id']; ?></td>
                        <td><?php echo htmlspecialchars($order['username'] ?? 'Utilisateur supprimé'); ?></td>
                        <td><?php echo htmlspecialchars($order['email'] ?? 'N/A'); ?></td>
                        <td><?php echo number_format($order['total_amount'], 2); ?> €</td>
                        <td>
                            <?php
                            $status_colors = [
                                'pending' => '#ffc107',
                                'processing' => '#17a2b8',
                                'shipped' => '#6f42c1',
                                'delivered' => '#28a745',
                                'cancelled' => '#dc3545'
                            ];
                            $status_labels = [
                                'pending' => 'En attente',
                                'processing' => 'En traitement',
                                'shipped' => 'Expédiée',
                                'delivered' => 'Livrée',
                                'cancelled' => 'Annulée'
                            ];
                            $status_icons = [
                                'pending' => 'fas fa-clock',
                                'processing' => 'fas fa-cog',
                                'shipped' => 'fas fa-truck',
                                'delivered' => 'fas fa-check-circle',
                                'cancelled' => 'fas fa-times-circle'
                            ];
                            ?>
                            <span style="background: <?php echo $status_colors[$order['status']]; ?>; color: white; padding: 4px 8px; border-radius: 3px; font-size: 0.8em;">
                                <i class="<?php echo $status_icons[$order['status']]; ?>"></i> 
                                <?php echo $status_labels[$order['status']]; ?>
                            </span>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></td>
                        <td>
                            <select onchange="updateStatus(<?php echo $order['id']; ?>, this.value)" 
                                    style="padding: 4px; border: 1px solid #ddd; border-radius: 3px; margin-right: 5px;">
                                <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>En traitement</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Expédiée</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Livrée</option>
                                <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Annulée</option>
                            </select>
                            <button class="btn btn-danger" onclick="deleteOrder(<?php echo $order['id']; ?>)" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php if (empty($orders)): ?>
        <div style="text-align: center; padding: 50px; color: #6c757d;">
            <i class="fas fa-shopping-cart" style="font-size: 3em; margin-bottom: 20px;"></i>
            <p>Aucune commande trouvée.</p>
        </div>
    <?php endif; ?>
</div>

<script>
// Variables globales pour les commandes
let ordersData = <?php echo json_encode($orders); ?>;

// Filtrer les commandes
function filterOrders() {
    const searchTerm = document.getElementById('search-orders').value.toLowerCase();
    const statusFilter = document.getElementById('status-filter').value;
    const table = document.getElementById('orders-table');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const customer = row.getAttribute('data-customer');
        const email = row.getAttribute('data-email');
        const orderId = row.getAttribute('data-order-id');
        const status = row.getAttribute('data-status');
        
        const matchesSearch = !searchTerm || 
                            customer.includes(searchTerm) || 
                            email.includes(searchTerm) || 
                            orderId.includes(searchTerm);
        const matchesStatus = !statusFilter || status === statusFilter;
        
        if (matchesSearch && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Filtrer en temps réel
document.getElementById('search-orders').addEventListener('input', filterOrders);
document.getElementById('status-filter').addEventListener('change', filterOrders);

// Mettre à jour le statut d'une commande
function updateStatus(orderId, newStatus) {
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', orderId);
    formData.append('status', newStatus);
    
    fetch('', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
        location.reload();
    });
}

// Supprimer une commande
function deleteOrder(orderId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette commande ? Cette action est irréversible.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_order');
    formData.append('order_id', orderId);
    
    fetch('', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Recharger la page pour voir les changements
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
}

console.log('Section commandes chargée avec', ordersData.length, 'commandes');
</script>
