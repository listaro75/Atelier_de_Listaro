<?php
	session_start();
	require_once('_db/connexion_DB.php');
	require_once('_functions/auth.php');

	// Si déjà connecté, rediriger vers la page d'accueil
	if (is_logged()) {
		header('Location: index.php');
		exit();
	}

	if(!empty($_POST)){
		extract($_POST);
		$valid = true;

		if(isset($_POST['connexion'])){
			$pseudo = trim($pseudo);
			$password = trim($password);

			// Validation du pseudo
			if(empty($pseudo)){
				$valid = false;
				$err_pseudo = "Le pseudo est vide.";
			}

			// Validation du mot de passe
			if(empty($password)){
				$valid = false;
				$err_password = "Le mot de passe est vide.";
			}

			// Protection contre les attaques par force brute
			if (!isset($_SESSION['login_attempt_time'])) {
				$_SESSION['login_attempt_time'] = time();
				$_SESSION['login_attempt_count'] = 1;
			} else {
				if (time() - $_SESSION['login_attempt_time'] < 300) { // 5 minutes
					if ($_SESSION['login_attempt_count'] >= 5) {
						$valid = false;
						$err_general = "Trop de tentatives. Veuillez réessayer dans 5 minutes.";
					} else {
						$_SESSION['login_attempt_count']++;
					}
				} else {
					$_SESSION['login_attempt_time'] = time();
					$_SESSION['login_attempt_count'] = 1;
				}
			}

			// Vérification des identifiants
			if($valid){
				$req = $DB->prepare("SELECT * FROM user WHERE pseudo = ?");
				$req->execute(array($pseudo));
				$user = $req->fetch();

				if($user){
					if(password_verify($password, $user['mdp'])){
						// Ajoutons des logs pour debug
						error_log("User data: " . print_r($user, true));
						error_log("Role: " . $user['role']);
						
						// Création de la session
						$_SESSION['id'] = $user['id'];
						$_SESSION['pseudo'] = $user['pseudo'];
						$_SESSION['role'] = $user['role'];
						$_SESSION['logged'] = true;
						
						error_log("Session data: " . print_r($_SESSION, true));

						// Mise à jour de la dernière connexion
						$update = $DB->prepare("UPDATE user SET date_last_conect = NOW() WHERE id = ?");
						$update->execute(array($user['id']));

						// Réinitialisation des tentatives de connexion
						unset($_SESSION['login_attempt_time']);
						unset($_SESSION['login_attempt_count']);

						// Redirection vers la page d'accueil
						header('Location: index.php');
						exit;
					} else {
						$valid = false;
						$err_password = "Mot de passe incorrect.";
					}
				} else {
					$valid = false;
					$err_pseudo = "Ce pseudo n'existe pas.";
				}
			}
		}
	}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Connexion</title>
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
            <h1>Connexion</h1>
            <?php if (isset($err_general)) { echo '<div class="error">' . $err_general . '</div>'; } ?>
            <form method="post">
                <label>Pseudo</label>
                <?php if (isset($err_pseudo)) { echo '<div class="error">' . $err_pseudo . '</div>'; } ?>
                <input type="text" name="pseudo" value="<?php if (isset($pseudo)) { echo $pseudo; } ?>" placeholder="Pseudo" required>

                <label>Mot de passe</label>
                <?php if (isset($err_password)) { echo '<div class="error">' . $err_password . '</div>'; } ?>
                <input type="password" name="password" placeholder="Mot de passe" required>

                <button type="submit" name="connexion">Se connecter</button>
            </form>
            
            <div class="auth-links">
                <a href="inscription.php" class="btn-secondary">S'inscrire</a>
                <a href="forgot_password.php" class="btn-secondary">Mot de passe oublié</a>
            </div>
        </div>
    </div>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html>