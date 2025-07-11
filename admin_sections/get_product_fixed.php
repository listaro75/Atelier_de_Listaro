<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');
include_once(__DIR__ . '/config.php');

// Log des erreurs pour debug
error_log("GET_PRODUCT_FIXED: Début du script");

if (!is_admin()) {
    error_log("GET_PRODUCT_FIXED: Accès refusé - utilisateur non admin");
    http_response_code(403);
    exit(json_encode(['error' => 'Accès refusé']));
}

if (!isset($_GET['id'])) {
    error_log("GET_PRODUCT_FIXED: ID manquant dans la requête");
    http_response_code(400);
    exit(json_encode(['error' => 'ID manquant']));
}

$product_id = $_GET['id'];
error_log("GET_PRODUCT_FIXED: Recherche produit ID: " . $product_id . " (type: " . gettype($product_id) . ")");

try {
    // Forcer la conversion en entier pour éviter les problèmes de type
    $product_id_int = (int)$product_id;
    error_log("GET_PRODUCT_FIXED: ID converti en int: " . $product_id_int);
    
    // Vérifier d'abord si le produit existe
    $stmt = $DB->prepare("SELECT COUNT(*) as count FROM products WHERE id = ?");
    $stmt->execute([$product_id_int]);
    $count = $stmt->fetchColumn();
    error_log("GET_PRODUCT_FIXED: Produits trouvés: " . $count);
    
    if ($count == 0) {
        // Essayer avec l'ID original
        $stmt = $DB->prepare("SELECT COUNT(*) as count FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $count_orig = $stmt->fetchColumn();
        error_log("GET_PRODUCT_FIXED: Produits trouvés avec ID original: " . $count_orig);
        
        if ($count_orig == 0) {
            // Lister les IDs disponibles
            $stmt = $DB->query("SELECT id FROM products LIMIT 5");
            $available_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            error_log("GET_PRODUCT_FIXED: IDs disponibles: " . implode(', ', $available_ids));
            
            http_response_code(404);
            exit(json_encode(['error' => 'Produit non trouvé', 'available_ids' => $available_ids]));
        }
        
        // Utiliser l'ID original
        $product_id = $product_id;
    } else {
        // Utiliser l'ID converti
        $product_id = $product_id_int;
    }
    
    // Récupérer le produit
    $stmt = $DB->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        error_log("GET_PRODUCT_FIXED: Produit non trouvé après vérification");
        http_response_code(404);
        exit(json_encode(['error' => 'Produit non trouvé après vérification']));
    }
    
    error_log("GET_PRODUCT_FIXED: Produit trouvé: " . $product['name']);
    
    // Récupérer les images
    $stmt = $DB->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC, id ASC");
    $stmt->execute([$product_id]);
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $product['images'] = $images;
    error_log("GET_PRODUCT_FIXED: Images récupérées: " . count($images));
    
    header('Content-Type: application/json');
    echo json_encode($product);
    
} catch (Exception $e) {
    error_log("GET_PRODUCT_FIXED: Erreur Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
