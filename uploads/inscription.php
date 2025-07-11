<?php
    include_once('_db/connexion_DB.php');

    if(!empty($_POST)){
        extract($_POST);

        $valid = true;

        if(isset($_POST['inscription'])){
            $pseudo = trim($pseudo);
            $mail = trim($mail);
            $confmail = trim($confmail);
            $password = trim($password);
            $confpassword = trim($confpassword);

            // Validation du pseudo
            if(empty($pseudo)){
                $valid = false;
                $err_pseudo = "Le pseudo est vide.";
            } elseif(strlen($pseudo) < 3 || strlen($pseudo) > 30) {
                $valid = false;
                $err_pseudo = "Le pseudo doit contenir entre 3 et 30 caractères.";
            } elseif(!preg_match("/^[a-zA-Z0-9_-]+$/", $pseudo)) {
                $valid = false;
                $err_pseudo = "Le pseudo ne peut contenir que des lettres, chiffres, tirets et underscores.";
            } else {
                $req = $DB->prepare("SELECT id FROM user WHERE pseudo = ?");
                $req->execute(array($pseudo));
                if ($req->fetch()) {
                    $valid = false;
                    $err_pseudo = "Le pseudo est déjà pris.";
                }
            }

            // Validation du mail
            if(empty($mail)){
                $valid = false;
                $err_mail = "L'adresse mail est vide.";
            } elseif(!filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $valid = false;
                $err_mail = "Format d'email invalide.";
            } elseif(strlen($mail) > 255) {
                $valid = false;
                $err_mail = "L'adresse email est trop longue.";
            } elseif($mail !== $confmail) {
                $valid = false;
                $err_mail = "Les mails ne correspondent pas.";
            } else {
                $req = $DB->prepare("SELECT id FROM user WHERE mail = ?");
                $req->execute(array($mail));
                if ($req->fetch()) {
                    $valid = false;
                    $err_mail = "L'adresse mail est déjà utilisée.";
                }
            }

            // Validation du mot de passe
            if(empty($password)){
                $valid = false;
                $err_password = "Le mot de passe est vide.";
            } elseif(strlen($password) < 8) {
                $valid = false;
                $err_password = "Le mot de passe doit contenir au moins 8 caractères.";
            } elseif(strlen($password) > 72) {
                $valid = false;
                $err_password = "Le mot de passe est trop long (maximum 72 caractères).";
            } elseif(!preg_match("/[A-Z]/", $password)) {
                $valid = false;
                $err_password = "Le mot de passe doit contenir au moins une majuscule.";
            } elseif(!preg_match("/[a-z]/", $password)) {
                $valid = false;
                $err_password = "Le mot de passe doit contenir au moins une minuscule.";
            } elseif(!preg_match("/[0-9]/", $password)) {
                $valid = false;
                $err_password = "Le mot de passe doit contenir au moins un chiffre.";
            } elseif(!preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
                $valid = false;
                $err_password = "Le mot de passe doit contenir au moins un caractère spécial (!@#$%^&*(),.?\":{}|<>).";
            } elseif($password !== $confpassword) {
                $valid = false;
                $err_password = "Les mots de passe ne correspondent pas.";
            }

            // Protection contre les attaques par force brute
            session_start();
            if (!isset($_SESSION['attempt_time'])) {
                $_SESSION['attempt_time'] = time();
                $_SESSION['attempt_count'] = 1;
            } else {
                if (time() - $_SESSION['attempt_time'] < 300) { // 5 minutes
                    if ($_SESSION['attempt_count'] >= 5) {
                        $valid = false;
                        $err_general = "Trop de tentatives. Veuillez réessayer dans 5 minutes.";
                    } else {
                        $_SESSION['attempt_count']++;
                    }
                } else {
                    $_SESSION['attempt_time'] = time();
                    $_SESSION['attempt_count'] = 1;
                }
            }

            // Si le formulaire est valide, on insère l'utilisateur
            if ($valid) {
                // Hachage du mot de passe avec BCRYPT
                $hashed_password = password_hash($password, PASSWORD_BCRYPT);
                
                // Date de création et dernière connexion
                $date_creation = date('Y-m-d H:i:s');
                
                try {
                    // Débogage temporaire
                    echo "<pre>";
                    var_dump([
                        'pseudo' => $pseudo,
                        'mail' => $mail,
                        'mdp' => $hashed_password,
                        'date_creation' => $date_creation
                    ]);
                    echo "</pre>";
                    
                    // Préparation de la requête SQL pour l'insertion
                    $req = $DB->prepare("INSERT INTO user (pseudo, mail, mdp, date_creation, date_last_conect, role) 
                                        VALUES (:pseudo, :mail, :mdp, :date_creation, :date_last_conect, :role)");
                    
                    $req->execute(array(
                        'pseudo' => $pseudo,
                        'mail' => $mail,
                        'mdp' => $hashed_password,
                        'date_creation' => $date_creation,
                        'date_last_conect' => $date_creation,
                        'role' => 1  // 1 = utilisateur normal
                    ));

                    if($req->rowCount() > 0) {
                        // L'insertion a réussi
                        header('Location: connexion.php');
                        exit;
                    } else {
                        $err_general = "Échec de l'insertion dans la base de données.";
                    }
                } catch(PDOException $e) {
                    $err_general = "Une erreur est survenue lors de l'inscription : " . $e->getMessage();
                }
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Inscription</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <link rel="stylesheet" href="_css/auth.css">
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="auth-page">
        <div class="form-container">
            <h1>Inscription</h1>
            <?php if (isset($err_general)) { echo '<div class="error">' . $err_general . '</div>'; } ?>
            <form method="post">
                <label>Pseudo</label>
                <?php if (isset($err_pseudo)) { echo '<div class="error">' . $err_pseudo . '</div>'; } ?>
                <input type="text" name="pseudo" value="<?php if (isset($pseudo)) { echo $pseudo; } ?>" placeholder="Pseudo" required>

                <label>Mail</label>
                <?php if (isset($err_mail)) { echo '<div class="error">' . $err_mail . '</div>'; } ?>
                <input type="email" name="mail" value="<?php if (isset($mail)) { echo $mail; } ?>" placeholder="Mail" required>

                <label>Confirmation Mail</label>
                <input type="email" name="confmail" value="<?php if (isset($confmail)) { echo $confmail; } ?>" placeholder="Confirmation Mail" required>

                <label>Mot de passe</label>
                <?php if (isset($err_password)) { echo '<div class="error">' . $err_password . '</div>'; } ?>
                <input type="password" name="password" placeholder="Mot de passe" required>

                <label>Confirmation Mot de passe</label>
                <input type="password" name="confpassword" placeholder="Confirmation Mot de passe" required>

                <button type="submit" name="inscription">Inscription</button>
            </form>
            
            <div class="auth-links">
                <a href="connexion.php" class="btn-secondary">Déjà inscrit ? Se connecter</a>
            </div>
        </div>
    </div>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html>
