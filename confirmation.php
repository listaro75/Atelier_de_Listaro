<?php
session_start();
require_once('_functions/auth.php');

// Vérifier que l'utilisateur est connecté
if (!is_logged()) {
    header('Location: connexion.php');
    exit();
}

// Ajouter un message de succès dans la session si nécessaire
$_SESSION['success_message'] = 'Votre commande a été confirmée avec succès !';

// Rediriger vers la page des commandes
header('Location: my_orders.php');
exit();
?>