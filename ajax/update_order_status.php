<?php
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');
session_start();

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

if (isset($_POST['order_id']) && isset($_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $stmt = $DB->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $success = $stmt->execute([$status, $order_id]);
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'message' => 'Paramètres manquants']);
} 