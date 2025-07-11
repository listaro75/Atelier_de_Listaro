<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    header('Location: connexion.php?error=admin_required');
    exit();
}

$file_path = __DIR__ . '/admin_sections/products.php';
$message = '';
$message_type = '';

// Traitement de la sauvegarde
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_file'])) {
    $new_content = $_POST['file_content'];
    
    // Faire une sauvegarde
    $backup_path = $file_path . '.backup.' . date('Y-m-d-H-i-s');
    copy($file_path, $backup_path);
    
    // Sauvegarder le nouveau contenu
    if (file_put_contents($file_path, $new_content)) {
        $message = "Fichier sauvegardé avec succès ! Sauvegarde créée : " . basename($backup_path);
        $message_type = 'success';
    } else {
        $message = "Erreur lors de la sauvegarde du fichier.";
        $message_type = 'error';
    }
}

// Lire le contenu actuel du fichier
$current_content = file_get_contents($file_path);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur de Fichiers - Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: -20px -20px 20px -20px;
        }
        .alert {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        textarea {
            width: 100%;
            height: 500px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-danger {
            background: #dc3545;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔧 Éditeur de Fichiers</h1>
            <p>Modification du fichier: admin_sections/products.php</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?= $message_type ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <div class="warning">
            <strong>⚠️ Attention !</strong><br>
            Cette page permet de modifier directement le code PHP. Une erreur de syntaxe peut casser votre site.<br>
            Une sauvegarde automatique sera créée avant chaque modification.
        </div>

        <form method="post">
            <h3>Contenu du fichier :</h3>
            <textarea name="file_content"><?= htmlspecialchars($current_content) ?></textarea>
            
            <div style="margin-top: 20px;">
                <button type="submit" name="save_file" class="btn" onclick="return confirm('Êtes-vous sûr de vouloir sauvegarder ces modifications ?')">
                    💾 Sauvegarder
                </button>
                <a href="admin_panel.php" class="btn btn-danger">
                    🚫 Annuler
                </a>
            </div>
        </form>

        <div style="margin-top: 30px;">
            <h3>📋 Instructions pour la sélection multiple d'images :</h3>
            <p>Pour activer la sélection multiple d'images, vous devez modifier la section du formulaire d'ajout de produit dans le fichier ci-dessus.</p>
            <p><strong>Cherchez cette ligne :</strong></p>
            <code>&lt;input type="file" id="product-images" name="images[]" multiple accept="image/*"&gt;</code>
            <p><strong>Et remplacez-la par le code amélioré avec bouton personnalisé et prévisualisation.</strong></p>
            
            <p><a href="admin_panel.php">← Retour au panel d'administration</a></p>
        </div>
    </div>
</body>
</html>
