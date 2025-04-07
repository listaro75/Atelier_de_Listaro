<?php
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');
session_start();

if (!is_logged()) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

if (!isset($_POST['prestation_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID manquant']);
    exit;
}

$prestation_id = intval($_POST['prestation_id']);
$user_id = $_SESSION['user_id'];

try {
    // Vérifier si déjà liké
    $stmt = $DB->prepare("SELECT id FROM prestation_likes WHERE prestation_id = ? AND user_id = ?");
    $stmt->execute([$prestation_id, $user_id]);
    $existing_like = $stmt->fetch();

    if ($existing_like) {
        // Supprimer le like
        $stmt = $DB->prepare("DELETE FROM prestation_likes WHERE prestation_id = ? AND user_id = ?");
        $stmt->execute([$prestation_id, $user_id]);
    } else {
        // Ajouter le like
        $stmt = $DB->prepare("INSERT INTO prestation_likes (prestation_id, user_id) VALUES (?, ?)");
        $stmt->execute([$prestation_id, $user_id]);
    }

    // Récupérer le nouveau nombre de likes
    $stmt = $DB->prepare("SELECT COUNT(*) FROM prestation_likes WHERE prestation_id = ?");
    $stmt->execute([$prestation_id]);
    $likes_count = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'likes_count' => $likes_count,
        'is_liked' => !$existing_like
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} 