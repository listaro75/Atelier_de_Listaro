<?php
session_start();
require_once('_db/connexion_DB.php');
require_once('_functions/auth.php');

// Vérifier que l'utilisateur est admin
if (!is_admin()) {
    header('Location: index.php');
    exit();
}

// Récupérer toutes les commandes avec les informations détaillées
$stmt = $DB->prepare("
    SELECT 
        o.id as order_id,
        o.created_at,
        o.total_amount,
        o.shipping_cost,
        o.shipping_method,
        o.shipping_address,
        o.status,
        o.stripe_payment_id,
        u.pseudo as user_pseudo,
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
    LEFT JOIN user u ON o.user_id = u.id
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id
    GROUP BY o.id
    ORDER BY o.created_at DESC
");

$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Gestion des commandes</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <style>
        /* Réinitialisation du body */
        body {
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Style spécifique pour la page des commandes */
        .main-content {
            flex: 1;
            padding-top: 100px; /* Ajustez selon la hauteur de votre navbar */
        }

        .orders-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: #333;
        }

        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .order-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            padding: 1.5rem;
            transition: transform 0.3s ease;
            border: 1px solid #eee;
        }

        .order-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            border-bottom: 1px solid #eee;
            padding-bottom: 0.5rem;
        }

        .order-header h3 {
            color: #333;
            margin: 0;
            font-size: 1.2rem;
        }

        .order-status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .status-en_attente { background: #ffd700; color: #000; }
        .status-payé { background: #90EE90; color: #006400; }
        .status-expédié { background: #87CEEB; color: #00008B; }
        .status-livré { background: #98FB98; color: #006400; }
        .status-annulé { background: #FFB6C1; color: #8B0000; }

        .order-info {
            margin-bottom: 1rem;
            font-size: 0.95rem;
        }

        .order-info p {
            margin: 0.5rem 0;
            line-height: 1.4;
        }

        .order-info strong {
            color: #555;
        }

        .order-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1.5rem;
            justify-content: flex-end;
            border-top: 1px solid #eee;
            padding-top: 1rem;
        }

        .status-select {
            padding: 0.5rem;
            border-radius: 5px;
            border: 1px solid #ddd;
            background-color: white;
            font-size: 0.9rem;
            color: #333;
            cursor: pointer;
        }

        .status-select:focus {
            outline: none;
            border-color: #87CEEB;
        }

        .btn-delete {
            background: #ff4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .btn-delete:hover {
            background: #cc0000;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .order-card {
                margin: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-content">
        <div class="orders-container">
            <h1>Gestion des commandes</h1>
            
            <div class="orders-grid">
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

                        <div class="order-info">
                            <p><strong>Client:</strong> <?php echo htmlspecialchars($order['user_pseudo']); ?></p>
                            <p><strong>Date:</strong> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></p>
                            <p><strong>Produits:</strong> <?php echo htmlspecialchars($order['products']); ?></p>
                            <p><strong>Livraison:</strong> <?php echo htmlspecialchars($order['shipping_method']); ?> 
                                (<?php echo number_format($order['shipping_cost'], 2); ?>€)</p>
                            <p><strong>Total:</strong> <?php echo number_format($order['total_amount'], 2); ?>€</p>
                        </div>

                        <div class="order-info">
                            <p><strong>Adresse:</strong></p>
                            <?php if ($address): ?>
                                <p><?php echo htmlspecialchars($address['firstname'] . ' ' . $address['lastname']); ?></p>
                                <p><?php echo htmlspecialchars($address['address']); ?></p>
                                <p><?php echo htmlspecialchars($address['postal'] . ' ' . $address['city']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="order-actions">
                            <select class="status-select" data-order-id="<?php echo $order['order_id']; ?>">
                                <option value="en_attente" <?php echo $order['status'] == 'en_attente' ? 'selected' : ''; ?>>En attente</option>
                                <option value="payé" <?php echo $order['status'] == 'payé' ? 'selected' : ''; ?>>Payé</option>
                                <option value="expédié" <?php echo $order['status'] == 'expédié' ? 'selected' : ''; ?>>Expédié</option>
                                <option value="livré" <?php echo $order['status'] == 'livré' ? 'selected' : ''; ?>>Livré</option>
                                <option value="annulé" <?php echo $order['status'] == 'annulé' ? 'selected' : ''; ?>>Annulé</option>
                            </select>
                            <button class="btn-delete" onclick="deleteOrder(<?php echo $order['order_id']; ?>)">
                                Supprimer
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
    // Fonction pour mettre à jour le statut
    $('.status-select').change(function() {
        const orderId = $(this).data('order-id');
        const newStatus = $(this).val();
        
        $.ajax({
            url: 'ajax/update_order_status.php',
            method: 'POST',
            data: {
                order_id: orderId,
                status: newStatus
            },
            success: function(response) {
                if (response.success) {
                    alert('Statut mis à jour avec succès');
                    location.reload();
                } else {
                    alert('Erreur lors de la mise à jour du statut');
                }
            },
            error: function() {
                alert('Erreur lors de la communication avec le serveur');
            }
        });
    });

    // Fonction pour supprimer une commande
    function deleteOrder(orderId) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette commande ?')) {
            $.ajax({
                url: 'ajax/delete_order.php',
                method: 'POST',
                data: { order_id: orderId },
                success: function(response) {
                    if (response.success) {
                        alert('Commande supprimée avec succès');
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression de la commande');
                    }
                },
                error: function() {
                    alert('Erreur lors de la communication avec le serveur');
                }
            });
        }
    }
    </script>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 