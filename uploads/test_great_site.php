<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test de Connexion - Atelier de Listaro</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 800px; 
            margin: 50px auto; 
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #fd7e14; font-weight: bold; }
        .info { color: #17a2b8; font-weight: bold; }
        .step { 
            background: #f8f9fa; 
            padding: 15px; 
            margin: 10px 0; 
            border-left: 4px solid #007bff;
            border-radius: 0 5px 5px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            background: #e9ecef;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Test de Connexion - Atelier de Listaro</h1>
        <p><strong>Site :</strong> http://atelierdelistaro.great-site.net</p>
        <p><strong>Date :</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        <hr>

        <?php
        // Test 1: Vérification du fichier .env
        echo "<h2>1. 📁 Vérification du fichier .env</h2>";
        if (file_exists('.env')) {
            echo "<p class='success'>✅ Fichier .env trouvé</p>";
            $envExists = true;
        } else {
            echo "<p class='error'>❌ Fichier .env introuvable</p>";
            echo "<div class='step'>";
            echo "<h3>Action requise :</h3>";
            echo "<p>1. Créez un fichier nommé <strong>.env</strong> à la racine de votre site</p>";
            echo "<p>2. Utilisez le modèle <strong>.env.example</strong> comme base</p>";
            echo "<p>3. Remplissez avec vos vrais identifiants Great-Site.net</p>";
            echo "</div>";
            $envExists = false;
        }

        if ($envExists) {
            // Test 2: Chargement de la configuration
            echo "<h2>2. ⚙️ Chargement de la configuration</h2>";
            try {
                require_once '_config/env.php';
                echo "<p class='success'>✅ Configuration chargée avec succès</p>";
                
                $host = getenv('DB_HOST') ?: 'Non défini';
                $dbname = getenv('DB_NAME') ?: 'Non défini';
                $username = getenv('DB_USERNAME') ?: 'Non défini';
                $password = getenv('DB_PASSWORD') ? '[Défini]' : '[Non défini]';
                
                echo "<div class='step'>";
                echo "<h3>Paramètres détectés :</h3>";
                echo "<p><strong>Host :</strong> $host</p>";
                echo "<p><strong>Base :</strong> $dbname</p>";
                echo "<p><strong>Utilisateur :</strong> $username</p>";
                echo "<p><strong>Mot de passe :</strong> $password</p>";
                echo "</div>";
                
                $configLoaded = true;
                
            } catch (Exception $e) {
                echo "<p class='error'>❌ Erreur de configuration : " . $e->getMessage() . "</p>";
                echo "<div class='step'>";
                echo "<h3>Vérifiez :</h3>";
                echo "<p>1. Le fichier _config/env.php existe</p>";
                echo "<p>2. Le fichier .env est correctement formaté</p>";
                echo "</div>";
                $configLoaded = false;
            }

            if ($configLoaded) {
                // Test 3: Connexion à la base de données
                echo "<h2>3. 🗄️ Test de connexion MySQL</h2>";
                try {
                    $pdo = new PDO(
                        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
                        getenv('DB_USERNAME'),
                        getenv('DB_PASSWORD'),
                        [
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT => 10
                        ]
                    );
                    
                    echo "<p class='success'>✅ <strong>Connexion à la base de données réussie !</strong></p>";
                    
                    // Test des tables
                    $stmt = $pdo->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . getenv('DB_NAME') . "'");
                    $result = $stmt->fetch();
                    $tableCount = $result['count'];
                    
                    echo "<div class='step'>";
                    echo "<h3>État de la base de données :</h3>";
                    echo "<p><strong>Nombre de tables :</strong> $tableCount</p>";
                    
                    if ($tableCount == 0) {
                        echo "<p class='warning'>⚠️ <strong>La base est vide.</strong></p>";
                        echo "<h4>Action suivante :</h4>";
                        echo "<p>1. Connectez-vous à phpMyAdmin depuis votre panel Great-Site.net</p>";
                        echo "<p>2. Sélectionnez votre base de données</p>";
                        echo "<p>3. Importez le fichier <strong>atelier_listaro_db.sql</strong></p>";
                    } else {
                        echo "<p class='success'>✅ <strong>Des tables sont présentes dans la base.</strong></p>";
                        
                        // Vérifier les tables principales
                        $expectedTables = ['user', 'products', 'prestations', 'orders', 'product_images'];
                        echo "<h4>Vérification des tables principales :</h4>";
                        foreach ($expectedTables as $table) {
                            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
                            if ($stmt->rowCount() > 0) {
                                echo "<p class='success'>✅ Table '$table' présente</p>";
                            } else {
                                echo "<p class='error'>❌ Table '$table' manquante</p>";
                            }
                        }
                    }
                    echo "</div>";
                    
                    // Test final
                    echo "<h2>4. 🎯 Test final</h2>";
                    if ($tableCount > 0) {
                        echo "<div class='step'>";
                        echo "<p class='success'><strong>🎉 INSTALLATION RÉUSSIE !</strong></p>";
                        echo "<p>Votre site Atelier de Listaro est prêt à fonctionner.</p>";
                        echo "<h4>Prochaines étapes :</h4>";
                        echo "<p>1. <a href='index.php'>Testez votre site</a></p>";
                        echo "<p>2. Connectez-vous avec le compte admin par défaut :</p>";
                        echo "<p>&nbsp;&nbsp;&nbsp;<strong>Email :</strong> admin@atelier-listaro.com</p>";
                        echo "<p>&nbsp;&nbsp;&nbsp;<strong>Mot de passe :</strong> Admin123!</p>";
                        echo "<p>3. Supprimez ce fichier test_simple.php pour la sécurité</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='step'>";
                        echo "<p class='warning'><strong>Connexion OK, mais base vide</strong></p>";
                        echo "<p>Importez maintenant votre fichier SQL via phpMyAdmin</p>";
                        echo "</div>";
                    }
                    
                } catch (PDOException $e) {
                    echo "<p class='error'>❌ <strong>Erreur de connexion :</strong> " . $e->getMessage() . "</p>";
                    echo "<div class='step'>";
                    echo "<h3>Solutions possibles :</h3>";
                    echo "<p>1. <strong>Vérifiez vos identifiants</strong> dans le fichier .env</p>";
                    echo "<p>2. <strong>Connectez-vous à votre panel Great-Site.net</strong> et vérifiez :</p>";
                    echo "<p>&nbsp;&nbsp;&nbsp;- La base de données existe</p>";
                    echo "<p>&nbsp;&nbsp;&nbsp;- Les identifiants sont corrects</p>";
                    echo "<p>&nbsp;&nbsp;&nbsp;- Le serveur MySQL est actif</p>";
                    echo "<p>3. <strong>Attendez 10-15 minutes</strong> après création de la base</p>";
                    echo "<p>4. <strong>Testez phpMyAdmin</strong> depuis votre panel</p>";
                    echo "<p>5. <strong>Contactez le support Great-Site.net</strong> si le problème persiste</p>";
                    echo "</div>";
                }
            }
        }
        ?>

        <div class="footer">
            <h3>🔗 Liens utiles</h3>
            <p><a href="http://atelierdelistaro.great-site.net">Retour au site</a> |
            <a href="diagnostic_connexion.php">Diagnostic avancé</a> |
            <a href="install.php">Installation automatique</a></p>
            
            <hr style="margin: 20px 0;">
            <p><small>⚠️ <strong>Important :</strong> Supprimez ce fichier après validation pour la sécurité</small></p>
        </div>
    </div>
</body>
</html>
