<?php
/**
 * Script de réparation pour les dossiers d'upload d'images
 */

include '_functions/image_utils.php';

echo "=== RÉPARATION DOSSIERS IMAGES ===\n\n";

// Créer le dossier uploads/products s'il n'existe pas
$uploadDir = __DIR__ . '/uploads/products';

echo "Vérification du dossier : $uploadDir\n";

if (!is_dir($uploadDir)) {
    echo "Le dossier n'existe pas. Création...\n";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✅ Dossier créé avec succès\n";
    } else {
        echo "❌ Erreur lors de la création du dossier\n";
    }
} else {
    echo "✅ Dossier existe déjà\n";
}

// Vérifier les permissions
if (is_writable($uploadDir)) {
    echo "✅ Permissions d'écriture OK\n";
} else {
    echo "⚠️ Tentative de correction des permissions...\n";
    if (chmod($uploadDir, 0755)) {
        echo "✅ Permissions corrigées\n";
    } else {
        echo "❌ Impossible de corriger les permissions\n";
    }
}

// Créer un fichier .htaccess pour sécuriser le dossier
$htaccessFile = $uploadDir . '/.htaccess';
$htaccessContent = "# Sécurité pour les uploads d'images
Options -Indexes
<Files *.php>
    Deny from all
</Files>
<Files *.php*>
    Deny from all
</Files>
<Files *.phtml>
    Deny from all
</Files>";

if (!file_exists($htaccessFile)) {
    if (file_put_contents($htaccessFile, $htaccessContent)) {
        echo "✅ Fichier .htaccess de sécurité créé\n";
    } else {
        echo "⚠️ Impossible de créer le fichier .htaccess\n";
    }
} else {
    echo "✅ Fichier .htaccess existe déjà\n";
}

// Créer une image par défaut pour les tests
$defaultImageDir = __DIR__ . '/assets/images';
if (!is_dir($defaultImageDir)) {
    mkdir($defaultImageDir, 0755, true);
}

// Créer une image SVG par défaut
$defaultImagePath = $defaultImageDir . '/no-image.svg';
$svgContent = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="300" height="200" viewBox="0 0 300 200" xmlns="http://www.w3.org/2000/svg">
    <rect width="100%" height="100%" fill="#f8f9fa"/>
    <rect x="10" y="10" width="280" height="180" fill="none" stroke="#dee2e6" stroke-width="2" stroke-dasharray="5,5"/>
    <circle cx="150" cy="80" r="25" fill="#dee2e6"/>
    <path d="M130,70 L170,70 L165,75 L160,65 L150,75 L140,65 L135,75 Z" fill="#adb5bd"/>
    <text x="150" y="130" font-family="Arial, sans-serif" font-size="14" fill="#6c757d" text-anchor="middle">Aucune image</text>
    <text x="150" y="150" font-family="Arial, sans-serif" font-size="12" fill="#adb5bd" text-anchor="middle">disponible</text>
</svg>';

if (!file_exists($defaultImagePath)) {
    if (file_put_contents($defaultImagePath, $svgContent)) {
        echo "✅ Image par défaut créée : assets/images/no-image.svg\n";
    } else {
        echo "⚠️ Impossible de créer l'image par défaut\n";
    }
}

echo "\n=== RÉPARATION TERMINÉE ===\n";
echo "Vous pouvez maintenant tester l'upload d'images dans l'admin panel.\n";
?>
