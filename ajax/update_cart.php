<?php
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');
include_once('../_functions/cart.php');
session_start();

if (!is_logged()) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if (!isset($_POST['product_id']) || !isset($_POST['action'])) {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
    exit;
}

$product_id = intval($_POST['product_id']);
$action = $_POST['action'];

try {
    switch($action) {
        case 'increase':
            $stock_remaining = addToCart($product_id, 1, $DB);
            echo json_encode([
                'success' => true,
                'cart_count' => getCartCount(),
                'stock_remaining' => $stock_remaining
            ]);
            break;
            
        case 'decrease':
            $current_quantity = $_SESSION['cart'][$product_id]['quantity'];
            updateCartQuantity($product_id, $current_quantity - 1);
            echo json_encode([
                'success' => true,
                'cart_count' => getCartCount()
            ]);
            break;
            
        case 'remove':
            removeFromCart($product_id);
            echo json_encode([
                'success' => true,
                'cart_count' => getCartCount()
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()
    ]);
} 