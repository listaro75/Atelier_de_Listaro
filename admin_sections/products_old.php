<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');
include_once(__DIR__ . '/config.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_product':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category = trim($_POST['category']);
                $stock = intval($_POST['stock']);
                
                if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                    try {
                        $DB->beginTransaction();
                        
                        // Insérer le produit
                        $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
                        if ($stmt->execute([$name, $description, $price, $category, $stock])) {
                            $product_id = $DB->lastInsertId();
                            
                            // Traiter les images
                            $uploadDir = __DIR__ . '/../uploads/products';
                            if (!is_dir($uploadDir)) {
                                mkdir($uploadDir, 0777, true);
                            }
                            
                            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                                if ($_FILES['images']['error'][$i] === 0) {
                                    $filename = $_FILES['images']['name'][$i];
                                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                                    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                    
                                    if (in_array($filetype, $allowed)) {
                                        $newname = uniqid() . '.' . $filetype;
                                        $upload_path = $uploadDir . '/' . $newname;
                                        
                                        if (move_uploaded_file($tmp_name, $upload_path)) {
                                            $is_main = ($i === 0) ? 1 : 0;
                                            $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                            $stmt->execute([$product_id, 'uploads/products/' . $newname, $is_main]);
                                        }
                                    }
                                }
                            }
                            
                            $DB->commit();
                            echo '<div class="alert alert-success">Produit ajouté avec succès !</div>';
                        }
                    } catch (Exception $e) {
                        $DB->rollback();
                        echo '<div class="alert alert-danger">Erreur lors de l\'ajout : ' . $e->getMessage() . '</div>';
                    }
                } else {
                    echo '<div class="alert alert-danger">Au moins une image est requise.</div>';
                }
                break;
                
            case 'delete_product':
                if (isset($_POST['product_id'])) {
                    try {
                        $DB->beginTransaction();
                        
                        // Supprimer les images
                        $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
                        $stmt->execute([$_POST['product_id']]);
                        $images = $stmt->fetchAll();
                        
                        foreach ($images as $image) {
                            $imagePath = __DIR__ . '/../' . $image['image_path'];
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }
                        
                        // Supprimer de la base
                        $stmt = $DB->prepare("DELETE FROM products WHERE id = ?");
                        $stmt->execute([$_POST['product_id']]);
                        
                        $DB->commit();
                        echo json_encode(['success' => true, 'message' => 'Produit supprimé avec succès']);
                        exit();
                    } catch (Exception $e) {
                        $DB->rollback();
                        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
                        exit();
                    }
                }
                break;
        }
    }
}

// Récupérer les produits
$stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();
?>

<!-- Styles CSS intégrés pour fonctionner en AJAX -->
<style>
.products-section {
    font-family: Arial, sans-serif;
}

.products-section .btn {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    margin: 2px;
}

