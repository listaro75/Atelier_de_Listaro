<?php
echo "<h1>🔧 Test Configuration Upload</h1>";

echo "<h2>Configuration PHP :</h2>";
echo "<ul>";
echo "<li>max_file_uploads: " . ini_get('max_file_uploads') . "</li>";
echo "<li>upload_max_filesize: " . ini_get('upload_max_filesize') . "</li>";
echo "<li>post_max_size: " . ini_get('post_max_size') . "</li>";
echo "<li>memory_limit: " . ini_get('memory_limit') . "</li>";
echo "</ul>";

echo "<h2>Permissions des dossiers :</h2>";
$dirs = ['uploads', 'uploads/products'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $perms = substr(sprintf('%o', fileperms($dir)), -4);
        echo "<p>✅ $dir : $perms</p>";
    } else {
        echo "<p>❌ $dir : N'existe pas</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>Test d'upload :</h2>";
    if (isset($_FILES['test_image'])) {
        $file = $_FILES['test_image'];
        echo "<pre>";
        print_r($file);
        echo "</pre>";
        
        if ($file['error'] === 0) {
            $upload_dir = 'uploads/products/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $filename = 'test_' . time() . '_' . $file['name'];
            $destination = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $destination)) {
                echo "<p style='color: green;'>✅ Upload réussi : $destination</p>";
                echo "<img src='$destination' style='max-width: 200px;'>";
            } else {
                echo "<p style='color: red;'>❌ Échec de l'upload</p>";
            }
        }
    }
}
?>

<form method="POST" enctype="multipart/form-data">
    <h2>Test d'upload d'image :</h2>
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Tester l'upload</button>
</form>

<div style="margin: 20px 0; padding: 20px; background: #f8f9fa; border-radius: 8px;">
    <h2>🔧 Actions Serveur :</h2>
    <button onclick="restartServer()" style="
        background: linear-gradient(45deg, #e74c3c, #c0392b);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        margin-right: 10px;
    ">
        🔄 Redémarrer le serveur
    </button>
    
    <button onclick="clearCache()" style="
        background: linear-gradient(45deg, #f39c12, #e67e22);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
    ">
        🧹 Vider le cache
    </button>
    
    <div id="action-result" style="margin-top: 15px; padding: 10px; border-radius: 5px; display: none;"></div>
</div>

<script>
function restartServer() {
    if (!confirm('⚠️ Redémarrer le serveur ? Cela va interrompre temporairement le service.')) {
        return;
    }
    
    showResult('Redémarrage en cours...', 'info');
    
    fetch('ajax/server_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=restart'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showResult('✅ ' + data.message + (data.details ? ' - ' + data.details : ''), 'success');
        } else {
            showResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showResult('❌ Erreur: ' + error.message, 'error');
    });
}

function clearCache() {
    showResult('Nettoyage du cache...', 'info');
    
    fetch('ajax/server_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=clear_cache'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showResult('✅ ' + data.message + (data.details ? ' - ' + data.details : ''), 'success');
        } else {
            showResult('❌ Erreur: ' + data.message, 'error');
        }
    })
    .catch(error => {
        showResult('❌ Erreur: ' + error.message, 'error');
    });
}

function showResult(message, type) {
    const resultDiv = document.getElementById('action-result');
    resultDiv.style.display = 'block';
    resultDiv.innerHTML = message;
    
    // Couleurs selon le type
    if (type === 'success') {
        resultDiv.style.background = '#d4edda';
        resultDiv.style.color = '#155724';
        resultDiv.style.border = '1px solid #c3e6cb';
    } else if (type === 'error') {
        resultDiv.style.background = '#f8d7da';
        resultDiv.style.color = '#721c24';
        resultDiv.style.border = '1px solid #f5c6cb';
    } else {
        resultDiv.style.background = '#cce5ff';
        resultDiv.style.color = '#004085';
        resultDiv.style.border = '1px solid #99ccff';
    }
}
</script>

<p><a href="admin_panel.php">← Retour au panel</a></p>
