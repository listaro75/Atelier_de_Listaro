<?php
session_start();
require_once '_db/db_connection.php';
require_once '_functions/image_utils.php';

// Vérifier les permissions administrateur
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: connexion.php');
    exit();
}

// Traitement des actions
$message = '';
$message_type = '';

if (isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'clean_orphans':
            $cleaned = cleanOrphanImages($DB);
            $message = "Nettoyage terminé : $cleaned fichiers orphelins supprimés.";
            $message_type = 'success';
            break;
    }
}

// Récupérer les statistiques
$stats = getImageStats($DB);

// Récupérer les produits avec le plus d'images
$stmt = $DB->query("
    SELECT p.id, p.name, COUNT(pi.id) as image_count 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    GROUP BY p.id, p.name 
    HAVING image_count > 0
    ORDER BY image_count DESC 
    LIMIT 10
");
$top_products = $stmt->fetchAll();

// Récupérer les produits sans images
$stmt = $DB->query("
    SELECT p.id, p.name 
    FROM products p 
    LEFT JOIN product_images pi ON p.id = pi.product_id 
    WHERE pi.product_id IS NULL
    ORDER BY p.name
    LIMIT 20
");
$products_without_images = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Images - Administration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: -20px -20px 20px -20px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin: 30px 0;
        }
        .section h3 {
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #5a6fd8;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .nav-links {
            margin-bottom: 20px;
        }
        .nav-links a {
            margin-right: 15px;
            color: #667eea;
            text-decoration: none;
        }
        .nav-links a:hover {
            text-decoration: underline;
        }
        .badge {
            background: #667eea;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
        }
        .badge-warning {
            background: #ffc107;
            color: #212529;
        }
        .badge-danger {
            background: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Gestion des Images</h1>
            <p>Administration et statistiques des images produits</p>
        </div>

        <div class="nav-links">
            <a href="admin_panel.php">← Retour au panel admin</a>
            <a href="test_image_limit.php">Test limite images</a>
            <a href="test_multi_images.html" target="_blank">🧪 Test sélection multiple</a>
            <a href="admin_sections/products.php">Gestion produits</a>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <!-- Statistiques générales -->
        <div class="section">
            <h3>📊 Statistiques Générales</h3>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['total_images'] ?></div>
                    <div class="stat-label">Images totales</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['products_with_images'] ?></div>
                    <div class="stat-label">Produits avec images</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['avg_images_per_product'] ?></div>
                    <div class="stat-label">Moyenne par produit</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $stats['max_images_product'] ?></div>
                    <div class="stat-label">Maximum d'images
                        <?php if ($stats['max_images_product'] > 5): ?>
                            <span class="badge badge-warning">⚠️ > 5</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Actions de maintenance -->
        <div class="section">
            <h3>🔧 Actions de Maintenance</h3>
            <form method="post" style="margin: 20px 0;" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer les fichiers orphelins ?')">
                <input type="hidden" name="action" value="clean_orphans">
                <button type="submit" class="btn btn-danger">
                    🗑️ Nettoyer les fichiers orphelins
                </button>
                <small style="display: block; margin-top: 5px; color: #666;">
                    Supprime les fichiers d'images qui ne sont plus référencés en base de données
                </small>
            </form>
        </div>

        <!-- Top produits avec images -->
        <div class="section">
            <h3>🏆 Produits avec le Plus d'Images</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Produit</th>
                        <th>Nom du Produit</th>
                        <th>Nombre d'Images</th>
                        <th>Statut Limite</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>
                                <?= $product['image_count'] ?>
                                <?php if ($product['image_count'] > 5): ?>
                                    <span class="badge badge-danger">⚠️ Dépasse la limite</span>
                                <?php elseif ($product['image_count'] == 5): ?>
                                    <span class="badge">✅ Limite atteinte</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($product['image_count'] > 5): ?>
                                    <span style="color: #dc3545;">❌ Dépasse (<?= $product['image_count'] - 5 ?> en trop)</span>
                                <?php elseif ($product['image_count'] == 5): ?>
                                    <span style="color: #28a745;">✅ Conforme</span>
                                <?php else: ?>
                                    <span style="color: #6c757d;">📈 Peut ajouter <?= 5 - $product['image_count'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="admin_sections/products.php?edit=<?= $product['id'] ?>" class="btn" style="font-size: 0.8rem; padding: 5px 10px;">
                                    ✏️ Modifier
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($top_products)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #666;">
                                Aucun produit avec images trouvé
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Produits sans images -->
        <div class="section">
            <h3>📷 Produits Sans Images</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID Produit</th>
                        <th>Nom du Produit</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products_without_images as $product): ?>
                        <tr>
                            <td><?= $product['id'] ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td>
                                <a href="admin_sections/products.php?edit=<?= $product['id'] ?>" class="btn" style="font-size: 0.8rem; padding: 5px 10px;">
                                    📷 Ajouter des images
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products_without_images)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #666;">
                                Tous les produits ont des images ! 🎉
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Informations sur la sélection multiple d'images -->
        <div class="section">
            <h3>📸 Guide : Sélection Multiple d'Images</h3>
            <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; border-left: 4px solid #28a745;">
                <h4>Comment ajouter plusieurs images à un produit :</h4>
                <ol style="margin: 10px 0; padding-left: 20px;">
                    <li><strong>Accéder au formulaire :</strong> Allez dans "Gestion produits" → "Ajouter un produit"</li>
                    <li><strong>Cliquer sur le bouton :</strong> "Sélectionner plusieurs images"</li>
                    <li><strong>Sélection multiple :</strong> 
                        <ul style="margin: 5px 0; padding-left: 20px;">
                            <li>Maintenez <code>Ctrl</code> (Windows) ou <code>Cmd</code> (Mac)</li>
                            <li>Cliquez sur chaque image désirée</li>
                            <li>Relâchez la touche et cliquez "Ouvrir"</li>
                        </ul>
                    </li>
                    <li><strong>Vérification :</strong> Toutes les images apparaissent en prévisualisation</li>
                    <li><strong>Suppression individuelle :</strong> Cliquez sur ❌ pour retirer une image</li>
                </ol>
                
                <div style="display: flex; gap: 15px; margin-top: 15px;">
                    <a href="test_multi_images.html" target="_blank" class="btn" style="background: #28a745;">
                        🧪 Tester la fonctionnalité
                    </a>
                    <a href="admin_sections/products.php" class="btn">
                        ➕ Ajouter un produit
                    </a>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px; border: 1px solid #ffeaa7;">
                    <strong>💡 Astuce :</strong> La première image sélectionnée deviendra automatiquement l'image principale du produit.
                </div>
            </div>
        </div>

        <!-- Informations sur la limite -->
        <div class="section">
            <h3>ℹ️ Informations sur la Limite d'Images</h3>
            <div style="background: #e7f3ff; padding: 20px; border-radius: 8px; border-left: 4px solid #0084ff;">
                <h4>Nouvelle Règle : Maximum 5 Images par Produit</h4>
                <ul>
                    <li>✅ <strong>Validation côté serveur :</strong> Empêche l'ajout de plus de 5 images</li>
                    <li>✅ <strong>Validation côté client :</strong> Alerte l'utilisateur avant l'envoi</li>
                    <li>✅ <strong>Comptage intelligent :</strong> Prend en compte les images existantes</li>
                    <li>✅ <strong>Messages clairs :</strong> Informe l'utilisateur du nombre d'images disponibles</li>
                </ul>
                <p><strong>Note :</strong> Les produits existants avec plus de 5 images sont tolérés mais ne peuvent plus en ajouter.</p>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh des stats toutes les 5 minutes
        setTimeout(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
