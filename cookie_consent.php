<?php
/**
 * TRAITEMENT DES CONSENTEMENTS COOKIES
 * Endpoint pour recevoir les consentements des utilisateurs
 */

session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/cookie_manager.php');

// Vérifier que c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Méthode non autorisée');
}

try {
    // Récupérer les données JSON ou POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Si pas de JSON, essayer les données POST
    if (!$data) {
        $data = $_POST;
    }
    
    // Récupérer les paramètres
    $action = isset($data['action']) ? $data['action'] : 'accepted';
    $preferences = isset($data['preferences']) ? $data['preferences'] : [];
    
    // Valider les préférences
    if (!is_array($preferences)) {
        $preferences = [];
    }
    
    // Préférences par défaut
    $default_preferences = [
        'essential' => true,
        'analytics' => false,
        'preferences' => false,
        'marketing' => false
    ];
    
    $preferences = array_merge($default_preferences, $preferences);
    
    // Initialiser le gestionnaire de cookies
    $cookieManager = new CookieManager($DB);
    
    // Déterminer le consentement basé sur l'action
    $consent = ($action === 'accepted' || $action === 'customized');
    
    // Enregistrer le consentement
    $cookieManager->setConsent($consent, $preferences);
    
    // Collecter les données si autorisé
    if ($consent) {
        $userData = $cookieManager->collectUserData();
        $cookieManager->saveCollectedData($userData);
    }
    
    // Réponse JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Consentement enregistré avec succès',
        'consent' => $consent,
        'preferences' => $preferences
    ]);
    
} catch (Exception $e) {
    error_log("Erreur lors du traitement du consentement : " . $e->getMessage());
    
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur lors de l\'enregistrement du consentement'
    ]);
}
?>
