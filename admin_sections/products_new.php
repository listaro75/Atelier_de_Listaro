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
            case 'add_product':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category = trim($_POST['category']);
                $stock = intval($_POST['stock']);
                
                $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$name, $description, $price, $category, $stock])) {
                    $product_id = $DB->lastInsertId();
                    
                    // Traiter les images si présentes
                    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                        $uploadDir = __DIR__ . '/../uploads/products';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                            if ($_FILES['images']['error'][$i] === 0) {
                                $filename = $_FILES['images']['name'][$i];
                                $tmp_name = $_FILES['images']['tmp_name'][$i];
                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                
                                if (in_array($ext, $allowed)) {
                                    $newname = uniqid() . '.' . $ext;
                                    $upload_path = $uploadDir . '/' . $newname;
                                    
                                    if (move_uploaded_file($tmp_name, $upload_path)) {
                                        $is_main = ($i === 0) ? 1 : 0;
                                        $image_path = 'uploads/products/' . $newname;
                                        
                                        $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                        $stmt->execute([$product_id, $image_path, $is_main]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Produit ajouté avec succès';
                } else {
                    $response['message'] = 'Erreur lors de l\'ajout du produit';
                }
                break;
                
            case 'delete_product':
                $product_id = intval($_POST['product_id']);
                
                // Supprimer les images
                $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
                $stmt->execute([$product_id]);
                $images = $stmt->fetchAll();
                
                foreach ($images as $image) {
                    $file_path = __DIR__ . '/../' . $image['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
                
                $stmt = $DB->prepare("DELETE FROM product_images WHERE product_id = ?");
                $stmt->execute([$product_id]);
                
                $stmt = $DB->prepare("DELETE FROM products WHERE id = ?");
                if ($stmt->execute([$product_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Produit supprimé avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la suppression';
                }
                break;
                
            case 'edit_product':
                $product_id = intval($_POST['product_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category = trim($_POST['category']);
                $stock = intval($_POST['stock']);
                
                $stmt = $DB->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $price, $category, $stock, $product_id])) {
                    $response['success'] = true;
                    $response['message'] = 'Produit modifié avec succès';
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

// Récupérer les produits
try {
    $stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC");
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $products = [];
}

// Récupérer les catégories
try {
    $stmt = $DB->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
}
?>

<div class="products-section">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Gestion des produits</h2>
        <button class="btn btn-success" onclick="showAddForm()">
            <i class="fas fa-plus"></i> Ajouter un produit
        </button>
    </div>
    
    <!-- Formulaire d'ajout (masqué par défaut) -->
    <div id="add-product-form" style="display: none; background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h3>Ajouter un nouveau produit</h3>
        <form id="product-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_product">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du produit *</label>
                    <input type="text" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Prix (€) *</label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Catégorie *</label>
                    <select name="category" required>
                        <option value="">Choisir une catégorie</option>
                        <option value="Bijoux">Bijoux</option>
                        <option value="Accessoires">Accessoires</option>
                        <option value="Décoration">Décoration</option>
                        <option value="Maroquinerie">Maroquinerie</option>
                        <option value="Textile">Textile</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" name="stock" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description *</label>
                <textarea name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Images</label>
                <input type="file" name="images[]" multiple accept="image/*">
                <small>Formats acceptés : JPG, PNG, GIF. La première image sera l'image principale.</small>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Ajouter
                </button>
                <button type="button" class="btn" onclick="hideAddForm()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
    
    <!-- Filtres -->
    <div style="display: flex; gap: 15px; margin-bottom: 20px; align-items: center;">
        <input type="text" id="search-input" placeholder="Rechercher un produit..." 
               style="padding: 8px; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
        <select id="category-filter" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <option value="">Toutes les catégories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn" onclick="filterProducts()">
            <i class="fas fa-filter"></i> Filtrer
        </button>
    </div>
    
    <!-- Liste des produits -->
    <div class="table-container">
        <table id="products-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Nom</th>
                    <th>Prix</th>
                    <th>Catégorie</th>
                    <th>Stock</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <?php
                    // Récupérer l'image principale
                    $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
                    $stmt->execute([$product['id']]);
                    $main_image = $stmt->fetchColumn();
                    ?>
                    <tr data-category="<?php echo htmlspecialchars($product['category']); ?>" 
                        data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>">
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <?php if ($main_image): ?>
                                <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo number_format($product['price'], 2); ?> €</td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal d'édition -->
<div id="edit-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h2>Modifier le produit</h2>
        <form id="edit-form" method="POST">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" id="edit-product-id" name="product_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du produit *</label>
                    <input type="text" id="edit-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Prix (€) *</label>
                    <input type="number" id="edit-price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Catégorie *</label>
                    <select id="edit-category" name="category" required>
                        <option value="">Choisir une catégorie</option>
                        <option value="Bijoux">Bijoux</option>
                        <option value="Accessoires">Accessoires</option>
                        <option value="Décoration">Décoration</option>
                        <option value="Maroquinerie">Maroquinerie</option>
                        <option value="Textile">Textile</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Stock *</label>
                    <input type="number" id="edit-stock" name="stock" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description *</label>
                <textarea id="edit-description" name="description" required></textarea>
            </div>
            
            <div style="display: flex; gap: 10px;">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Modifier
                </button>
                <button type="button" class="btn" onclick="closeEditModal()">
                    <i class="fas fa-times"></i> Annuler
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Variables globales pour les produits
let productsData = <?php echo json_encode($products); ?>;

// Afficher le formulaire d'ajout
function showAddForm() {
    document.getElementById('add-product-form').style.display = 'block';
}

// Masquer le formulaire d'ajout
function hideAddForm() {
    document.getElementById('add-product-form').style.display = 'none';
    document.getElementById('product-form').reset();
}

// Filtrer les produits
function filterProducts() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const categoryFilter = document.getElementById('category-filter').value;
    const table = document.getElementById('products-table');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const name = row.getAttribute('data-name');
        const category = row.getAttribute('data-category');
        
        const matchesSearch = !searchTerm || name.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        
        if (matchesSearch && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Filtrer en temps réel
document.getElementById('search-input').addEventListener('input', filterProducts);
document.getElementById('category-filter').addEventListener('change', filterProducts);

// Modifier un produit
function editProduct(id) {
    // Trouver le produit dans les données
    const product = productsData.find(p => p.id == id);
    if (!product) {
        alert('Produit non trouvé');
        return;
    }
    
    // Remplir le formulaire
    document.getElementById('edit-product-id').value = product.id;
    document.getElementById('edit-name').value = product.name;
    document.getElementById('edit-price').value = product.price;
    document.getElementById('edit-category').value = product.category;
    document.getElementById('edit-stock').value = product.stock;
    document.getElementById('edit-description').value = product.description;
    
    // Afficher le modal
    document.getElementById('edit-modal').style.display = 'block';
}

// Fermer le modal d'édition
function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

// Supprimer un produit
function deleteProduct(id) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete_product');
    formData.append('product_id', id);
    
    fetch('admin_sections/products.php', {
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

// Gestion du formulaire d'ajout
document.getElementById('product-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('admin_sections/products.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produit ajouté avec succès');
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
});

// Gestion du formulaire d'édition
document.getElementById('edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('admin_sections/products.php', {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Produit modifié avec succès');
            closeEditModal();
            location.reload();
        } else {
            alert('Erreur: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Erreur:', error);
        alert('Une erreur est survenue');
    });
});

// Fermer le modal en cliquant à l'extérieur
window.onclick = function(event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) {
        closeEditModal();
    }
}

console.log('Section produits chargée avec', productsData.length, 'produits');
</script>
