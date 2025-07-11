<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    // Rediriger vers la page de connexion avec un message
    header('Location: connexion.php?error=admin_required');
    exit();
}

// Rediriger vers le panneau d'administration complet
header('Location: admin_panel.php');
exit();
?>
