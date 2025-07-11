<?php
/**
 * =============================================================================
 * SYSTÈME D'AUTHENTIFICATION SÉCURISÉ
 * =============================================================================
 * Atelier de Listaro - Gestion des utilisateurs et sécurité
 * Date: 8 juillet 2025
 * =============================================================================
 */

// Chargement des variables d'environnement
require_once(__DIR__ . '/../_config/env.php');

/**
 * Vérifier si l'utilisateur est connecté
 */
function is_logged() {
    return isset($_SESSION['logged']) && 
           $_SESSION['logged'] === true && 
           isset($_SESSION['id']) && 
           isset($_SESSION['last_activity']);
}

/**
 * Vérifier si la session est encore valide
 */
function is_session_valid() {
    if (!is_logged()) {
        return false;
    }
    
    $session_lifetime = (int)getenv('SESSION_LIFETIME') ?: 3600;
    $last_activity = $_SESSION['last_activity'] ?? 0;
    
    if (time() - $last_activity > $session_lifetime) {
        session_destroy();
        return false;
    }
    
    // Mettre à jour l'activité
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Forcer l'authentification
 */
function require_auth() {
    if (!is_session_valid()) {
        header('Location: connexion.php?error=session_expired');
        exit();
    }
}

/**
 * Obtenir l'ID de l'utilisateur connecté
 */
function get_user_id() {
    return is_logged() ? $_SESSION['id'] : null;
}

/**
 * Obtenir le rôle de l'utilisateur connecté
 */
function get_user_role() {
    return is_logged() ? ($_SESSION['role'] ?? 'user') : null;
}

/**
 * Vérifier si l'utilisateur est administrateur
 */
function is_admin() {
    return is_logged() && get_user_role() === 'admin';
}

/**
 * Forcer les droits administrateur
 */
function force_admin() {
    if (!is_admin()) {
        header('Location: index.php?error=access_denied');
        exit();
    }
}

/**
 * Obtenir le pseudo de l'utilisateur connecté
 */
function get_user_pseudo() {
    return is_logged() ? ($_SESSION['pseudo'] ?? 'Utilisateur') : null;
}

/**
 * Obtenir l'email de l'utilisateur connecté
 */
function get_user_email() {
    return is_logged() ? ($_SESSION['email'] ?? '') : null;
}

/**
 * Vérifier les tentatives de connexion (protection force brute)
 */
function check_login_attempts() {
    $max_attempts = (int)getenv('MAX_LOGIN_ATTEMPTS') ?: 5;
    $lockout_duration = (int)getenv('LOGIN_LOCKOUT_DURATION') ?: 300;
    
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = 0;
        $_SESSION['last_attempt_time'] = time();
        return true;
    }
    
    $time_since_last = time() - $_SESSION['last_attempt_time'];
    
    // Réinitialiser si assez de temps s'est écoulé
    if ($time_since_last > $lockout_duration) {
        $_SESSION['login_attempts'] = 0;
        return true;
    }
    
    // Vérifier si trop de tentatives
    if ($_SESSION['login_attempts'] >= $max_attempts) {
        $remaining_time = $lockout_duration - $time_since_last;
        throw new Exception("Trop de tentatives. Réessayez dans " . ceil($remaining_time / 60) . " minute(s).");
    }
    
    return true;
}

/**
 * Enregistrer une tentative de connexion échouée
 */
function record_failed_login() {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
    $_SESSION['last_attempt_time'] = time();
}

/**
 * Réinitialiser les tentatives de connexion
 */
function reset_login_attempts() {
    unset($_SESSION['login_attempts']);
    unset($_SESSION['last_attempt_time']);
}

/**
 * Créer une session sécurisée
 */
function create_secure_session($user_data) {
    // Régénérer l'ID de session pour éviter la fixation
    session_regenerate_id(true);
    
    // Définir les données de session
    $_SESSION['id'] = $user_data['id'];
    $_SESSION['pseudo'] = $user_data['pseudo'];
    $_SESSION['email'] = $user_data['mail'];
    $_SESSION['role'] = $user_data['role'];
    $_SESSION['logged'] = true;
    $_SESSION['last_activity'] = time();
    $_SESSION['login_time'] = time();
    
    // Réinitialiser les tentatives
    reset_login_attempts();
    
    // Log de sécurité
    error_log("Connexion réussie pour l'utilisateur: " . $user_data['pseudo'] . " (ID: " . $user_data['id'] . ")");
}

/**
 * Détruire la session de manière sécurisée
 */
function destroy_session() {
    $user_id = get_user_id();
    $pseudo = get_user_pseudo();
    
    // Nettoyer toutes les données de session
    $_SESSION = array();
    
    // Détruire le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Détruire la session
    session_destroy();
    
    // Log de sécurité
    if ($user_id) {
        error_log("Déconnexion de l'utilisateur: $pseudo (ID: $user_id)");
    }
}

/**
 * Vérifier la force d'un mot de passe
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une minuscule";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
    }
    
    return $errors;
}

/**
 * Générer un hash sécurisé pour un mot de passe
 */
function generate_secure_password_hash($password) {
    $options = [
        'cost' => 12, // Plus élevé = plus sécurisé mais plus lent
    ];
    return password_hash($password, PASSWORD_BCRYPT, $options);
}

/**
 * Vérifier les permissions pour une action
 */
function check_permission($action, $resource = null) {
    $user_role = get_user_role();
    
    $permissions = [
        'admin' => ['*'], // Admin peut tout faire
        'user' => [
            'view_products',
            'view_prestations',
            'create_order',
            'view_own_orders',
            'edit_own_profile'
        ]
    ];
    
    $user_permissions = $permissions[$user_role] ?? [];
    
    return in_array('*', $user_permissions) || in_array($action, $user_permissions);
}

/**
 * Fonction utilitaire pour logger les actions importantes
 */
function log_security_event($event, $details = '') {
    $user_id = get_user_id();
    $pseudo = get_user_pseudo();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    $log_message = "[SECURITY] $event - User: $pseudo (ID: $user_id) - IP: $ip";
    if ($details) {
        $log_message .= " - Details: $details";
    }
    
    error_log($log_message);
}

/**
 * Générer un token CSRF
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Vérifier un token CSRF
 */
function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Configuration de sécurité pour les sessions
function configure_secure_session() {
    // Configuration des cookies de session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Strict');
    
    // Durée de vie des sessions
    $session_lifetime = (int)getenv('SESSION_LIFETIME') ?: 3600;
    ini_set('session.gc_maxlifetime', $session_lifetime);
    
    // Nom du cookie de session
    session_name('ATELIER_SESSION');
}

// Initialiser la configuration sécurisée
configure_secure_session();

?>
