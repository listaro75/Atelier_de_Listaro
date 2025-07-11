<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    header('Location: connexion.php?error=admin_required');
    exit();
}

// Récupérer les statistiques
$stats_products = $DB->query("SELECT COUNT(*) FROM products")->fetchColumn();
$stats_users = $DB->query("SELECT COUNT(*) FROM user")->fetchColumn();

// Essayer de récupérer les commandes (si la table existe)
try {
    $stats_orders = $DB->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $stats_revenue = $DB->query("SELECT SUM(total) FROM orders WHERE status = 'delivered'")->fetchColumn() ?: 0;
} catch (Exception $e) {
    $stats_orders = 0;
    $stats_revenue = 0;
}

// Récupérer les produits
$products = $DB->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'delete_product':
                if (isset($_POST['product_id'])) {
                    try {
                        // Supprimer les images
                        $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ?");
                        $stmt->execute([$_POST['product_id']]);
                        $images = $stmt->fetchAll();
                        
                        foreach ($images as $image) {
                            if (file_exists($image['image_path'])) {
                                unlink($image['image_path']);
                            }
                        }
                        
                        // Supprimer le produit
                        $stmt = $DB->prepare("DELETE FROM products WHERE id = ?");
                        $stmt->execute([$_POST['product_id']]);
                        
                        echo json_encode(['success' => true, 'message' => 'Produit supprimé']);
                        exit();
                    } catch (Exception $e) {
                        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
                        exit();
                    }
                }
                break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Atelier de Listaro</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            color: #2c3e50;
            font-size: 2.2em;
        }

        .logout-btn {
            background: rgba(231, 76, 60, 0.9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .logout-btn:hover {
            background: rgba(231, 76, 60, 1);
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
            color: #3498db;
        }

        .stat-number {
            font-size: 2.2em;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stat-label {
            color: #666;
            font-size: 1.1em;
        }

        .section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .section h2 {
            color: #2c3e50;
            font-size: 1.8em;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .section h2 i {
            margin-right: 15px;
            color: #3498db;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #2c3e50;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .btn {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin-right: 5px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 3px 10px rgba(52, 152, 219, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
        }

        .btn-danger:hover {
            box-shadow: 0 3px 10px rgba(231, 76, 60, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60 0%, #229954 100%);
        }

        .btn-success:hover {
            box-shadow: 0 3px 10px rgba(39, 174, 96, 0.4);
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .quick-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-cogs"></i> Centre d'Administration</h1>
            <a href="deconnexion.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                Déconnexion
            </a>
        </div>

        <!-- Statistiques -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-box"></i>
                </div>
                <div class="stat-number"><?php echo $stats_products; ?></div>
                <div class="stat-label">Produits</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?php echo $stats_users; ?></div>
                <div class="stat-label">Utilisateurs</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-number"><?php echo $stats_orders; ?></div>
                <div class="stat-label">Commandes</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-euro-sign"></i>
                </div>
                <div class="stat-number"><?php echo number_format($stats_revenue, 2); ?>€</div>
                <div class="stat-label">Chiffre d'affaires</div>
            </div>
        </div>

        <!-- Actions rapides -->
        <div class="section">
            <h2><i class="fas fa-bolt"></i> Actions rapides</h2>
            <div class="quick-actions">
                <a href="administrateur.php" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    Ajouter un produit
                </a>
                <a href="admin_orders.php" class="btn">
                    <i class="fas fa-shopping-cart"></i>
                    Voir les commandes
                </a>
                <a href="debug_products.php" class="btn" target="_blank">
                    <i class="fas fa-wrench"></i>
                    Diagnostic
                </a>
                <a href="shop.php" class="btn" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    Voir le site
                </a>
            </div>
        </div>

        <!-- Produits récents -->
        <div class="section">
            <h2><i class="fas fa-box"></i> Produits récents</h2>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Nom</th>
                            <th>Prix</th>
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
                            <tr>
                                <td><?php echo $product['id']; ?></td>
                                <td>
                                    <?php if ($mainImage): ?>
                                        <img src="<?php echo htmlspecialchars($mainImage['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #f0f0f0; border-radius: 5px; display: flex; align-items: center; justify-content: center;">
                                            <i class="fas fa-image" style="color: #ccc;"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo number_format($product['price'], 2); ?> €</td>
                                <td>
                                    <span style="background: <?php echo $product['stock'] > 0 ? '#27ae60' : '#e74c3c'; ?>; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8em;">
                                        <?php echo $product['stock']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($product['created_at'])); ?></td>
                                <td>
                                    <a href="product_details.php?id=<?php echo $product['id']; ?>" class="btn" target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
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
    </div>

    <script>
        function deleteProduct(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer ce produit ?')) {
                const formData = new FormData();
                formData.append('action', 'delete_product');
                formData.append('product_id', id);

                fetch('admin_simple.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer la ligne
                        const row = document.querySelector(`button[onclick*="deleteProduct(${id})"]`).closest('tr');
                        row.remove();
                        
                        // Notification
                        const alert = document.createElement('div');
                        alert.className = 'alert alert-success';
                        alert.textContent = data.message;
                        document.querySelector('.container').insertBefore(alert, document.querySelector('.stats-grid'));
                        
                        setTimeout(() => alert.remove(), 3000);
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
    </script>
</body>
</html>
