<?php
    session_start();
    include_once('_db/connexion_DB.php');
    include_once('_functions/auth.php');

    // Debug des uploads
    error_log("Document Root: " . $_SERVER['DOCUMENT_ROOT']);
    error_log("Current Directory: " . __DIR__);
    error_log("Upload Max Filesize: " . ini_get('upload_max_filesize'));
    error_log("Post Max Size: " . ini_get('post_max_size'));

    // Vérifier si l'utilisateur est admin
    if (!is_admin()) {
        // Debug pour comprendre le problème
        error_log("Accès admin refusé - Session: " . print_r($_SESSION, true));
        error_log("Fonction is_admin() retourne: " . (is_admin() ? 'true' : 'false'));
        
        // Redirection avec message d'erreur
        header('Location: test_admin.php');
        exit();
    }

    // Définir les chemins
    $uploadDir = 'uploads';
    $productsDir = 'uploads/products';

    // Debug des permissions
    error_log("Permissions uploads: " . substr(sprintf('%o', fileperms($uploadDir)), -4));
    error_log("Permissions products: " . substr(sprintf('%o', fileperms($productsDir)), -4));

    // Debug des données POST et FILES
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        error_log('POST Data: ' . print_r($_POST, true));
        error_log('FILES Data: ' . print_r($_FILES, true));
    }

    // Traitement de l'ajout/modification de produit
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category = trim($_POST['category']);
                $stock = intval($_POST['stock']);
                
                // Traitement de l'image
                if (isset($_FILES['images'])) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    // Debug
                    error_log("Fichiers reçus : " . print_r($_FILES['images'], true));

                    // Vérifier qu'au moins une image a été uploadée
                    if (empty($_FILES['images']['name'][0])) {
                        $error_msg = "Au moins une image est requise.";
                    } else {
                        try {
                            $DB->beginTransaction();

                            // Insérer d'abord le produit
                            $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
                            
                            if ($stmt->execute([$name, $description, $price, $category, $stock])) {
                                $product_id = $DB->lastInsertId();
                                
                                // Créer le dossier s'il n'existe pas
                                if (!is_dir($productsDir)) {
                                    mkdir($productsDir, 0777, true);
                                }

                                // Traiter chaque image
                                $total_files = count($_FILES['images']['name']);
                                for ($i = 0; $i < $total_files; $i++) {
                                    if ($_FILES['images']['error'][$i] === 0) {
                                        $filename = $_FILES['images']['name'][$i];
                                        $tmp_name = $_FILES['images']['tmp_name'][$i];
                                        $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                                        error_log("Traitement de l'image $i : $filename");

                                        if (in_array($filetype, $allowed)) {
                                            $newname = uniqid() . '.' . $filetype;
                                            $upload_path = $productsDir . '/' . $newname;

                                            if (move_uploaded_file($tmp_name, $upload_path)) {
                                                // Définir si c'est l'image principale (première image)
                                                $is_main = ($i === 0) ? 1 : 0;
                                                
                                                $stmt = $DB->prepare("INSERT INTO product_images (product_id, image_path, is_main) VALUES (?, ?, ?)");
                                                if (!$stmt->execute([$product_id, $upload_path, $is_main])) {
                                                    throw new Exception("Erreur lors de l'enregistrement de l'image $i");
                                                }
                                                error_log("Image $i uploadée avec succès : $upload_path");
                                            } else {
                                                throw new Exception("Erreur lors de l'upload de l'image $i");
                                            }
                                        } else {
                                            throw new Exception("Type de fichier non autorisé pour l'image $i");
                                        }
                                    }
                                }
                                
                                $DB->commit();
                                $success_msg = "Produit et images ajoutés avec succès !";
                            } else {
                                throw new Exception("Erreur lors de l'ajout du produit");
                            }
                        } catch (Exception $e) {
                            $DB->rollBack();
                            $error_msg = $e->getMessage();
                            error_log("Erreur : " . $e->getMessage());
                        }
                    }
                }
            } elseif ($_POST['action'] === 'edit') {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $category = trim($_POST['category']);
                $stock = intval($_POST['stock']);
                
                // Mise à jour du produit
                $stmt = $DB->prepare("UPDATE products SET name = ?, description = ?, price = ?, category = ?, stock = ? WHERE id = ?");
                if ($stmt->execute([$name, $description, $price, $category, $stock, $_POST['product_id']])) {
                    $success_msg = "Produit mis à jour avec succès !";
                } else {
                    $error_msg = "Erreur lors de la mise à jour du produit.";
                }
            } elseif ($_POST['action'] === 'delete' && isset($_POST['product_id'])) {
                include_once('_functions/image_utils.php');
                
                $result = deleteProductWithImages($_POST['product_id'], $DB);
                
                if ($result['success']) {
                    $success_msg = $result['message'] . " ({$result['total_images']} images supprimées)";
                    
                    // Forcer la sortie pour éviter les problèmes de cache
                    echo json_encode(['success' => true, 'message' => $success_msg]);
                    exit();
                } else {
                    $error_msg = $result['message'];
                    echo json_encode(['success' => false, 'message' => $error_msg]);
                    exit();
                }
                    } else {
                        $DB->rollback();
                        $error_msg = "Erreur lors de la suppression du produit.";
                    }
                } catch (Exception $e) {
                    $DB->rollback();
                    $error_msg = "Erreur lors de la suppression : " . $e->getMessage();
                }
            } elseif ($_POST['action'] === 'make_main_image' && isset($_POST['product_id']) && isset($_POST['image_id'])) {
                try {
                    $DB->beginTransaction();
                    
                    // D'abord, on retire le statut d'image principale pour toutes les images du produit
                    $stmt = $DB->prepare("UPDATE product_images SET is_main = 0 WHERE product_id = ?");
                    $stmt->execute([$_POST['product_id']]);
                    
                    // Ensuite, on définit la nouvelle image principale
                    $stmt = $DB->prepare("UPDATE product_images SET is_main = 1 WHERE id = ? AND product_id = ?");
                    if ($stmt->execute([$_POST['image_id'], $_POST['product_id']])) {
                        $DB->commit();
                        echo "Image principale mise à jour avec succès";
                    } else {
                        throw new Exception("Erreur lors de la mise à jour de l'image principale");
                    }
                } catch (Exception $e) {
                    $DB->rollBack();
                    error_log("Erreur lors de la mise à jour de l'image principale: " . $e->getMessage());
                    http_response_code(500);
                    echo "Erreur lors de la mise à jour de l'image principale";
                }
                exit;
            } elseif ($_POST['action'] === 'delete_image' && isset($_POST['image_id'])) {
                try {
                    // Récupérer les informations de l'image
                    $stmt = $DB->prepare("SELECT * FROM product_images WHERE id = ?");
                    $stmt->execute([$_POST['image_id']]);
                    $image = $stmt->fetch();
                    
                    if ($image) {
                        // Supprimer le fichier physique
                        if (file_exists($image['image_path'])) {
                            unlink($image['image_path']);
                        }
                        
                        // Supprimer l'entrée de la base de données
                        $stmt = $DB->prepare("DELETE FROM product_images WHERE id = ?");
                        if ($stmt->execute([$_POST['image_id']])) {
                            echo "Image supprimée avec succès";
                        } else {
                            throw new Exception("Erreur lors de la suppression de l'image");
                        }
                    }
                } catch (Exception $e) {
                    error_log("Erreur lors de la suppression de l'image: " . $e->getMessage());
                    http_response_code(500);
                    echo "Erreur lors de la suppression de l'image";
                }
                exit;
            }
        }
    }

    // Récupération des produits
    $products = $DB->query("SELECT * FROM products ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Administration</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <style>
        .admin-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .product-form {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }

        .product-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .product-images {
            position: relative;
            width: 100%;
            height: 300px;
            overflow: hidden;
            background: #f8f9fa;
        }

        .image-slider {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .product-image {
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            max-width: 100%;
            max-height: 100%;
            width: auto !important;
            height: auto !important;
            object-fit: contain;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .product-image.active {
            opacity: 1;
        }

        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 2;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 20px;
        }

        .slider-arrow:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .slider-arrow.prev {
            left: 10px;
        }

        .slider-arrow.next {
            right: 10px;
        }

        .image-dots {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            z-index: 2;
            background: rgba(0, 0, 0, 0.3);
            padding: 5px 10px;
            border-radius: 15px;
        }

        .dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .dot.active {
            background: white;
            transform: scale(1.2);
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn-edit,
        .btn-delete {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }

        .btn-edit {
            background: #007bff;
            color: white;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
        }

        .image-inputs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .image-input {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
        }

        .image-input label {
            font-size: 0.9em;
            color: #666;
            margin-bottom: 5px;
        }

        .image-input input[type="file"] {
            width: 100%;
            padding: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: white;
        }

        .image-input:first-child {
            background: #e8f4ff;
        }

        .image-input:first-child label {
            color: #007bff;
            font-weight: bold;
        }

        .product-edit {
            padding: 15px;
        }

        .product-edit .form-group {
            margin-bottom: 10px;
        }

        .product-edit input,
        .product-edit textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }

        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn-save:hover {
            background: #218838;
        }

        .btn-cancel:hover {
            background: #5a6268;
        }

        .current-images {
            display: flex;
            gap: 10px;
            margin-bottom: 10px;
        }

        .image-item {
            position: relative;
            width: 100px;
            height: 100px;
            overflow: hidden;
            border-radius: 4px;
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-controls {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .image-controls:hover {
            opacity: 1;
        }

        .btn-make-main {
            background: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 5px;
        }

        .btn-delete-image {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <h1>Administration des produits</h1>
        
        <div class="admin-container">
            <!-- Messages de succès/erreur -->
            <?php if(isset($success_msg)): ?>
                <div style="background: #d4edda; color: #155724; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error_msg)): ?>
                <div style="background: #f8d7da; color: #721c24; padding: 10px; margin-bottom: 20px; border-radius: 4px;">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire d'ajout/modification -->
            <div class="product-form">
                <h2>Ajouter un produit</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label>Nom du produit</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Prix</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    <div class="form-group">
                        <label>Catégorie</label>
                        <input type="text" name="category" required>
                    </div>
                    <div class="form-group">
                        <label>Stock</label>
                        <input type="number" name="stock" required>
                    </div>
                    <div class="form-group">
                        <label>Images (6 maximum)</label>
                        <div class="image-inputs">
                            <div class="image-input">
                                <label>Image 1 (principale)</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif" required>
                            </div>
                            <div class="image-input">
                                <label>Image 2</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                            <div class="image-input">
                                <label>Image 3</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                            <div class="image-input">
                                <label>Image 4</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                            <div class="image-input">
                                <label>Image 5</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                            <div class="image-input">
                                <label>Image 6</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                        </div>
                        <small>L'image 1 sera l'image principale affichée en premier</small>
                    </div>
                    <button type="submit" class="btn-edit">Ajouter</button>
                </form>
            </div>

            <!-- Liste des produits existants -->
            <div class="product-grid">
                <?php foreach($products as $product): 
                    // Récupérer toutes les images du produit
                    $stmt = $DB->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
                    $stmt->execute([$product['id']]);
                    $images = $stmt->fetchAll();
                ?>
                    <div class="product-item" id="product-<?php echo $product['id']; ?>">
                        <div class="product-images">
                            <?php if(!empty($images)): ?>
                                <div class="image-slider">
                                    <?php foreach($images as $index => $image): ?>
                                        <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image <?php echo $index === 0 ? 'active' : ''; ?>"
                                             data-index="<?php echo $index; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <?php if(count($images) > 1): ?>
                                    <button class="slider-arrow prev" onclick="prevImage(this)">&lt;</button>
                                    <button class="slider-arrow next" onclick="nextImage(this)">&gt;</button>
                                    <div class="image-dots">
                                        <?php foreach($images as $index => $image): ?>
                                            <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                  onclick="showImage(this.parentNode.parentNode, <?php echo $index; ?>)"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        <!-- Mode affichage -->
                        <div class="product-info" id="display-<?php echo $product['id']; ?>">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p><?php echo htmlspecialchars($product['description']); ?></p>
                            <p>Prix: <?php echo number_format($product['price'], 2); ?>€</p>
                            <p>Stock: <?php echo $product['stock']; ?></p>
                            <div class="button-group">
                                <button class="btn-edit" onclick="toggleEdit(<?php echo $product['id']; ?>)">Modifier</button>
                                <button class="btn-delete" onclick="deleteProduct(<?php echo $product['id']; ?>)">Supprimer</button>
                            </div>
                        </div>
                        <!-- Mode édition -->
                        <div class="product-edit" id="edit-<?php echo $product['id']; ?>" style="display: none;">
                            <form onsubmit="updateProduct(event, <?php echo $product['id']; ?>)">
                                <div class="form-group">
                                    <label>Nom</label>
                                    <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                                </div>
                                <div class="form-group">
                                    <label>Prix</label>
                                    <input type="number" name="price" step="0.01" value="<?php echo $product['price']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Catégorie</label>
                                    <input type="text" name="category" value="<?php echo htmlspecialchars($product['category']); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Stock</label>
                                    <input type="number" name="stock" value="<?php echo $product['stock']; ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Images actuelles</label>
                                    <div class="current-images">
                                        <?php foreach($images as $index => $image): ?>
                                            <div class="image-item">
                                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                                     alt="Image <?php echo $index + 1; ?>">
                                                <div class="image-controls">
                                                    <?php if(!$image['is_main']): ?>
                                                        <button type="button" class="btn-make-main" onclick="makeMainImage(<?php echo $product['id']; ?>, <?php echo $image['id']; ?>)">
                                                            Définir comme principale
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button" class="btn-delete-image" onclick="deleteImage(<?php echo $image['id']; ?>)">
                                                        Supprimer
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    
                                    <label>Ajouter de nouvelles images</label>
                                    <div class="image-inputs">
                                        <?php
                                        $remaining_slots = 6 - count($images);
                                        for($i = 0; $i < $remaining_slots; $i++): 
                                        ?>
                                            <div class="image-input">
                                                <label>Nouvelle image <?php echo $i + 1; ?></label>
                                                <input type="file" name="new_images[]" accept=".jpg,.jpeg,.png,.gif">
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="button-group">
                                    <button type="submit" class="btn-save">Enregistrer</button>
                                    <button type="button" class="btn-cancel" onclick="toggleEdit(<?php echo $product['id']; ?>)">Annuler</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        function toggleEdit(productId) {
            const displayEl = document.getElementById(`display-${productId}`);
            const editEl = document.getElementById(`edit-${productId}`);
            
            if (displayEl.style.display !== 'none') {
                displayEl.style.display = 'none';
                editEl.style.display = 'block';
            } else {
                displayEl.style.display = 'block';
                editEl.style.display = 'none';
            }
        }

        function updateProduct(event, productId) {
            event.preventDefault();
            const form = event.target;
            const formData = new FormData(form);
            formData.append('action', 'edit');
            formData.append('product_id', productId);

            fetch('administrateur.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Rafraîchir la page pour voir les modifications
                window.location.reload();
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la mise à jour du produit');
            });
        }

        function deleteProduct(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('product_id', id);

                // Désactiver le bouton pour éviter les clics multiples
                const button = document.querySelector(`button[onclick*="deleteProduct(${id})"]`);
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Suppression...';
                }

                fetch('administrateur.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then((data) => {
                    // Vérifier si c'est du JSON
                    try {
                        const result = JSON.parse(data);
                        if (result.success) {
                            // Supprimer l'élément de la page sans recharger
                            const productCard = button.closest('.product-card');
                            if (productCard) {
                                productCard.remove();
                            }
                            alert('Produit supprimé avec succès !');
                        } else {
                            alert('Erreur lors de la suppression');
                        }
                    } catch (e) {
                        // Si ce n'est pas du JSON, recharger la page
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression du produit');
                    // Réactiver le bouton en cas d'erreur
                    if (button) {
                        button.disabled = false;
                        button.textContent = 'Supprimer';
                    }
                });
            }
        }

        function makeMainImage(productId, imageId) {
            const formData = new FormData();
            formData.append('action', 'make_main_image');
            formData.append('product_id', productId);
            formData.append('image_id', imageId);

            fetch('administrateur.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(() => {
                // Rafraîchir la page pour voir les modifications
                window.location.reload();
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Une erreur est survenue lors de la mise à jour de l\'image principale');
            });
        }

        function deleteImage(imageId) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette image ?')) {
                const formData = new FormData();
                formData.append('action', 'delete_image');
                formData.append('image_id', imageId);

                fetch('administrateur.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(() => {
                    // Rafraîchir la page pour voir les modifications
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Une erreur est survenue lors de la suppression de l\'image');
                });
            }
        }

        function showImage(container, index) {
            const images = container.getElementsByClassName('product-image');
            const dots = container.getElementsByClassName('dot');
            
            Array.from(images).forEach(img => img.classList.remove('active'));
            Array.from(dots).forEach(dot => dot.classList.remove('active'));
            
            images[index].classList.add('active');
            dots[index].classList.add('active');
        }

        function nextImage(button) {
            const container = button.parentNode;
            const images = container.getElementsByClassName('product-image');
            const currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
            const nextIndex = (currentIndex + 1) % images.length;
            showImage(container, nextIndex);
        }

        function prevImage(button) {
            const container = button.parentNode;
            const images = container.getElementsByClassName('product-image');
            const currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
            const prevIndex = (currentIndex - 1 + images.length) % images.length;
            showImage(container, prevIndex);
        }

        // Défilement automatique
        document.addEventListener('DOMContentLoaded', function() {
            const sliders = document.getElementsByClassName('product-images');
            Array.from(sliders).forEach(slider => {
                if (slider.getElementsByClassName('product-image').length > 1) {
                    setInterval(() => {
                        const nextButton = slider.querySelector('.slider-arrow.next');
                        if (nextButton && !slider.matches(':hover')) {
                            nextImage(nextButton);
                        }
                    }, 5000);
                }
            });
        });
    </script>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 