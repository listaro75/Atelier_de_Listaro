<?php
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');
session_start();

if (!is_logged()) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

$product_id = intval($_POST['product_id']);
$user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur non trouvé']);
    exit;
}

try {
    // Vérifier si déjà liké
    $stmt = $DB->prepare("SELECT id FROM product_likes WHERE product_id = ? AND user_id = ?");
    $stmt->execute([$product_id, $user_id]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Supprimer le like
        $stmt = $DB->prepare("DELETE FROM product_likes WHERE product_id = ? AND user_id = ?");
        $stmt->execute([$product_id, $user_id]);
    } else {
        // Ajouter le like
        $stmt = $DB->prepare("INSERT INTO product_likes (product_id, user_id) VALUES (?, ?)");
        $stmt->execute([$product_id, $user_id]);
    }

    // Récupérer le nouveau nombre de likes
    $stmt = $DB->prepare("SELECT COUNT(*) FROM product_likes WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $likes_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'likes_count' => $likes_count,
        'is_liked' => !$existing_like
    ]);
} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 