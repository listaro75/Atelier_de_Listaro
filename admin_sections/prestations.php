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
            case 'add_prestation':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $duration = trim($_POST['duration']);
                $category = trim($_POST['category']);
                
                if (empty($name) || empty($description) || $price < 0 || empty($category)) {
                    $response['message'] = 'Tous les champs obligatoires doivent être remplis';
                    break;
                }
                
                $stmt = $DB->prepare("INSERT INTO prestations (name, description, price, duration, category) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $description, $price, $duration, $category])) {
                    $response['success'] = true;
                    $response['message'] = 'Prestation ajoutée avec succès';
                } else {
                    $response['message'] = 'Erreur lors de l\'ajout de la prestation';
                }
                break;
                
            case 'update_prestation':
                $id = intval($_POST['id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $duration = trim($_POST['duration']);
                $category = trim($_POST['category']);
                
                if (empty($name) || empty($description) || $price < 0 || empty($category)) {
                    $response['message'] = 'Tous les champs obligatoires doivent être remplis';
                    break;
                }
                
                $stmt = $DB->prepare("UPDATE prestations SET name = ?, description = ?, price = ?, duration = ?, category = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $price, $duration, $category, $id])) {
                    $response['success'] = true;
                    $response['message'] = 'Prestation mise à jour avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la mise à jour de la prestation';
                }
                break;
                
            case 'delete_prestation':
                $id = intval($_POST['id']);
                
                $stmt = $DB->prepare("DELETE FROM prestations WHERE id = ?");
                if ($stmt->execute([$id])) {
                    $response['success'] = true;
                    $response['message'] = 'Prestation supprimée avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression de la prestation';
                }
                break;
        }
        
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Erreur : ' . $e->getMessage();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les prestations
try {
    $stmt = $DB->query("SELECT * FROM prestations ORDER BY created_at DESC");
    $prestations = $stmt->fetchAll();
} catch (Exception $e) {
    $prestations = [];
}

// Récupérer les catégories uniques
try {
    $stmt = $DB->query("SELECT DISTINCT category FROM prestations ORDER BY category");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}
?>

<style>
    .prestations-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .prestations-header h2 {
        margin: 0;
        color: #333;
    }

    .btn {
        background: #3498db;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 5px;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: background 0.3s;
    }

    .btn:hover {
        background: #2980b9;
    }

    .btn-success {
        background: #27ae60;
    }

    .btn-success:hover {
        background: #219a52;
    }

    .btn-danger {
        background: #e74c3c;
    }

    .btn-danger:hover {
        background: #c0392b;
    }

    .btn-warning {
        background: #f39c12;
        color: white;
    }

    .btn-warning:hover {
        background: #e67e22;
    }

    .prestations-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .prestation-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        transition: transform 0.3s;
    }

    .prestation-card:hover {
        transform: translateY(-5px);
    }

    .prestation-card h3 {
        margin: 0 0 10px 0;
        color: #333;
        font-size: 1.3em;
    }

    .prestation-card .category {
        background: #3498db;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 0.8em;
        margin-bottom: 10px;
        display: inline-block;
    }

    .prestation-card .price {
        font-size: 1.5em;
        font-weight: bold;
        color: #27ae60;
        margin: 10px 0;
    }

    .prestation-card .duration {
        color: #666;
        font-size: 0.9em;
        margin-bottom: 15px;
    }

    .prestation-card .description {
        color: #555;
        line-height: 1.4;
        margin-bottom: 15px;
        max-height: 60px;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .prestation-card .actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .prestation-card .actions button {
        padding: 8px 12px;
        font-size: 0.9em;
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
        margin: 50px auto;
        padding: 30px;
        border-radius: 10px;
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }

    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    .modal-header h3 {
        margin: 0;
        color: #333;
    }

    .close {
        background: none;
        border: none;
        font-size: 1.5em;
        cursor: pointer;
        color: #999;
    }

    .close:hover {
        color: #333;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #333;
    }

    .form-group input,
    .form-group textarea,
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1em;
    }

    .form-group textarea {
        resize: vertical;
        height: 100px;
    }

    .form-group input:focus,
    .form-group textarea:focus,
    .form-group select:focus {
        outline: none;
        border-color: #3498db;
        box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.2);
    }

    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        display: none;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-error {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .stats-row {
        display: flex;
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-box {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        flex: 1;
        text-align: center;
    }

    .stat-box .number {
        font-size: 2em;
        font-weight: bold;
        color: #3498db;
    }

    .stat-box .label {
        color: #666;
        margin-top: 5px;
    }

    .search-filters {
        background: white;
        padding: 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .search-filters .form-row {
        display: flex;
        gap: 20px;
        align-items: end;
    }

    .search-filters .form-group {
        flex: 1;
        margin-bottom: 0;
    }

    .empty-state {
        text-align: center;
        padding: 50px;
        color: #666;
    }

    .empty-state i {
        font-size: 3em;
        color: #ddd;
        margin-bottom: 20px;
    }
</style>

<div class="prestations-header">
    <h2>Gestion des Prestations</h2>
    <button class="btn btn-success" onclick="openModal('add')">
        <i class="fas fa-plus"></i> Ajouter une prestation
    </button>
</div>

<div class="alert alert-success" id="success-alert"></div>
<div class="alert alert-error" id="error-alert"></div>

<div class="stats-row">
    <div class="stat-box">
        <div class="number"><?php echo count($prestations); ?></div>
        <div class="label">Total prestations</div>
    </div>
    <div class="stat-box">
        <div class="number"><?php echo count($categories); ?></div>
        <div class="label">Catégories</div>
    </div>
    <div class="stat-box">
        <div class="number"><?php echo count($prestations) > 0 ? '€' . number_format(array_sum(array_column($prestations, 'price')) / count($prestations), 2) : '0'; ?></div>
        <div class="label">Prix moyen</div>
    </div>
</div>

<div class="search-filters">
    <div class="form-row">
        <div class="form-group">
            <label>Recherche</label>
            <input type="text" id="search-input" placeholder="Rechercher par nom ou description..." onkeyup="filterPrestations()">
        </div>
        <div class="form-group">
            <label>Catégorie</label>
            <select id="category-filter" onchange="filterPrestations()">
                <option value="">Toutes les catégories</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button class="btn" onclick="resetFilters()">
                <i class="fas fa-times"></i> Réinitialiser
            </button>
        </div>
    </div>
</div>

<div class="prestations-grid" id="prestations-grid">
    <?php if (empty($prestations)): ?>
        <div class="empty-state">
            <i class="fas fa-concierge-bell"></i>
            <h3>Aucune prestation</h3>
            <p>Commencez par ajouter votre première prestation.</p>
            <button class="btn btn-success" onclick="openModal('add')">
                <i class="fas fa-plus"></i> Ajouter une prestation
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($prestations as $prestation): ?>
            <div class="prestation-card" data-id="<?php echo $prestation['id']; ?>" data-name="<?php echo htmlspecialchars($prestation['name']); ?>" data-category="<?php echo htmlspecialchars($prestation['category']); ?>">
                <div class="category"><?php echo htmlspecialchars($prestation['category']); ?></div>
                <h3><?php echo htmlspecialchars($prestation['name']); ?></h3>
                <div class="price">€<?php echo number_format($prestation['price'], 2); ?></div>
                <?php if ($prestation['duration']): ?>
                    <div class="duration">
                        <i class="fas fa-clock"></i> <?php echo htmlspecialchars($prestation['duration']); ?>
                    </div>
                <?php endif; ?>
                <div class="description"><?php echo htmlspecialchars($prestation['description']); ?></div>
                <div class="actions">
                    <button class="btn btn-warning" onclick="editPrestation(<?php echo $prestation['id']; ?>)">
                        <i class="fas fa-edit"></i> Modifier
                    </button>
                    <button class="btn btn-danger" onclick="deletePrestation(<?php echo $prestation['id']; ?>, '<?php echo htmlspecialchars($prestation['name']); ?>')">
                        <i class="fas fa-trash"></i> Supprimer
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal pour ajouter/modifier une prestation -->
<div id="prestation-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 id="modal-title">Ajouter une prestation</h3>
            <button class="close" onclick="closeModal()">&times;</button>
        </div>
        <form id="prestation-form">
            <input type="hidden" id="prestation-id" name="id">
            <input type="hidden" id="form-action" name="action" value="add_prestation">
            
            <div class="form-group">
                <label for="name">Nom de la prestation *</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="category">Catégorie *</label>
                <input type="text" id="category" name="category" required>
            </div>
            
            <div class="form-group">
                <label for="price">Prix (€) *</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>
            
            <div class="form-group">
                <label for="duration">Durée (optionnel)</label>
                <input type="text" id="duration" name="duration" placeholder="ex: 1h30, 2 jours, etc.">
            </div>
            
            <div class="form-group">
                <label for="description">Description *</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn" onclick="closeModal()">Annuler</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Variables globales
    let prestationsData = <?php echo json_encode($prestations); ?>;
    
    // Fonction pour ouvrir le modal
    function openModal(action, prestationId = null) {
        const modal = document.getElementById('prestation-modal');
        const title = document.getElementById('modal-title');
        const form = document.getElementById('prestation-form');
        const actionInput = document.getElementById('form-action');
        const idInput = document.getElementById('prestation-id');
        
        form.reset();
        
        if (action === 'add') {
            title.textContent = 'Ajouter une prestation';
            actionInput.value = 'add_prestation';
            idInput.value = '';
        } else if (action === 'edit') {
            title.textContent = 'Modifier la prestation';
            actionInput.value = 'update_prestation';
            idInput.value = prestationId;
            
            // Remplir le formulaire avec les données de la prestation
            const prestation = prestationsData.find(p => p.id == prestationId);
            if (prestation) {
                document.getElementById('name').value = prestation.name;
                document.getElementById('category').value = prestation.category;
                document.getElementById('price').value = prestation.price;
                document.getElementById('duration').value = prestation.duration || '';
                document.getElementById('description').value = prestation.description;
            }
        }
        
        modal.style.display = 'block';
    }
    
    // Fonction pour fermer le modal
    function closeModal() {
        document.getElementById('prestation-modal').style.display = 'none';
    }
    
    // Fonction pour modifier une prestation
    function editPrestation(id) {
        openModal('edit', id);
    }
    
    // Fonction pour supprimer une prestation
    function deletePrestation(id, name) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer la prestation "${name}" ?`)) {
            const formData = new FormData();
            formData.append('action', 'delete_prestation');
            formData.append('id', id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Supprimer la ligne du tableau au lieu de recharger
                    const row = document.querySelector(`tr[data-id="${id}"]`);
                    if (row) row.remove();
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert('Erreur lors de la suppression', 'error');
            });
        }
    }
    
    // Fonction pour afficher les alertes
    function showAlert(message, type) {
        const alertId = type === 'success' ? 'success-alert' : 'error-alert';
        const alert = document.getElementById(alertId);
        alert.textContent = message;
        alert.style.display = 'block';
        
        setTimeout(() => {
            alert.style.display = 'none';
        }, 5000);
    }
    
    // Fonction pour filtrer les prestations
    function filterPrestations() {
        const searchTerm = document.getElementById('search-input').value.toLowerCase();
        const categoryFilter = document.getElementById('category-filter').value;
        const cards = document.querySelectorAll('.prestation-card');
        
        cards.forEach(card => {
            const name = card.dataset.name.toLowerCase();
            const category = card.dataset.category;
            
            const matchesSearch = name.includes(searchTerm);
            const matchesCategory = !categoryFilter || category === categoryFilter;
            
            if (matchesSearch && matchesCategory) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // Fonction pour réinitialiser les filtres
    function resetFilters() {
        document.getElementById('search-input').value = '';
        document.getElementById('category-filter').value = '';
        filterPrestations();
    }
    
    // Gestionnaire de soumission du formulaire
    document.getElementById('prestation-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch('', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                closeModal();
            } else {
                showAlert(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            showAlert('Erreur lors de l\'enregistrement', 'error');
        });
    });
    
    // Fermer le modal en cliquant à l'extérieur
    window.onclick = function(event) {
        const modal = document.getElementById('prestation-modal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>
