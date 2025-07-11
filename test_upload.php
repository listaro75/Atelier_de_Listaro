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

<p><a href="admin_panel.php">← Retour au panel</a></p>
