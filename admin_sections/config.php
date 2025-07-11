<?php
// Configuration des chemins pour les sections admin
define('ADMIN_ROOT', __DIR__ . '/..');
define('UPLOADS_DIR', ADMIN_ROOT . '/uploads');
define('PRODUCTS_IMAGES_DIR', UPLOADS_DIR . '/products');

// Fonction pour obtenir le chemin relatif d'une image
function getImagePath($imagePath) {
    // Si le chemin commence déjà par uploads/, on le retourne tel quel
    if (strpos($imagePath, 'uploads/') === 0) {
        return $imagePath;
    }
    // Sinon, on l'ajoute
    return $imagePath;
}

// Fonction pour obtenir le chemin absolu d'une image
function getImageAbsolutePath($imagePath) {
    return ADMIN_ROOT . '/' . $imagePath;
}

// Créer les dossiers si nécessaire
if (!is_dir(UPLOADS_DIR)) {
    mkdir(UPLOADS_DIR, 0755, true);
}

if (!is_dir(PRODUCTS_IMAGES_DIR)) {
    mkdir(PRODUCTS_IMAGES_DIR, 0755, true);
}
?>
