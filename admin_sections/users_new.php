<?php
session_start();
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
            case 'delete_user':
                $user_id = intval($_POST['user_id']);
                
                // Ne pas supprimer l'utilisateur actuel
                if ($user_id == $_SESSION['user_id']) {
                    $response['message'] = 'Vous ne pouvez pas supprimer votre propre compte';
                    break;
                }
                
                $stmt = $DB->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt->execute([$user_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Utilisateur supprimé avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression';
                }
                break;
                
            case 'change_role':
                $user_id = intval($_POST['user_id']);
                $new_role = $_POST['role'];
                
                if (!in_array($new_role, ['admin', 'user'])) {
                    $response['message'] = 'Rôle invalide';
                    break;
                }
                
                $stmt = $DB->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$new_role, $user_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Rôle modifié avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la modification';
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

// Récupérer les utilisateurs
try {
    $stmt = $DB->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $users = [];
}
?>

<div class="users-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Gestion des utilisateurs</h2>
        <div style="display: flex; gap: 10px;">
            <span class="btn" style="background: #6c757d; cursor: default;">
                <i class="fas fa-users"></i> <?php echo count($users); ?> utilisateurs
            </span>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="stats-cards" style="margin-bottom: 20px;">
        <?php
        $admin_count = 0;
        $user_count = 0;
        foreach ($users as $user) {
            if ($user['role'] === 'admin') $admin_count++;
            else $user_count++;
        }
        ?>
        <div class="stat-card">
            <div class="icon">
                <i class="fas fa-user-shield"></i>
            </div>
            <div class="number"><?php echo $admin_count; ?></div>
            <div class="label">Administrateurs</div>
        </div>
        <div class="stat-card">
            <div class="icon">
                <i class="fas fa-user"></i>
            </div>
            <div class="number"><?php echo $user_count; ?></div>
            <div class="label">Utilisateurs</div>
        </div>
    </div>
    
    <!-- Filtres -->
    <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
        <input type="text" id="search-users" placeholder="Rechercher un utilisateur..." 
               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
        <select id="role-filter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">Tous les rôles</option>
            <option value="admin">Administrateurs</option>
            <option value="user">Utilisateurs</option>
        </select>
        <button class="btn" onclick="filterUsers()">
            <i class="fas fa-filter"></i> Filtrer
        </button>
    </div>
    
    <!-- Liste des utilisateurs -->
    <div class="table-container">
        <table id="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nom d'utilisateur</th>
                    <th>Email</th>
                    <th>Rôle</th>
                    <th>Date d'inscription</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr data-role="<?php echo $user['role']; ?>" 
                        data-username="<?php echo htmlspecialchars(strtolower($user['username'])); ?>"
                        data-email="<?php echo htmlspecialchars(strtolower($user['email'])); ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($user['username']); ?>
                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span style="color: #28a745; font-size: 0.8em;">(Vous)</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['role'] === 'admin'): ?>
                                <span style="background: #dc3545; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8em;">
                                    <i class="fas fa-user-shield"></i> Admin
                                </span>
                            <?php else: ?>
                                <span style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 3px; font-size: 0.8em;">
                                    <i class="fas fa-user"></i> Utilisateur
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <select onchange="changeRole(<?php echo $user['id']; ?>, this.value)" 
                                        style="padding: 4px; border: 1px solid #ddd; border-radius: 3px; margin-right: 5px;">
                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>Utilisateur</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                                <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['id']; ?>)" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php else: ?>
                                <em style="color: #6c757d;">Votre compte</em>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Variables globales pour les utilisateurs
let usersData = <?php echo json_encode($users); ?>;

// Filtrer les utilisateurs
function filterUsers() {
    const searchTerm = document.getElementById('search-users').value.toLowerCase();
    const roleFilter = document.getElementById('role-filter').value;
    const table = document.getElementById('users-table');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const username = row.getAttribute('data-username');
        const email = row.getAttribute('data-email');
        const role = row.getAttribute('data-role');
        
        const matchesSearch = !searchTerm || username.includes(searchTerm) || email.includes(searchTerm);
        const matchesRole = !roleFilter || role === roleFilter;
        
        if (matchesSearch && matchesRole) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Filtrer en temps réel
document.getElementById('search-users').addEventListener('input', filterUsers);
document.getElementById('role-filter').addEventListener('change', filterUsers);

// Changer le rôle d'un utilisateur
function changeRole(userId, newRole) {
    if (!confirm('Êtes-vous sûr de vouloir changer le rôle de cet utilisateur ?')) {
        // Restaurer la valeur précédente
        location.reload();
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'change_role');
    formData.append('user_id', userId);
    formData.append('role', newRole);
    
    fetch('admin_sections/users.php', {
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

// Supprimer un utilisateur
function deleteUser(userId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_user');
    formData.append('user_id', userId);
    
    fetch('admin_sections/users.php', {
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

console.log('Section utilisateurs chargée avec', usersData.length, 'utilisateurs');
</script>
