<?php
session_start();
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');

// Vérifier les permissions
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès refusé']);
    exit();
}

// Vérifier les données POST
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$order_id = intval($_POST['order_id']);
$status = $_POST['status'];

// Vérifier que le statut est valide
$valid_statuses = ['pending', 'processing', 'delivered', 'cancelled'];
if (!in_array($status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Statut invalide']);
    exit();
}

try {
    // Mettre à jour le statut de la commande
    $stmt = $DB->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
