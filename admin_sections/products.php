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
                        // Vérifier le nombre maximum d'images (5 maximum)
                        $image_count = count($_FILES['images']['name']);
                        if ($image_count > 5) {
                            $response['message'] = 'Vous ne pouvez ajouter que 5 images maximum par produit';
                            break;
                        }
                        
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
                include_once(__DIR__ . '/../_functions/image_utils.php');
                $product_id = intval($_POST['product_id']);
                
                $result = deleteProductWithImages($product_id, $DB);
                $response['success'] = $result['success'];
                $response['message'] = $result['message'];
                
                if ($result['success']) {
                    error_log("🗑️ Produit $product_id supprimé avec {$result['total_images']} images");
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
                    // Traiter les nouvelles images si présentes
                    if (isset($_FILES['new_images']) && !empty($_FILES['new_images']['name'][0])) {
                        // Vérifier le nombre total d'images (existantes + nouvelles) <= 5
                        $stmt_count = $DB->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
                        $stmt_count->execute([$product_id]);
                        $existing_images = $stmt_count->fetchColumn();
                        
                        $new_images_count = count($_FILES['new_images']['name']);
                        $total_images = $existing_images + $new_images_count;
                        
                        if ($total_images > 5) {
                            $response['message'] = "Vous ne pouvez avoir que 5 images maximum par produit. Vous avez déjà $existing_images images. Vous pouvez ajouter au maximum " . (5 - $existing_images) . " nouvelles images.";
                            break;
                        }
                        
                        $uploadDir = __DIR__ . '/../uploads/products';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        
                        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                        for ($i = 0; $i < count($_FILES['new_images']['name']); $i++) {
                            if ($_FILES['new_images']['error'][$i] === 0) {
                                $filename = $_FILES['new_images']['name'][$i];
                                $tmp_name = $_FILES['new_images']['tmp_name'][$i];
                                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                                
                                if (in_array($ext, $allowed)) {
                                    $newname = uniqid() . '.' . $ext;
                                    $upload_path = $uploadDir . '/' . $newname;
                                    
                                    if (move_uploaded_file($tmp_name, $upload_path)) {
                                        $image_path = 'uploads/products/' . $newname;
                                        
                                        // Vérifier s'il y a déjà une image principale
                                        $stmt_check = $DB->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ? AND is_main = 1");
                                        $stmt_check->execute([$product_id]);
                                        $has_main = $stmt_check->fetchColumn() > 0;
                                        
                                        $is_main = (!$has_main && $i === 0) ? 1 : 0;
                                        
                                        $stmt_img = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                        $stmt_img->execute([$product_id, $image_path, $is_main]);
                                    }
                                }
                            }
                        }
                    }
                    
                    $response['success'] = true;
                    $response['message'] = 'Produit modifié avec succès';
                } else {
                    $response['message'] = 'Erreur lors de la modification';
                }
                break;
                
            case 'delete_image':
                $image_id = intval($_POST['image_id']);
                
                // Récupérer les infos de l'image
                $stmt = $DB->prepare("SELECT image_path, product_id FROM product_images WHERE id = ?");
                $stmt->execute([$image_id]);
                $image = $stmt->fetch();
                
                if ($image) {
                    // Supprimer le fichier
                    $file_path = __DIR__ . '/../' . $image['image_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                    
                    // Supprimer de la base
                    $stmt = $DB->prepare("DELETE FROM product_images WHERE id = ?");
                    if ($stmt->execute([$image_id])) {
                        $response['success'] = true;
                        $response['message'] = 'Image supprimée avec succès';
                    } else {
                        $response['message'] = 'Erreur lors de la suppression';
                    }
                } else {
                    $response['message'] = 'Image non trouvée';
                }
                break;
                
            case 'set_main_image':
                $image_id = intval($_POST['image_id']);
                
                // Récupérer le product_id
                $stmt = $DB->prepare("SELECT product_id FROM product_images WHERE id = ?");
                $stmt->execute([$image_id]);
                $product_id = $stmt->fetchColumn();
                
                if ($product_id) {
                    // Désactiver toutes les images principales pour ce produit
                    $stmt = $DB->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
                    $stmt->execute([$product_id]);
                    
                    // Activer cette image comme principale
                    $stmt = $DB->prepare("UPDATE product_images SET is_main = 1 WHERE id = ?");
                    if ($stmt->execute([$image_id])) {
                        $response['success'] = true;
                        $response['message'] = 'Image principale définie avec succès';
                    } else {
                        $response['message'] = 'Erreur lors de la définition de l\'image principale';
                    }
                } else {
                    $response['message'] = 'Image non trouvée';
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

<style>
    /* Styles pour la prévisualisation des images */
    .image-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
        padding: 10px;
        border: 2px dashed #ddd;
        border-radius: 8px;
        min-height: 60px;
        background: #fafafa;
    }
    
    .image-item {
        position: relative;
        width: 100px;
        height: 100px;
        border: 2px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .image-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .main-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        background: #28a745;
        color: white;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 10px;
        font-weight: bold;
    }
    
    .delete-btn {
        position: absolute;
        top: -5px;
        right: -5px;
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .delete-btn:hover {
        background: #c82333;
    }
    
    /* Amélioration du champ de sélection de fichiers */
    input[type="file"][multiple] {
        padding: 8px;
        border: 2px dashed #667eea;
        border-radius: 8px;
        background: #f8f9ff;
        width: 100%;
        cursor: pointer;
    }
    
    input[type="file"][multiple]:hover {
        border-color: #5a6fd8;
        background: #f0f4ff;
    }
    
    /* Message d'aide pour les images multiples */
    .image-help {
        background: #e7f3ff;
        border: 1px solid #0084ff;
        border-radius: 6px;
        padding: 12px;
        margin-top: 8px;
        font-size: 13px;
        color: #0066cc;
        line-height: 1.4;
    }
    
    .image-help strong {
        color: #004499;
    }
    
    /* Style pour le bouton de sélection de fichiers */
    .file-input-wrapper {
        position: relative;
        display: inline-block;
        cursor: pointer;
        width: 100%;
    }
    
    .file-input-custom {
        position: relative;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: center;
        font-weight: bold;
        margin-bottom: 8px;
        transition: all 0.3s ease;
    }
    
    .file-input-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }
    
    .file-input-custom i {
        margin-right: 8px;
    }
</style>

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
                <label>Images * (Sélection multiple possible)</label>
                <div class="file-input-wrapper">
                    <button type="button" class="file-input-custom" onclick="document.getElementById('product-images').click()">
                        <i class="fas fa-images"></i> Sélectionner plusieurs images (Ctrl+clic)
                    </button>
                    <input type="file" id="product-images" name="images[]" multiple accept="image/*" onchange="previewImages(this)" required style="display: none;">
                </div>
                <div class="image-help">
                    <strong>📸 Comment sélectionner plusieurs images :</strong><br>
                    1. Cliquez sur le bouton ci-dessus<br>
                    2. Dans la fenêtre qui s'ouvre, maintenez <strong>Ctrl</strong> (Windows) ou <strong>Cmd</strong> (Mac)<br>
                    3. Cliquez sur chaque image que vous voulez ajouter<br>
                    4. Cliquez sur "Ouvrir" pour confirmer<br>
                    <br>
                    <strong>Règles :</strong> Formats JPG, PNG, GIF • Maximum 5 images • La première sera l'image principale
                </div>
                <div id="image-preview" class="image-preview"></div>
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
                    // Récupérer l'image principale et le nombre total d'images
                    $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1 LIMIT 1");
                    $stmt->execute([$product['id']]);
                    $main_image = $stmt->fetchColumn();
                    
                    $stmt = $DB->prepare("SELECT COUNT(*) FROM product_images WHERE product_id = ?");
                    $stmt->execute([$product['id']]);
                    $image_count = $stmt->fetchColumn();
                    ?>
                    <tr data-category="<?php echo htmlspecialchars($product['category']); ?>" 
                        data-name="<?php echo htmlspecialchars(strtolower($product['name'])); ?>">
                        <td><?php echo $product['id']; ?></td>
                        <td style="position: relative;">
                            <?php if ($main_image): ?>
                                <img src="<?php echo htmlspecialchars($main_image); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                                     style="width: 50px; height: 50px; object-fit: cover; border-radius: 4px;">
                                <?php if ($image_count > 1): ?>
                                    <div style="position: absolute; top: -5px; right: -5px; background: #3498db; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                        <?php echo $image_count; ?>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div style="width: 50px; height: 50px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center; position: relative;">
                                    <i class="fas fa-image" style="color: #ccc;"></i>
                                    <?php if ($image_count > 0): ?>
                                        <div style="position: absolute; top: -5px; right: -5px; background: #3498db; color: white; border-radius: 50%; width: 20px; height: 20px; font-size: 11px; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                                            <?php echo $image_count; ?>
                                        </div>
                                    <?php endif; ?>
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
            
            <div class="form-group">
                <label>Images actuelles</label>
                <div id="current-images" class="image-preview"></div>
            </div>
            
            <div class="form-group">
                <label>Ajouter de nouvelles images</label>
                <input type="file" id="edit-images" name="new_images[]" multiple accept="image/*" onchange="previewEditImages(this)">
                <small>Formats acceptés : JPG, PNG, GIF. <strong>Maximum 5 images par produit au total.</strong> Les nouvelles images seront ajoutées aux existantes.</small>
                <div id="edit-image-preview" class="image-preview"></div>
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
    
    // Charger les images existantes
    loadCurrentImages(product.id);
    
    // Réinitialiser l'aperçu des nouvelles images
    document.getElementById('edit-image-preview').innerHTML = '';
    document.getElementById('edit-images').value = '';
    
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

// Fonction pour prévisualiser les images lors de l'ajout
function previewImages(input) {
    const preview = document.getElementById('image-preview');
    preview.innerHTML = '';
    
    if (input.files && input.files.length > 0) {
        // Vérifier le nombre maximum d'images (5 maximum)
        if (input.files.length > 5) {
            alert('⚠️ Vous ne pouvez sélectionner que 5 images maximum par produit.\n\nVeuillez sélectionner moins d\'images.');
            input.value = ''; // Réinitialiser la sélection
            return;
        }
        
        // Afficher un message de confirmation
        const fileCount = input.files.length;
        const helpDiv = document.querySelector('.image-help');
        helpDiv.innerHTML = `
            <strong>✅ ${fileCount} image${fileCount > 1 ? 's' : ''} sélectionnée${fileCount > 1 ? 's' : ''}</strong><br>
            • La première image sera l'image principale<br>
            • Vous pouvez supprimer des images avec le bouton ❌<br>
            • Maximum 5 images par produit
        `;
        
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'image-item';
                    imageDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}">
                        ${index === 0 ? '<div class="main-badge">Principal</div>' : ''}
                        <button type="button" class="delete-btn" onclick="removePreviewImage(this, ${index})" title="Supprimer cette image">&times;</button>
                    `;
                    preview.appendChild(imageDiv);
                };
                reader.readAsDataURL(file);
            }
        });
    } else {
        // Remettre le message d'aide original si aucune image
        const helpDiv = document.querySelector('.image-help');
        helpDiv.innerHTML = `
            <strong>📸 Comment sélectionner plusieurs images :</strong><br>
            1. Cliquez sur le bouton ci-dessus<br>
            2. Dans la fenêtre qui s'ouvre, maintenez <strong>Ctrl</strong> (Windows) ou <strong>Cmd</strong> (Mac)<br>
            3. Cliquez sur chaque image que vous voulez ajouter<br>
            4. Cliquez sur "Ouvrir" pour confirmer<br>
            <br>
            <strong>Règles :</strong> Formats JPG, PNG, GIF • Maximum 5 images • La première sera l'image principale
        `;
    }
}

// Fonction pour supprimer une image de l'aperçu
function removePreviewImage(btn, index) {
    const input = document.getElementById('product-images');
    const files = Array.from(input.files);
    files.splice(index, 1);
    
    // Recréer l'input file avec les fichiers restants
    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    // Rafraîchir l'aperçu
    previewImages(input);
}

// Fonction pour prévisualiser les nouvelles images lors de l'édition
function previewEditImages(input) {
    const preview = document.getElementById('edit-image-preview');
    preview.innerHTML = '';
    
    if (input.files) {
        // Compter les images existantes
        const currentImages = document.getElementById('current-images');
        const existingImagesCount = currentImages ? currentImages.children.length : 0;
        
        // Vérifier le total d'images (existantes + nouvelles) <= 5
        const totalImages = existingImagesCount + input.files.length;
        if (totalImages > 5) {
            const maxNewImages = 5 - existingImagesCount;
            alert(`Vous ne pouvez avoir que 5 images maximum par produit. Vous avez déjà ${existingImagesCount} images. Vous pouvez ajouter au maximum ${maxNewImages} nouvelles images.`);
            input.value = ''; // Réinitialiser la sélection
            return;
        }
        
        Array.from(input.files).forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const imageDiv = document.createElement('div');
                    imageDiv.className = 'image-item';
                    imageDiv.innerHTML = `
                        <img src="${e.target.result}" alt="Preview">
                        <button type="button" class="delete-btn" onclick="removeEditPreviewImage(this, ${index})">&times;</button>
                    `;
                    preview.appendChild(imageDiv);
                };
                reader.readAsDataURL(file);
            }
        });
    }
}

// Fonction pour supprimer une nouvelle image de l'aperçu lors de l'édition
function removeEditPreviewImage(btn, index) {
    const input = document.getElementById('edit-images');
    const files = Array.from(input.files);
    files.splice(index, 1);
    
    const dt = new DataTransfer();
    files.forEach(file => dt.items.add(file));
    input.files = dt.files;
    
    previewEditImages(input);
}

// Fonction pour charger les images existantes d'un produit
function loadCurrentImages(productId) {
    fetch(`admin_sections/get_product_images.php?id=${productId}`)
        .then(response => response.json())
        .then(images => {
            const container = document.getElementById('current-images');
            container.innerHTML = '';
            
            images.forEach(image => {
                const imageDiv = document.createElement('div');
                imageDiv.className = 'image-item';
                imageDiv.innerHTML = `
                    <img src="${image.image_path}" alt="Image produit">
                    ${image.is_main == 1 ? '<div class="main-badge">Principal</div>' : ''}
                    <button type="button" class="delete-btn" onclick="deleteProductImage(${image.id})">&times;</button>
                    ${image.is_main != 1 ? `<button type="button" class="btn btn-sm" onclick="setMainImage(${image.id})" style="position: absolute; bottom: 5px; left: 5px; padding: 2px 6px; font-size: 10px;">Principal</button>` : ''}
                `;
                container.appendChild(imageDiv);
            });
        })
        .catch(error => {
            console.error('Erreur lors du chargement des images:', error);
        });
}

// Fonction pour supprimer une image existante
function deleteProductImage(imageId) {
    if (!confirm('Supprimer cette image ?')) return;
    
    const formData = new FormData();
    formData.append('action', 'delete_image');
    formData.append('image_id', imageId);
    
    fetch('admin_sections/products.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const productId = document.getElementById('edit-product-id').value;
            loadCurrentImages(productId);
        } else {
            alert('Erreur: ' + data.message);
        }
    });
}

// Fonction pour définir l'image principale
function setMainImage(imageId) {
    const formData = new FormData();
    formData.append('action', 'set_main_image');
    formData.append('image_id', imageId);
    
    fetch('admin_sections/products.php', {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const productId = document.getElementById('edit-product-id').value;
            loadCurrentImages(productId);
        } else {
            alert('Erreur: ' + data.message);
        }
    });
}
</script>
