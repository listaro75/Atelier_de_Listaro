<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

// Vérifier l'authentification admin
if (!is_admin()) {
    http_response_code(403);
    exit(json_encode(['error' => 'Accès refusé']));
}

// Vérifier l'ID du produit
if (!isset($_GET['id']) || empty($_GET['id'])) {
    http_response_code(400);
    exit(json_encode(['error' => 'ID produit manquant']));
}

$product_id = intval($_GET['id']);

try {
    // Récupérer le produit
    $stmt = $DB->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        http_response_code(404);
        exit(json_encode(['error' => 'Produit non trouvé']));
    }
    
    // Récupérer les images du produit
    $stmt = $DB->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Ajouter les images au produit
    $product['images'] = $images;
    
    // Retourner le produit en JSON
    header('Content-Type: application/json');
    echo json_encode($product);
    
} catch (Exception $e) {
    http_response_code(500);
    exit(json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]));
}
?>
