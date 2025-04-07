<?php
session_start();
require_once('../_db/connexion_DB.php');
require_once('../_functions/auth.php');

header('Content-Type: application/json');

if (!is_admin()) {
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

try {
    $orderId = $_POST['order_id'] ?? null;
    
    if (!$orderId) {
        throw new Exception('ID de commande manquant');
    }

    $DB->beginTransaction();

    // Supprimer d'abord les items de la commande
    $stmt = $DB->prepare("DELETE FROM order_items WHERE order_id = ?");
    $stmt->execute([$orderId]);

    // Puis supprimer la commande
    $stmt = $DB->prepare("DELETE FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);

    $DB->commit();
    
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    $DB->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 