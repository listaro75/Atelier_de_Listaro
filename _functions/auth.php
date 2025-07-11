<?php
function is_logged() {
    return isset($_SESSION['logged']) && $_SESSION['logged'] === true && isset($_SESSION['id']);
}

function require_auth() {
    if (!is_logged()) {
        header('Location: connexion.php');
        exit();
    }
}

function get_user_id() {
    return $_SESSION['id'] ?? null;
}

function get_user_role() {
    return isset($_SESSION['role']) ? $_SESSION['role'] : null;
}

function is_admin() {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    // Nettoyer la valeur du rôle (supprimer espaces, caractères invisibles)
    $role = trim($_SESSION['role']);
    
    // Support des deux systèmes : numérique (3) et textuel ('admin')
    return $role === 'admin' || $role === '3' || $role === 3;
}

function force_admin() {
    if(!is_admin()) {
        header('Location: index.php');
        exit();
    }
}
?> 