.products-section .btn:hover {
    background: linear-gradient(135deg, #2980b9 0%, #1f5f8b 100%);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
}

.products-section .btn-success {
    background: linear-gradient(135deg, #27ae60 0%, #1e8449 100%);
}

.products-section .btn-success:hover {
    background: linear-gradient(135deg, #1e8449 0%, #145a32 100%);
}

.products-section .btn-danger {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
}

.products-section .btn-danger:hover {
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
}

.products-section .section-actions {
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}

.products-section .form-group {
    margin-bottom: 15px;
}

.products-section .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
    color: #2c3e50;
}

.products-section .form-group input, 
.products-section .form-group select, 
.products-section .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 2px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.products-section .form-group input:focus, 
.products-section .form-group select:focus, 
.products-section .form-group textarea:focus {
    outline: none;
    border-color: #3498db;
}

.products-section .alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
    font-weight: bold;
}

.products-section .alert-success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.products-section .alert-danger {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.products-section .table-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.products-section table {
    width: 100%;
    border-collapse: collapse;
}

.products-section th, 
.products-section td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.products-section th {
    background: #f8f9fa;
    font-weight: bold;
    color: #2c3e50;
}

.products-section tr:hover {
    background: #f8f9fa;
}

.products-section .search-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.products-section .search-filters input,
.products-section .search-filters select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 200px;
}

.products-section #add-product-form {
    display: none;
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.products-section .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.products-section .form-actions {
    display: flex;
    gap: 10px;
}

@media (max-width: 768px) {
    .products-section .section-actions {
        flex-direction: column;
        gap: 15px;
    }
    
    .products-section .search-filters {
        width: 100%;
        justify-content: center;
    }
    
    .products-section .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="products-section">
    <div class="section-actions">
        <div>
            <button class="btn btn-success" onclick="showAddProductForm()">
                <i class="fas fa-plus"></i>
                Ajouter un produit
            </button>
            <button class="btn" onclick="window.open('admin_sections/debug_products.php', '_blank')">
                <i class="fas fa-wrench"></i>
                Diagnostic
            </button>
            <button class="btn" onclick="alert('Export non disponible pour le moment')" disabled style="opacity: 0.5; cursor: not-allowed;">
                <i class="fas fa-download"></i>
                Exporter
            </button>
        </div>
        <div class="search-filters">
            <input type="text" id="search-products" placeholder="Rechercher un produit..." onkeyup="filterProducts()">
            <select id="filter-category" onchange="filterProducts()">
                <option value="">Toutes les catégories</option>
                <option value="Lampes">Lampes</option>
                <option value="Décoration">Décoration</option>
                <option value="Mobilier">Mobilier</option>
                <option value="Accessoires">Accessoires</option>
            </select>
        </div>
    </div>

    <!-- Formulaire d'ajout de produit -->
    <div id="add-product-form">
        <h3>Ajouter un nouveau produit</h3>
        <form method="POST" enctype="multipart/form-data" onsubmit="return validateAddForm()">
            <input type="hidden" name="action" value="add_product">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du produit</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Prix</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="4" required></textarea>
            </div>
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" required>
                        <option value="">Sélectionner une catégorie</option>
                        <option value="Lampes">Lampes</option>
                        <option value="Décoration">Décoration</option>
                        <option value="Mobilier">Mobilier</option>
                        <option value="Accessoires">Accessoires</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Images (maximum 6)</label>
                <input type="file" name="images[]" multiple accept="image/*" required>
                <small>Formats acceptés : JPG, PNG, GIF. La première image sera l'image principale.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Ajouter</button>
                <button type="button" class="btn" onclick="hideAddProductForm()">Annuler</button>
            </div>
        </form>
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
                    $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1");
                    $stmt->execute([$product['id']]);
                    $mainImage = $stmt->fetch();
                    ?>
                    <tr data-category="<?php echo htmlspecialchars($product['category']); ?>">
                        <td><?php echo $product['id']; ?></td>
                        <td>
                            <?php if ($mainImage): ?>
                                <img src="<?php echo htmlspecialchars($mainImage['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                            <br>
                            <small style="color: #666;">
                                <?php echo htmlspecialchars(substr($product['description'], 0, 50)); ?>...
                            </small>
                        </td>
                        <td>
                            <strong style="color: #27ae60;">
                                <?php echo number_format($product['price'], 2); ?> €
                            </strong>
                        </td>
                        <td>
                            <span style="background: #3498db; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                <?php echo htmlspecialchars($product['category']); ?>
                            </span>
                        </td>
                        <td>
                            <span style="background: <?php echo $product['stock'] > 0 ? '#27ae60' : '#e74c3c'; ?>; color: white; padding: 3px 8px; border-radius: 12px; font-size: 12px;">
                                <?php echo $product['stock']; ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('d/m/Y H:i', strtotime($product['created_at'])); ?>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <button class="btn" style="padding: 5px 10px; font-size: 12px;" 
                                        onclick="editProduct(<?php echo $product['id']; ?>)" 
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-danger" style="padding: 5px 10px; font-size: 12px;" 
                                        onclick="deleteProduct(<?php echo $product['id']; ?>)" 
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Fonctions pour la gestion des produits
function showAddProductForm() {
    console.log('Affichage du formulaire d\'ajout');
    document.getElementById('add-product-form').style.display = 'block';
}

function hideAddProductForm() {
    console.log('Masquage du formulaire d\'ajout');
    document.getElementById('add-product-form').style.display = 'none';
}

function validateAddForm() {
    const name = document.querySelector('input[name="name"]').value;
    const price = document.querySelector('input[name="price"]').value;
    const category = document.querySelector('select[name="category"]').value;
    const images = document.querySelector('input[name="images[]"]').files;
    
    if (!name || !price || !category || images.length === 0) {
        alert('Veuillez remplir tous les champs et ajouter au moins une image.');
        return false;
    }
    
    return true;
}

function editProduct(id) {
    console.log('Édition du produit ID:', id);
    alert('Fonction d\'édition en cours de développement pour le produit ID: ' + id);
}

function deleteProduct(id) {
    console.log('Suppression du produit ID:', id);
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
        const formData = new FormData();
        formData.append('action', 'delete_product');
        formData.append('product_id', id);

        fetch('admin_sections/products.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression');
        });
    }
}

function filterProducts() {
    const searchTerm = document.getElementById('search-products').value.toLowerCase();
    const categoryFilter = document.getElementById('filter-category').value;
    const table = document.getElementById('products-table');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) {
        const row = rows[i];
        const name = row.cells[2].textContent.toLowerCase();
        const category = row.getAttribute('data-category');
        
        const matchesSearch = name.includes(searchTerm);
        const matchesCategory = !categoryFilter || category === categoryFilter;
        
        if (matchesSearch && matchesCategory) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}

// Test des fonctions au chargement
console.log('Section produits chargée');
console.log('Nombre de produits affichés:', document.querySelectorAll('#products-table tbody tr').length);
</script>
