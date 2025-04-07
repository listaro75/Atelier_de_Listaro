<?php
    include_once('_db/connexion_DB.php');
    include_once('_functions/auth.php');
    session_start();

    // Vérifier si l'utilisateur est admin
    if (!is_admin()) {
        header('Location: index.php');
        exit();
    }

    // Définir les chemins
    $uploadDir = 'uploads';
    $prestationsDir = 'uploads/prestations';

    // Créer les dossiers s'ils n'existent pas
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    if (!is_dir($prestationsDir)) mkdir($prestationsDir, 0777, true);

    // Traitement des actions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['action'])) {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $price = floatval($_POST['price']);
                $duration = trim($_POST['duration']);
                $category = trim($_POST['category']);
                
                if (isset($_FILES['images'])) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    
                    try {
                        $DB->beginTransaction();

                        // Insérer la prestation
                        $stmt = $DB->prepare("INSERT INTO prestations (name, description, price, duration, category) VALUES (?, ?, ?, ?, ?)");
                        
                        if ($stmt->execute([$name, $description, $price, $duration, $category])) {
                            $prestation_id = $DB->lastInsertId();
                            
                            // Traiter chaque image
                            $total_files = count($_FILES['images']['name']);
                            for ($i = 0; $i < $total_files; $i++) {
                                if ($_FILES['images']['error'][$i] === 0) {
                                    $filename = $_FILES['images']['name'][$i];
                                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                                    $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                                    if (in_array($filetype, $allowed)) {
                                        $newname = uniqid() . '.' . $filetype;
                                        $upload_path = $prestationsDir . '/' . $newname;

                                        if (move_uploaded_file($tmp_name, $upload_path)) {
                                            $is_main = ($i === 0) ? 1 : 0;
                                            
                                            $stmt = $DB->prepare("INSERT INTO prestation_images (prestation_id, image_path, is_main) VALUES (?, ?, ?)");
                                            if (!$stmt->execute([$prestation_id, $upload_path, $is_main])) {
                                                throw new Exception("Erreur lors de l'enregistrement de l'image");
                                            }
                                        }
                                    }
                                }
                            }
                            
                            $DB->commit();
                            $success_msg = "Prestation ajoutée avec succès !";
                        }
                    } catch (Exception $e) {
                        $DB->rollBack();
                        $error_msg = $e->getMessage();
                    }
                }
            } elseif ($_POST['action'] === 'edit') {
                // Code pour l'édition
                $stmt = $DB->prepare("UPDATE prestations SET name = ?, description = ?, price = ?, duration = ?, category = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['name'],
                    $_POST['description'],
                    $_POST['price'],
                    $_POST['duration'],
                    $_POST['category'],
                    $_POST['prestation_id']
                ]);
            } elseif ($_POST['action'] === 'delete') {
                // Code pour la suppression
                $stmt = $DB->prepare("DELETE FROM prestations WHERE id = ?");
                $stmt->execute([$_POST['prestation_id']]);
            } elseif ($_POST['action'] === 'make_main_image') {
                try {
                    $DB->beginTransaction();
                    
                    $stmt = $DB->prepare("UPDATE prestation_images SET is_main = 0 WHERE prestation_id = ?");
                    $stmt->execute([$_POST['prestation_id']]);
                    
                    $stmt = $DB->prepare("UPDATE prestation_images SET is_main = 1 WHERE id = ?");
                    $stmt->execute([$_POST['image_id']]);
                    
                    $DB->commit();
                } catch (Exception $e) {
                    $DB->rollBack();
                }
            }
        }
    }

    // Récupération des prestations
    $prestations = $DB->query("SELECT * FROM prestations ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Administration des Prestations</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <h1>Administration des Prestations</h1>
        
        <div class="admin-container">
            <!-- Formulaire d'ajout -->
            <div class="form-container">
                <h2>Ajouter une prestation</h2>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">
                    
                    <div class="form-group">
                        <label>Nom de la prestation</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Prix (€)</label>
                        <input type="number" name="price" step="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Durée</label>
                        <input type="text" name="duration" placeholder="Ex: 1h30">
                    </div>
                    
                    <div class="form-group">
                        <label>Catégorie</label>
                        <input type="text" name="category" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Images (6 maximum)</label>
                        <div class="image-inputs">
                            <div class="image-input">
                                <label>Image 1 (principale)</label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif" required>
                            </div>
                            <?php for($i = 2; $i <= 6; $i++): ?>
                            <div class="image-input">
                                <label>Image <?php echo $i; ?></label>
                                <input type="file" name="images[]" accept=".jpg,.jpeg,.png,.gif">
                            </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-add">Ajouter la prestation</button>
                </form>
            </div>

            <!-- Liste des prestations -->
            <div class="prestations-grid">
                <?php foreach($prestations as $prestation): 
                    $stmt = $DB->prepare("SELECT * FROM prestation_images WHERE prestation_id = ? ORDER BY is_main DESC");
                    $stmt->execute([$prestation['id']]);
                    $images = $stmt->fetchAll();
                ?>
                    <div class="prestation-item">
                        <div class="prestation-images">
                            <?php if(!empty($images)): ?>
                                <div class="image-slider">
                                    <?php foreach($images as $index => $image): ?>
                                        <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($prestation['name']); ?>"
                                             class="prestation-image <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <?php endforeach; ?>
                                </div>
                                <?php if(count($images) > 1): ?>
                                    <button class="slider-arrow prev" onclick="prevImage(this)">&lt;</button>
                                    <button class="slider-arrow next" onclick="nextImage(this)">&gt;</button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="prestation-info">
                            <h3><?php echo htmlspecialchars($prestation['name']); ?></h3>
                            <p><?php echo htmlspecialchars($prestation['description']); ?></p>
                            <p>Prix: <?php echo number_format($prestation['price'], 2); ?>€</p>
                            <p>Durée: <?php echo htmlspecialchars($prestation['duration']); ?></p>
                            <p>Catégorie: <?php echo htmlspecialchars($prestation['category']); ?></p>
                            
                            <div class="button-group">
                                <button onclick="editPrestation(<?php echo $prestation['id']; ?>)">Modifier</button>
                                <button onclick="deletePrestation(<?php echo $prestation['id']; ?>)">Supprimer</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    // Fonctions JavaScript existantes pour le slider d'images
    // ... (les mêmes que dans prestations.php)

    function editPrestation(id) {
        // À implémenter : ouvrir un formulaire de modification
        alert('Fonctionnalité de modification à venir');
    }

    function deletePrestation(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette prestation ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="prestation_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 