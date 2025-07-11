<?php
    include_once('_db/connexion_DB.php');

    if(!empty($_POST)){
        extract($_POST);

        $valid = true;

        if(isset($_POST['inscription'])){
            $pseudo = trim($pseudo);
            $nom = trim($nom);
            $prenom = trim($prenom);
            $mail = trim($mail);
            $password = trim($password);
            $confpassword = trim($confpassword);
            $telephone = isset($telephone) ? trim($telephone) : '';
            $conditions = isset($conditions) ? true : false;
            $newsletter = isset($newsletter) ? true : false;

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

            // Validation du nom
            if(empty($nom)){
                $valid = false;
                $err_nom = "Le nom est vide.";
            } elseif(strlen($nom) < 2 || strlen($nom) > 50) {
                $valid = false;
                $err_nom = "Le nom doit contenir entre 2 et 50 caractères.";
            } elseif(!preg_match("/^[a-zA-ZÀ-ÿ\s\-\']+$/u", $nom)) {
                $valid = false;
                $err_nom = "Le nom ne peut contenir que des lettres, espaces, tirets et apostrophes.";
            }

            // Validation du prénom
            if(empty($prenom)){
                $valid = false;
                $err_prenom = "Le prénom est vide.";
            } elseif(strlen($prenom) < 2 || strlen($prenom) > 50) {
                $valid = false;
                $err_prenom = "Le prénom doit contenir entre 2 et 50 caractères.";
            } elseif(!preg_match("/^[a-zA-ZÀ-ÿ\s\-\']+$/u", $prenom)) {
                $valid = false;
                $err_prenom = "Le prénom ne peut contenir que des lettres, espaces, tirets et apostrophes.";
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

            // Validation du téléphone (optionnel)
            if(!empty($telephone)) {
                if(strlen($telephone) < 10 || strlen($telephone) > 20) {
                    $valid = false;
                    $err_telephone = "Le numéro de téléphone doit contenir entre 10 et 20 caractères.";
                } elseif(!preg_match("/^[0-9\s\-\+\(\)\.]+$/", $telephone)) {
                    $valid = false;
                    $err_telephone = "Le numéro de téléphone contient des caractères invalides.";
                }
            }

            // Validation des conditions d'utilisation (obligatoire)
            if(!$conditions) {
                $valid = false;
                $err_conditions = "Vous devez accepter les conditions d'utilisation.";
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
                    // Vérifier si les colonnes existent
                    $has_telephone = $DB->query("SHOW COLUMNS FROM user LIKE 'telephone'")->rowCount() > 0;
                    $has_nom = $DB->query("SHOW COLUMNS FROM user LIKE 'nom'")->rowCount() > 0;
                    $has_prenom = $DB->query("SHOW COLUMNS FROM user LIKE 'prenom'")->rowCount() > 0;
                    $has_newsletter = $DB->query("SHOW COLUMNS FROM user LIKE 'newsletter'")->rowCount() > 0;
                    
                    // Construire la requête selon les colonnes disponibles
                    $fields = "pseudo, mail, mdp, date_creation, date_last_conect, role";
                    $values = ":pseudo, :mail, :mdp, :date_creation, :date_last_conect, :role";
                    $params = array(
                        'pseudo' => $pseudo,
                        'mail' => $mail,
                        'mdp' => $hashed_password,
                        'date_creation' => $date_creation,
                        'date_last_conect' => $date_creation,
                        'role' => 1  // 1 = utilisateur normal
                    );
                    
                    if ($has_nom) {
                        $fields .= ", nom";
                        $values .= ", :nom";
                        $params['nom'] = $nom;
                    }
                    
                    if ($has_prenom) {
                        $fields .= ", prenom";
                        $values .= ", :prenom";
                        $params['prenom'] = $prenom;
                    }
                    
                    if ($has_telephone) {
                        $fields .= ", telephone";
                        $values .= ", :telephone";
                        $params['telephone'] = $telephone;
                    }
                    
                    if ($has_newsletter) {
                        $fields .= ", newsletter";
                        $values .= ", :newsletter";
                        $params['newsletter'] = $newsletter ? 1 : 0;
                    }
                    
                    $req = $DB->prepare("INSERT INTO user ($fields) VALUES ($values)");
                    $req->execute($params);

                    if($req->rowCount() > 0) {
                        // L'insertion a réussi - Envoyer l'email de bienvenue
                        require_once('_functions/mail.php');
                        
                        // Tentative d'envoi de l'email de bienvenue
                        $emailSent = sendWelcomeEmail($pseudo, $mail);
                        
                        if ($emailSent) {
                            $success_message = "Inscription réussie ! Un email de bienvenue vous a été envoyé à " . htmlspecialchars($mail) . ". Vous allez être redirigé vers la page de connexion.";
                        } else {
                            $success_message = "Inscription réussie ! Vous allez être redirigé vers la page de connexion. (Email de bienvenue non envoyé - vérifiez votre configuration email)";
                        }
                        
                        // Redirection après 3 secondes pour laisser le temps de voir le message
                        echo "<script>
                            setTimeout(function() {
                                window.location.href = 'connexion.php';
                            }, 3000);
                        </script>";
                        
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
            <?php if (isset($success_message)) { 
                echo '<div class="success" style="background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;">' . $success_message . '</div>'; 
            } ?>
            <form method="post">
                <label>Pseudo</label>
                <?php if (isset($err_pseudo)) { echo '<div class="error">' . $err_pseudo . '</div>'; } ?>
                <input type="text" name="pseudo" value="<?php if (isset($pseudo)) { echo htmlspecialchars($pseudo); } ?>" placeholder="Pseudo" required>

                <label>Nom</label>
                <?php if (isset($err_nom)) { echo '<div class="error">' . $err_nom . '</div>'; } ?>
                <input type="text" name="nom" value="<?php if (isset($nom)) { echo htmlspecialchars($nom); } ?>" placeholder="Nom" required>

                <label>Prénom</label>
                <?php if (isset($err_prenom)) { echo '<div class="error">' . $err_prenom . '</div>'; } ?>
                <input type="text" name="prenom" value="<?php if (isset($prenom)) { echo htmlspecialchars($prenom); } ?>" placeholder="Prénom" required>

                <label>Mail</label>
                <?php if (isset($err_mail)) { echo '<div class="error">' . $err_mail . '</div>'; } ?>
                <input type="email" name="mail" value="<?php if (isset($mail)) { echo htmlspecialchars($mail); } ?>" placeholder="Mail" required>

                <label>Téléphone (optionnel)</label>
                <?php if (isset($err_telephone)) { echo '<div class="error">' . $err_telephone . '</div>'; } ?>
                <input type="tel" name="telephone" value="<?php if (isset($telephone)) { echo htmlspecialchars($telephone); } ?>" placeholder="Numéro de téléphone (ex: 01 23 45 67 89)">

                <label>Mot de passe</label>
                <?php if (isset($err_password)) { echo '<div class="error">' . $err_password . '</div>'; } ?>
                <input type="password" name="password" placeholder="Mot de passe" required>

                <label>Confirmation Mot de passe</label>
                <input type="password" name="confpassword" placeholder="Confirmation Mot de passe" required>

                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <?php if (isset($err_conditions)) { echo '<div class="error">' . $err_conditions . '</div>'; } ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="conditions" value="1" <?php if (isset($conditions) && $conditions) { echo 'checked'; } ?> required>
                            J'accepte les <a href="#" target="_blank">conditions d'utilisation</a> *
                        </label>
                    </div>
                    
                    <div class="checkbox-item">
                        <label class="checkbox-label">
                            <input type="checkbox" name="newsletter" value="1" <?php if (isset($newsletter) && $newsletter) { echo 'checked'; } ?>>
                            Je souhaite recevoir la newsletter
                        </label>
                    </div>
                </div>

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
