#!/bin/bash
# =====================================================
#     PRÉPARATION DES FICHIERS - RASPBERRY PI
#     Script pour préparer les fichiers avant déploiement
# =====================================================

clear
echo "📦 ========================================"
echo "   PRÉPARATION POUR DÉPLOIEMENT"
echo "   Raspberry Pi - Atelier de Listaro"
echo "========================================"
echo

# Configuration
UPLOAD_DIR="/tmp/atelier_listaro_update"
SOURCE_FILES=(
    "admin_sections/products.php"
    "test_multi_images.html"
    "admin_images.php"
    "file_editor.php"
)

# Créer le dossier de préparation
echo "📁 Création du dossier de préparation..."
rm -rf "$UPLOAD_DIR"
mkdir -p "$UPLOAD_DIR/admin_sections"
echo "✅ Dossier créé: $UPLOAD_DIR"
echo

# Fonction pour créer les fichiers s'ils n'existent pas
create_missing_files() {
    echo "🔧 Vérification et création des fichiers manquants..."
    
    # Créer test_multi_images.html s'il n'existe pas
    if [ ! -f "$UPLOAD_DIR/test_multi_images.html" ]; then
        echo "📄 Création de test_multi_images.html..."
        cat > "$UPLOAD_DIR/test_multi_images.html" << 'EOF'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Sélection Multiple d'Images</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        h1 { color: #333; text-align: center; margin-bottom: 30px; }
        .demo-section { margin: 30px 0; padding: 20px; border: 2px dashed #ddd; border-radius: 8px; background: #fafafa; }
        .file-input-custom { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 20px; border-radius: 8px; border: none; cursor: pointer; width: 100%; text-align: center; font-weight: bold; margin-bottom: 8px; transition: all 0.3s ease; }
        .file-input-custom:hover { transform: translateY(-2px); box-shadow: 0 4px 8px rgba(0,0,0,0.2); }
        .image-help { background: #e7f3ff; border: 1px solid #0084ff; border-radius: 6px; padding: 12px; margin-top: 8px; font-size: 13px; color: #0066cc; line-height: 1.4; }
        .image-preview { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; padding: 10px; border: 2px dashed #ddd; border-radius: 8px; min-height: 60px; background: #fafafa; }
        .image-item { position: relative; width: 100px; height: 100px; border: 2px solid #ddd; border-radius: 8px; overflow: hidden; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .image-item img { width: 100%; height: 100%; object-fit: cover; }
        .main-badge { position: absolute; top: 5px; left: 5px; background: #28a745; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: bold; }
        .delete-btn { position: absolute; top: -5px; right: -5px; background: #dc3545; color: white; border: none; border-radius: 50%; width: 20px; height: 20px; font-size: 12px; cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .success-message { background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 6px; margin: 20px 0; }
        .info-box { background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 6px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🖼️ Test - Sélection Multiple d'Images</h1>
        <div class="info-box">
            <strong>📋 Instructions :</strong><br>
            1. Cliquez sur le bouton ci-dessous<br>
            2. Sélectionnez plusieurs images avec Ctrl+clic<br>
            3. Vérifiez la prévisualisation<br>
            4. Testez la suppression individuelle
        </div>
        
        <div class="demo-section">
            <h3>Test de Sélection Multiple</h3>
            <button type="button" class="file-input-custom" onclick="document.getElementById('test-images').click()">
                <i class="fas fa-images"></i> Sélectionner plusieurs images (Ctrl+clic)
            </button>
            <input type="file" id="test-images" multiple accept="image/*" onchange="previewImages(this)" style="display: none;">
            <div class="image-help">
                <strong>📸 Sélection multiple :</strong><br>
                Maintenez Ctrl (Windows) ou Cmd (Mac) et cliquez sur plusieurs images
            </div>
            <div id="image-preview" class="image-preview"></div>
        </div>
        
        <div id="result-info"></div>
        <div style="text-align: center; margin-top: 30px;">
            <a href="admin_panel.php">← Retour au panel d'administration</a>
        </div>
    </div>

    <script>
        function previewImages(input) {
            const preview = document.getElementById('image-preview');
            const resultInfo = document.getElementById('result-info');
            preview.innerHTML = '';
            resultInfo.innerHTML = '';
            
            if (input.files && input.files.length > 0) {
                if (input.files.length > 5) {
                    alert('Maximum 5 images par produit.');
                    input.value = '';
                    return;
                }
                
                const fileCount = input.files.length;
                resultInfo.innerHTML = '<div class="success-message"><strong>✅ Test réussi !</strong><br>' + fileCount + ' image(s) sélectionnée(s).</div>';
                
                Array.from(input.files).forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const imageDiv = document.createElement('div');
                            imageDiv.className = 'image-item';
                            imageDiv.innerHTML = '<img src="' + e.target.result + '" alt="Preview"><div class="main-badge">Principal</div>';
                            preview.appendChild(imageDiv);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            }
        }
    </script>
</body>
</html>
EOF
        echo "   ✅ test_multi_images.html créé"
    fi
    
    echo "✅ Vérification terminée"
    echo
}

# Afficher les instructions
echo "📋 INSTRUCTIONS DE DÉPLOIEMENT:"
echo "----------------------------------------"
echo "1. Transférez les fichiers vers le Raspberry Pi:"
echo "   scp -r /chemin/vers/fichiers/ admin@192.168.1.95:/tmp/atelier_listaro_update/"
echo
echo "2. Connectez-vous au Raspberry Pi:"
echo "   ssh admin@192.168.1.95"
echo
echo "3. Exécutez le script de déploiement:"
echo "   sudo bash /tmp/atelier_listaro_update/deploy_raspi.sh"
echo "----------------------------------------"
echo

# Créer les fichiers manquants
create_missing_files

# Afficher le contenu préparé
echo "📦 FICHIERS PRÊTS POUR LE DÉPLOIEMENT:"
echo "----------------------------------------"
if [ -f "$UPLOAD_DIR/admin_sections/products.php" ]; then
    echo "✅ admin_sections/products.php"
else
    echo "❌ admin_sections/products.php - CRITIQUE"
fi

if [ -f "$UPLOAD_DIR/test_multi_images.html" ]; then
    echo "✅ test_multi_images.html"
else
    echo "❌ test_multi_images.html"
fi

if [ -f "$UPLOAD_DIR/admin_images.php" ]; then
    echo "✅ admin_images.php"
else
    echo "⚠️  admin_images.php - Optionnel"
fi

echo "----------------------------------------"
echo

# Créer un script de transfert automatique
echo "🚀 Création du script de transfert automatique..."
cat > transfer_to_raspi.sh << 'EOF'
#!/bin/bash
echo "📡 Transfert vers Raspberry Pi..."
scp -r /tmp/atelier_listaro_update/ admin@192.168.1.95:/tmp/
scp deploy_raspi.sh admin@192.168.1.95:/tmp/atelier_listaro_update/
echo "✅ Transfert terminé!"
echo
echo "🔗 Connectez-vous maintenant au Raspberry Pi:"
echo "ssh admin@192.168.1.95"
echo
echo "🚀 Puis exécutez le déploiement:"
echo "sudo bash /tmp/atelier_listaro_update/deploy_raspi.sh"
EOF

chmod +x transfer_to_raspi.sh
echo "✅ Script de transfert créé: transfer_to_raspi.sh"
echo

echo "🎯 PRÊT POUR LE DÉPLOIEMENT!"
echo "Exécutez: ./transfer_to_raspi.sh"
echo
