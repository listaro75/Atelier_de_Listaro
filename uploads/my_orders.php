<?php
session_start();
require_once('_db/connexion_DB.php');
require_once('_functions/auth.php');

// Vérifier que l'utilisateur est connecté
if (!is_logged()) {
    header('Location: connexion.php');
    exit();
}

// Récupérer les commandes de l'utilisateur
$stmt = $DB->prepare("
    SELECT 
        o.id as order_id,
        o.created_at,
        o.total_amount,
        o.shipping_cost,
        o.shipping_method,
        o.shipping_address,
        o.status,
        GROUP_CONCAT(
            CONCAT(
                oi.quantity, 
                'x ',
                p.name,
                ' (',
                oi.price,
                '€)'
            ) SEPARATOR ', '
        ) as products
    FROM orders o
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$stmt->execute([$_SESSION['id']]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mes commandes</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <style>
        .orders-page {
            padding-top: 150px;
            min-height: 100vh;
            background-color: #f5f5f5;
        }

        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #eee;
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .order-status {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.8rem;
        }

        .status-en_attente { background: #ffd700; color: #000; }
        .status-payé { background: #90EE90; color: #006400; }
        .status-expédié { background: #87CEEB; color: #00008B; }
        .status-livré { background: #98FB98; color: #006400; }
        .status-annulé { background: #FFB6C1; color: #8B0000; }

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .detail-group {
            padding: 1rem;
            background: #f9f9f9;
            border-radius: 5px;
        }

        .detail-group h4 {
            margin: 0 0 0.5rem 0;
            color: #333;
            font-size: 0.9rem;
            text-transform: uppercase;
        }

        .detail-group p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }

        .no-orders {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 10px;
            margin-top: 2rem;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 4px;
        }

        .alert-success {
            color: #155724;
            background-color: #d4edda;
            border-color: #c3e6cb;
        }
    </style>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="orders-page">
        <div class="orders-container">
            <h1>Mes commandes</h1>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']); // Supprimer le message après l'affichage
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (empty($orders)): ?>
                <div class="no-orders">
                    <h3>Vous n'avez pas encore de commande</h3>
                    <p>Découvrez nos produits et passez votre première commande !</p>
                    <a href="index.php" class="btn-secondary">Voir les produits</a>
                </div>
            <?php else: ?>
                <?php foreach ($orders as $order): 
                    $address = json_decode($order['shipping_address'], true);
                ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3>Commande #<?php echo $order['order_id']; ?></h3>
                            <span class="order-status status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>

                        <div class="order-details">
                            <div class="detail-group">
                                <h4>Date</h4>
                                <p><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            </div>

                            <div class="detail-group">
                                <h4>Total</h4>
                                <p><?php echo number_format($order['total_amount'], 2); ?>€</p>
                            </div>

                            <div class="detail-group">
                                <h4>Livraison</h4>
                                <p><?php echo htmlspecialchars($order['shipping_method']); ?></p>
                                <p><?php echo number_format($order['shipping_cost'], 2); ?>€</p>
                            </div>
                        </div>

                        <div class="detail-group" style="margin-top: 1rem;">
                            <h4>Produits</h4>
                            <p><?php echo htmlspecialchars($order['products']); ?></p>
                        </div>

                        <div class="detail-group" style="margin-top: 1rem;">
                            <h4>Adresse de livraison</h4>
                            <?php if ($address): ?>
                                <p><?php echo htmlspecialchars($address['firstname'] . ' ' . $address['lastname']); ?></p>
                                <p><?php echo htmlspecialchars($address['address']); ?></p>
                                <p><?php echo htmlspecialchars($address['postal'] . ' ' . $address['city']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 