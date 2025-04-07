<?php
    include_once('_db/connexion_DB.php');
    include_once('_functions/auth.php');
    session_start();

    // Force la connexion pour accéder à cette page
    force_user_connection();

    // Récupération des informations de l'utilisateur
    $req = $DB->prepare("SELECT * FROM user WHERE id = ?");
    $req->execute(array($_SESSION['id']));
    $user = $req->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Mon Profil</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <h1>Mon Profil</h1>
    <div class="profile-container">
        <p>Pseudo : <?php echo htmlspecialchars($user['pseudo']); ?></p>
        <p>Email : <?php echo htmlspecialchars($user['mail']); ?></p>
        <p>Membre depuis : <?php echo date('d/m/Y', strtotime($user['date_creation'])); ?></p>
        <p>Dernière connexion : <?php echo date('d/m/Y H:i', strtotime($user['date_last_conect'])); ?></p>
    </div>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 