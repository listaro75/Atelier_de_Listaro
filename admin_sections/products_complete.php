<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');
include_once(__DIR__ . '/config.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

$response = ['success' => false, 'message' => ''];

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
                                            $image_path = 'uploads/products/' . $newname;
                                            
                                            $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                            $stmt->execute([$product_id, $image_path, $is_main]);
                                        }
                                    }
                                }
                            }
                            
                            $DB->commit();
                            $response['success'] = true;
                            $response['message'] = 'Produit ajouté avec succès';
                        } else {
                            $DB->rollback();
                            $response['message'] = 'Erreur lors de l\'ajout du produit';
                        }
                    } catch (Exception $e) {
                        $DB->rollback();
                        $response['message'] = 'Erreur: ' . $e->getMessage();
                    }
                } else {
                    $response['message'] = 'Aucune image fournie';
                }
                break;
                
            case 'edit_product':
                $product_id = intval($_POST['product_id']);
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category = trim($_POST['category']);
                $stock = intval($_POST['stock']);
                
                try {
                    $DB->beginTransaction();
                    
                    // Mettre à jour le produit
                    $stmt = $DB->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ? WHERE id = ?");
                    if ($stmt->execute([$name, $description, $price, $category, $stock, $product_id])) {
                        
                        // Traiter les nouvelles images si fournies
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
                                    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                    
                                    if (in_array($filetype, $allowed)) {
                                        $newname = uniqid() . '.' . $filetype;
                                        $upload_path = $uploadDir . '/' . $newname;
                                        
                                        if (move_uploaded_file($tmp_name, $upload_path)) {
                                            $is_main = ($i === 0) ? 1 : 0;
                                            $image_path = 'uploads/products/' . $newname;
                                            
                                            // Si c'est la première image, désactiver les autres images principales
                                            if ($is_main) {
                                                $stmt = $DB->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
                                                $stmt->execute([$product_id]);
                                            }
                                            
                                            $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                            $stmt->execute([$product_id, $image_path, $is_main]);
                                        }
                                    }
                                }
                            }
                        }
                        
                        $DB->commit();
                        $response['success'] = true;
                        $response['message'] = 'Produit modifié avec succès';
                    } else {
                        $DB->rollback();
                        $response['message'] = 'Erreur lors de la modification du produit';
                    }
                } catch (Exception $e) {
                    $DB->rollback();
                    $response['message'] = 'Erreur: ' . $e->getMessage();
                }
                break;
                
            case 'delete_product':
                include_once(__DIR__ . '/../_functions/image_utils.php');
                $product_id = intval($_POST['product_id']);
                
                $result = deleteProductWithImages($product_id, $DB);
                $response['success'] = $result['success'];
                $response['message'] = $result['message'];
                break;
                
            case 'delete_image':
                $image_id = intval($_POST['image_id']);
                
                try {
                    // Récupérer l'image
                    $stmt = $DB->prepare("SELECT * FROM product_images WHERE id = ?");
                    $stmt->execute([$image_id]);
                    $image = $stmt->fetch();
                    
                    if ($image) {
                        // Supprimer le fichier
                        $image_file = __DIR__ . '/../' . $image['image_path'];
                        if (file_exists($image_file)) {
                            unlink($image_file);
                        }
                        
                        // Supprimer de la base de données
                        $stmt = $DB->prepare("DELETE FROM product_images WHERE id = ?");
                        if ($stmt->execute([$image_id])) {
                            $response['success'] = true;
                            $response['message'] = 'Image supprimée avec succès';
                        } else {
                            $response['message'] = 'Erreur lors de la suppression de l\'image';
                        }
                    } else {
                        $response['message'] = 'Image non trouvée';
                    }
                } catch (Exception $e) {
                    $response['message'] = 'Erreur: ' . $e->getMessage();
                }
                break;
        }
    }
    
    // Retourner une réponse JSON si c'est une requête AJAX
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
}

// Récupérer les produits
$stmt = $DB->query("SELECT * FROM products ORDER BY created_at DESC");
$products = $stmt->fetchAll();

// Récupérer les catégories uniques
$stmt = $DB->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
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

.products-section .btn-warning {
    background: linear-gradient(135deg, #f39c12 0%, #e67e22 100%);
}

.products-section .btn-warning:hover {
    background: linear-gradient(135deg, #e67e22 0%, #d35400 100%);
}

.products-section .section-actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 15px;
}

.products-section .search-filters {
    display: flex;
    gap: 10px;
    align-items: center;
}

.products-section .search-filters input,
.products-section .search-filters select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.products-section .table-container {
    overflow-x: auto;
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.products-section table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
}

.products-section table th,
.products-section table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.products-section table th {
    background: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.products-section table tr:hover {
    background-color: #f8f9fa;
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

.products-section .form-group {
    margin-bottom: 15px;
}

.products-section .form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}

.products-section .form-group input,
.products-section .form-group select,
.products-section .form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
}

.products-section .form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.products-section .form-actions {
    display: flex;
    gap: 10px;
}

/* Modal styles */
.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: #fefefe;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 600px;
    max-height: 80vh;
    overflow-y: auto;
}

.close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    line-height: 1;
    cursor: pointer;
}

.close:hover {
    color: black;
}

.image-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 10px;
}

