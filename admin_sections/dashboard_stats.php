<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}

try {
    // Récupérer les statistiques
    $stats = array();

    // Nombre de produits
    $stmt = $DB->query("SELECT COUNT(*) as count FROM products");
    $stats['products'] = $stmt->fetchColumn();

    // Nombre de commandes - vérifier si la table existe
    try {
        $stmt = $DB->query("SELECT COUNT(*) as count FROM orders");
        $stats['orders'] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $stats['orders'] = 0;
    }

    // Nombre d'utilisateurs
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user");
    $stats['users'] = $stmt->fetchColumn();

    // Chiffre d'affaires (commandes livrées) - vérifier si la table existe
    try {
        $stmt = $DB->query("SELECT SUM(total) as revenue FROM orders WHERE status = 'delivered'");
        $stats['revenue'] = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $stats['revenue'] = 0;
    }

    // Retourner les statistiques en JSON
    header('Content-Type: application/json');
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
?>
