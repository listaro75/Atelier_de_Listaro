<?php
/**
 * Statistiques Newsletter - Admin Panel
 * Atelier de Listaro
 */

session_start();
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

try {
    // Compter les abonnés newsletter
    $stmt = $DB->prepare("SELECT COUNT(*) as count FROM user WHERE newsletter = 1");
    $stmt->execute();
    $subscribers = $stmt->fetchColumn();
    
    // Compter les emails envoyés (simulé pour l'instant)
    $sent = 0; // Vous pouvez créer une table pour tracker les envois
    
    // Retourner les statistiques
    echo json_encode([
        'subscribers' => $subscribers,
        'sent' => $sent,
        'success' => true
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erreur lors de la récupération des statistiques',
        'details' => $e->getMessage(),
        'success' => false
    ]);
}
?>