.image-item {
    position: relative;
    width: 100px;
    height: 100px;
}

.image-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 5px;
}

.image-item .delete-image {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #e74c3c;
    color: white;
    border: none;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 12px;
    cursor: pointer;
}

.main-image-badge {
    position: absolute;
    bottom: 0;
    left: 0;
    background: #27ae60;
    color: white;
    padding: 2px 6px;
    font-size: 10px;
    border-radius: 0 5px 0 0;
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
    
    .modal-content {
        width: 95%;
        margin: 10% auto;
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
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo htmlspecialchars($category); ?>"><?php echo htmlspecialchars($category); ?></option>
                <?php endforeach; ?>
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
                    <label>Prix (€)</label>
                    <input type="number" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Catégorie</label>
                    <select name="category" required>
                        <option value="">Sélectionner une catégorie</option>
                        <option value="Bijoux">Bijoux</option>
                        <option value="Accessoires">Accessoires</option>
                        <option value="Décoration">Décoration</option>
                        <option value="Maroquinerie">Maroquinerie</option>
                        <option value="Textile">Textile</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" name="stock" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" required></textarea>
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
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><?php echo number_format($product['price'], 2); ?> €</td>
                        <td><?php echo htmlspecialchars($product['category']); ?></td>
                        <td><?php echo $product['stock']; ?></td>
                        <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                        <td>
                            <button class="btn btn-warning" onclick="editProduct(<?php echo $product['id']; ?>)">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="btn btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">
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
        <form id="edit-form" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_product">
            <input type="hidden" id="edit-product-id" name="product_id">
            
            <div class="form-grid">
                <div class="form-group">
                    <label>Nom du produit</label>
                    <input type="text" id="edit-name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Prix (€)</label>
                    <input type="number" id="edit-price" name="price" step="0.01" min="0" required>
                </div>
                
                <div class="form-group">
                    <label>Catégorie</label>
                    <select id="edit-category" name="category" required>
                        <option value="">Sélectionner une catégorie</option>
                        <option value="Bijoux">Bijoux</option>
                        <option value="Accessoires">Accessoires</option>
                        <option value="Décoration">Décoration</option>
                        <option value="Maroquinerie">Maroquinerie</option>
                        <option value="Textile">Textile</option>
                        <option value="Autre">Autre</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Stock</label>
                    <input type="number" id="edit-stock" name="stock" min="0" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Description</label>
                <textarea id="edit-description" name="description" required></textarea>
            </div>
            
            <div class="form-group">
                <label>Images actuelles</label>
                <div id="current-images" class="image-preview"></div>
            </div>
            
            <div class="form-group">
                <label>Ajouter de nouvelles images</label>
                <input type="file" name="images[]" multiple accept="image/*">
                <small>Formats acceptés : JPG, PNG, GIF.</small>
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">Modifier</button>
                <button type="button" class="btn" onclick="closeEditModal()">Annuler</button>
            </div>
        </form>
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
    
    // Récupérer les données du produit
    fetch(`admin_sections/get_product.php?id=${id}`)
        .then(response => response.json())
        .then(product => {
            if (product.error) {
                alert('Erreur: ' + product.error);
                return;
            }
            
            // Remplir le formulaire d'édition
            document.getElementById('edit-product-id').value = product.id;
            document.getElementById('edit-name').value = product.name;
            document.getElementById('edit-price').value = product.price;
            document.getElementById('edit-category').value = product.category;
            document.getElementById('edit-stock').value = product.stock;
            document.getElementById('edit-description').value = product.description;
            
            // Afficher les images actuelles
            const currentImagesDiv = document.getElementById('current-images');
            currentImagesDiv.innerHTML = '';
            
            if (product.images && product.images.length > 0) {
                product.images.forEach(image => {
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'image-item';
                    imageDiv.innerHTML = `
                        <img src="${image.image_path}" alt="Image produit">
                        <button type="button" class="delete-image" onclick="deleteImage(${image.id})">×</button>
                        ${image.is_main ? '<span class="main-image-badge">Principal</span>' : ''}
                    `;
                    currentImagesDiv.appendChild(imageDiv);
                });
            }
            
            // Afficher le modal
            document.getElementById('edit-modal').style.display = 'block';
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la récupération des données du produit');
        });
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
}

function deleteImage(imageId) {
    if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
        const formData = new FormData();
        formData.append('action', 'delete_image');
        formData.append('image_id', imageId);

        fetch('admin_sections/products.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Recharger les images du produit
                const productId = document.getElementById('edit-product-id').value;
                editProduct(productId);
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors de la suppression de l\'image');
        });
    }
}

function deleteProduct(id) {
    console.log('Suppression du produit ID:', id);
    if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
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
                alert('Produit supprimé avec succès');
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

// Gestionnaire pour le formulaire d'édition
document.getElementById('edit-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'edit_product');
    
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
        alert('Erreur lors de la modification');
    });
});

// Fermer le modal en cliquant en dehors
window.onclick = function(event) {
    const modal = document.getElementById('edit-modal');
    if (event.target === modal) {
        closeEditModal();
    }
}

// Test des fonctions au chargement
console.log('Section produits complète chargée');
console.log('Nombre de produits affichés:', document.querySelectorAll('#products-table tbody tr').length);
</script>
