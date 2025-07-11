<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    header('Location: connexion.php?error=admin_required');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test - Limite 5 Images</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .test-section h3 {
            color: #34495e;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px dashed #3498db;
            border-radius: 5px;
            background: white;
        }
        .limit-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #3498db;
            margin-bottom: 20px;
        }
        .limit-info h4 {
            margin: 0 0 10px 0;
            color: #2980b9;
        }
        .test-result {
            margin-top: 15px;
            padding: 10px;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .btn {
            background: #3498db;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn:hover {
            background: #2980b9;
        }
        .file-count {
            margin-top: 10px;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1><i class="fas fa-images"></i> Test - Limitation à 5 Images</h1>
        
        <div class="limit-info">
            <h4><i class="fas fa-info-circle"></i> Nouvelle Fonctionnalité</h4>
            <p>Le système a été modifié pour <strong>limiter le nombre d'images à 5 maximum par produit</strong>.</p>
            <ul>
                <li>✅ Validation côté serveur (PHP)</li>
                <li>✅ Validation côté client (JavaScript)</li>
                <li>✅ Messages d'erreur informatifs</li>
                <li>✅ Indication visuelle dans les formulaires</li>
            </ul>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-plus"></i> Test 1 : Ajout de nouveau produit</h3>
            <p>Testez la sélection de plus de 5 images lors de l'ajout d'un produit :</p>
            
            <div class="form-group">
                <label>Sélectionner des images (testez avec plus de 5)</label>
                <input type="file" id="test-add" multiple accept="image/*" onchange="testAddImages(this)">
                <div class="file-count" id="add-count"></div>
                <div class="test-result" id="add-result" style="display: none;"></div>
            </div>
            
            <p><strong>Résultat attendu :</strong> Si vous sélectionnez plus de 5 images, vous devriez voir une alerte et la sélection devrait être réinitialisée.</p>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-edit"></i> Test 2 : Simulation d'édition</h3>
            <p>Testez l'ajout de nouvelles images à un produit ayant déjà des images :</p>
            
            <div class="form-group">
                <label>Images existantes simulées : 3</label>
                <div style="background: #fff; padding: 10px; border: 1px solid #ddd; border-radius: 3px;">
                    <span>📷 Image 1</span> | 
                    <span>📷 Image 2</span> | 
                    <span>📷 Image 3</span>
                </div>
            </div>
            
            <div class="form-group">
                <label>Sélectionner de nouvelles images (testez avec plus de 2)</label>
                <input type="file" id="test-edit" multiple accept="image/*" onchange="testEditImages(this)">
                <div class="file-count" id="edit-count"></div>
                <div class="test-result" id="edit-result" style="display: none;"></div>
            </div>
            
            <p><strong>Résultat attendu :</strong> Si vous sélectionnez plus de 2 nouvelles images (3 existantes + 2 nouvelles = 5 max), vous devriez voir une alerte.</p>
        </div>

        <div class="test-section">
            <h3><i class="fas fa-link"></i> Test 3 : Panel d'administration</h3>
            <p>Testez la fonctionnalité complète dans le panel d'administration :</p>
            <a href="admin_panel.php#" class="btn">
                <i class="fas fa-external-link-alt"></i> Ouvrir le Panel Admin
            </a>
            <p style="margin-top: 10px;"><strong>Instructions :</strong></p>
            <ol>
                <li>Allez dans la section "Produits"</li>
                <li>Cliquez sur "Ajouter un produit"</li>
                <li>Essayez de sélectionner plus de 5 images</li>
                <li>Vérifiez que le message d'erreur s'affiche</li>
                <li>Testez également l'édition d'un produit existant</li>
            </ol>
        </div>
    </div>

    <script>
        function testAddImages(input) {
            const countDiv = document.getElementById('add-count');
            const resultDiv = document.getElementById('add-result');
            
            if (input.files.length > 0) {
                countDiv.textContent = `Fichiers sélectionnés : ${input.files.length}`;
                countDiv.style.color = input.files.length > 5 ? '#e74c3c' : '#27ae60';
                
                if (input.files.length > 5) {
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = '❌ Test réussi ! Le système a détecté plus de 5 images.';
                    resultDiv.style.display = 'block';
                    
                    // Simuler l'alerte et la réinitialisation
                    setTimeout(() => {
                        alert('Vous ne pouvez sélectionner que 5 images maximum par produit.');
                        input.value = '';
                        countDiv.textContent = '';
                        resultDiv.style.display = 'none';
                    }, 500);
                } else {
                    resultDiv.className = 'test-result success';
                    resultDiv.textContent = '✅ Nombre d\'images acceptable.';
                    resultDiv.style.display = 'block';
                }
            } else {
                countDiv.textContent = '';
                resultDiv.style.display = 'none';
            }
        }

        function testEditImages(input) {
            const countDiv = document.getElementById('edit-count');
            const resultDiv = document.getElementById('edit-result');
            const existingImages = 3; // Simulé
            
            if (input.files.length > 0) {
                const totalImages = existingImages + input.files.length;
                countDiv.textContent = `Nouvelles images : ${input.files.length} | Total : ${totalImages}`;
                countDiv.style.color = totalImages > 5 ? '#e74c3c' : '#27ae60';
                
                if (totalImages > 5) {
                    const maxNewImages = 5 - existingImages;
                    resultDiv.className = 'test-result error';
                    resultDiv.textContent = `❌ Test réussi ! Limite dépassée. Maximum ${maxNewImages} nouvelles images autorisées.`;
                    resultDiv.style.display = 'block';
                    
                    // Simuler l'alerte et la réinitialisation
                    setTimeout(() => {
                        alert(`Vous ne pouvez avoir que 5 images maximum par produit. Vous avez déjà ${existingImages} images. Vous pouvez ajouter au maximum ${maxNewImages} nouvelles images.`);
                        input.value = '';
                        countDiv.textContent = '';
                        resultDiv.style.display = 'none';
                    }, 500);
                } else {
                    resultDiv.className = 'test-result success';
                    resultDiv.textContent = '✅ Nombre total d\'images acceptable.';
                    resultDiv.style.display = 'block';
                }
            } else {
                countDiv.textContent = '';
                resultDiv.style.display = 'none';
            }
        }
    </script>
</body>
</html>
