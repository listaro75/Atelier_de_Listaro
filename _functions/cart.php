<?php
function initCart() {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

function getCartCount() {
    initCart();
    return array_sum(array_column($_SESSION['cart'], 'quantity'));
}

function addToCart($product_id, $quantity = 1, $DB) {
    initCart();
    
    // Vérifier le stock disponible
    $stmt = $DB->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch();
    
    if (!$product) {
        throw new Exception('Produit non trouvé');
    }
    
    $current_quantity = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
    $new_quantity = $current_quantity + $quantity;
    
    // Vérifier si la nouvelle quantité ne dépasse pas le stock
    if ($new_quantity > $product['stock']) {
        throw new Exception('Stock insuffisant');
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity'] = $new_quantity;
    } else {
        $_SESSION['cart'][$product_id] = [
            'product_id' => $product_id,
            'quantity' => $quantity
        ];
    }
    
    return $product['stock'] - $new_quantity; // Retourne le stock restant
}

function removeFromCart($product_id) {
    initCart();
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
}

function updateCartQuantity($product_id, $quantity) {
    initCart();
    if ($quantity <= 0) {
        removeFromCart($product_id);
    } else {
        $_SESSION['cart'][$product_id]['quantity'] = $quantity;
    }
}

function getCartItems($DB) {
    initCart();
    $items = [];
    
    foreach ($_SESSION['cart'] as $product_id => $item) {
        $stmt = $DB->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product) {
            // Récupérer l'image principale
            $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1");
            $stmt->execute([$product_id]);
            $image = $stmt->fetch();
            
            $items[] = [
                'product' => $product,
                'image' => $image ? $image['image_path'] : null,
                'quantity' => $item['quantity']
            ];
        }
    }
    
    return $items;
} 