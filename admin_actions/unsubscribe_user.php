<?php
/**
 * Désabonner un utilisateur de la newsletter - Admin Panel
 * Atelier de Listaro
 */

session_start();
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur manquant']);
    exit();
}

$user_id = (int)$data['user_id'];

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit();
}

try {
    // Vérifier que l'utilisateur existe et est abonné
    $stmt = $DB->prepare("SELECT pseudo as username, mail as email FROM user WHERE id = ? AND newsletter = 1");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé ou déjà désabonné']);
        exit();
    }
    
    // Désabonner l'utilisateur
    $stmt = $DB->prepare("UPDATE user SET newsletter = 0 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true, 
            'message' => $user['username'] . ' (' . $user['email'] . ') a été désabonné(e) de la newsletter'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
    }
    
} catch (Exception $e) {
    error_log("Erreur désabonnement newsletter: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur lors du désabonnement'
    ]);
}
?>
