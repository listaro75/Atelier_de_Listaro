<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès refusé']));
}

// Vérifier que l'ID du produit est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'ID produit requis']));
}

$product_id = intval($_GET['id']);

try {
    // Récupérer toutes les images du produit
    $stmt = $DB->prepare("SELECT id, image_path, is_main FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les images en JSON
    header('Content-Type: application/json');
    echo json_encode($images);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des images: ' . $e->getMessage()]);
}
?>
