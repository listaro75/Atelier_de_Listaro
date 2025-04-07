<?php
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');
include_once('_functions/cart.php');
session_start();

if (!is_logged()) {
    header('Location: connexion.php');
    exit;
}

$cart_items = getCartItems($DB);
$total = 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mon Panier</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <div class="cart-container">
            <h1>Mon Panier</h1>
            
            <?php if(empty($cart_items)): ?>
                <div class="empty-cart">
                    <p>Votre panier est vide</p>
                    <a href="shop.php" class="btn-shop">Retour à la boutique</a>
                </div>
            <?php else: ?>
                <div class="cart-items">
                    <?php foreach($cart_items as $item): 
                        $subtotal = $item['product']['price'] * $item['quantity'];
                        $total += $subtotal;
                    ?>
                        <div class="cart-item">
                            <div class="item-image">
                                <?php if($item['image']): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['product']['name']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="item-info">
                                <h3><?php echo htmlspecialchars($item['product']['name']); ?></h3>
                                <p class="item-price"><?php echo number_format($item['product']['price'], 2); ?> €</p>
                            </div>
                            <div class="item-quantity">
                                <button onclick="updateQuantity(<?php echo $item['product']['id']; ?>, 'decrease')">-</button>
                                <span><?php echo $item['quantity']; ?></span>
                                <button onclick="updateQuantity(<?php echo $item['product']['id']; ?>, 'increase')">+</button>
                            </div>
                            <div class="item-subtotal">
                                <?php echo number_format($subtotal, 2); ?> €
                            </div>
                            <button class="remove-item" onclick="removeItem(<?php echo $item['product']['id']; ?>)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total">
                        <h3>Total : <?php echo number_format($total, 2); ?> €</h3>
                        <a href="checkout.php" class="btn-checkout">Procéder au paiement</a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function updateQuantity(productId, action) {
        fetch('ajax/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&action=${action}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            }
        });
    }

    function removeItem(productId) {
        if(confirm('Voulez-vous vraiment retirer cet article du panier ?')) {
            fetch('ajax/update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=remove`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }
    }
    </script>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 