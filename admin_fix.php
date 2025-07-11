<?php
session_start();
include_once('_db/connexion_DB.php');

// Fonction is_admin de remplacement (corrigée)
function is_admin_fixed() {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    $role = trim($_SESSION['role']);
    return $role === 'admin' || $role === '3' || $role === 3;
}

// Vérifier si l'utilisateur est admin avec notre fonction corrigée
if (!is_admin_fixed()) {
    echo "<!DOCTYPE html><html><head><title>Accès refusé</title></head><body>";
    echo "<h1>Accès refusé</h1>";
    echo "<p>Vous n'avez pas les droits administrateur.</p>";
    echo "<p>Session actuelle :</p><pre>" . print_r($_SESSION, true) . "</pre>";
    echo "<a href='test_admin.php'>Diagnostiquer le problème</a><br>";
    echo "<a href='index.php'>Retour à l'accueil</a>";
    echo "</body></html>";
    exit();
}

// Si on arrive ici, l'utilisateur est admin !
echo "<!DOCTYPE html><html><head><title>Admin OK</title></head><body>";
echo "<h1 style='color: green;'>✅ Accès administrateur confirmé !</h1>";
echo "<p>Votre fonction is_admin() standard ne fonctionne pas, mais vous êtes bien administrateur.</p>";
echo "<h2>Solutions :</h2>";
echo "<h3>Solution 1 : Utilisez cette page temporaire</h3>";
echo "<a href='admin_temp.php' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Administration temporaire</a><br><br>";

echo "<h3>Solution 2 : Corriger le fichier auth.php</h3>";
echo "<p>Le problème semble venir du fichier <code>_functions/auth.php</code>. Voulez-vous que je le corrige ?</p>";
echo "<a href='?fix_auth=1' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px;'>Corriger auth.php</a><br><br>";

// Correction automatique du fichier auth.php
if (isset($_GET['fix_auth']) && $_GET['fix_auth'] == '1') {
    $auth_file = '_functions/auth.php';
    
    // Lire le contenu actuel
    $content = file_get_contents($auth_file);
    
    // Remplacer la fonction is_admin
    $new_function = 'function is_admin() {
    if (!isset($_SESSION[\'role\'])) {
        return false;
    }
    
    // Nettoyer la valeur du rôle (supprimer espaces, caractères invisibles)
    $role = trim($_SESSION[\'role\']);
    
    // Support des deux systèmes : numérique (3) et textuel (\'admin\')
    return $role === \'admin\' || $role === \'3\' || $role === 3;
}';

    // Chercher et remplacer l'ancienne fonction
    $pattern = '/function is_admin\(\) \{[^}]*\}/s';
    if (preg_match($pattern, $content)) {
        $new_content = preg_replace($pattern, $new_function, $content);
        
        // Sauvegarder l'ancien fichier
        file_put_contents($auth_file . '.backup', $content);
        
        // Écrire le nouveau contenu
        if (file_put_contents($auth_file, $new_content)) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "✅ Fichier auth.php corrigé ! L'ancien fichier a été sauvegardé en auth.php.backup<br>";
            echo "<a href='test_admin.php'>Tester maintenant</a> | <a href='administrateur.php'>Aller à l'administration</a>";
            echo "</div>";
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
            echo "❌ Erreur lors de la sauvegarde du fichier";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; margin: 20px 0; border-radius: 5px;'>";
        echo "⚠️ Impossible de trouver la fonction is_admin() dans le fichier";
        echo "</div>";
    }
}

echo "<h3>Debug info :</h3>";
echo "<pre>";
echo "Session : " . print_r($_SESSION, true);
echo "is_admin_fixed() : " . (is_admin_fixed() ? 'TRUE' : 'FALSE') . "\n";
if (function_exists('is_admin')) {
    echo "is_admin() standard : " . (is_admin() ? 'TRUE' : 'FALSE') . "\n";
} else {
    echo "is_admin() standard : FONCTION NON TROUVÉE\n";
}
echo "</pre>";

echo "<a href='index.php'>Retour à l'accueil</a>";
echo "</body></html>";
?>
