<?php
function loadEnv() {
    $envFile = __DIR__ . '/../.env';
    
    if (!file_exists($envFile)) {
        throw new Exception('.env file not found at: ' . $envFile);
    }

    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        throw new Exception('Could not read .env file');
    }
    
    foreach ($lines as $lineNumber => $line) {
        $line = trim($line);
        
        // Ignorer les lignes vides et les commentaires
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Vérifier qu'il y a bien un signe =
        if (strpos($line, '=') === false) {
            continue;
        }
        
        // Séparer clé et valeur
        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }
        
        $key = trim($parts[0]);
        $value = trim($parts[1]);
        
        // Supprimer les guillemets si présents
        if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
            (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
            $value = substr($value, 1, -1);
        }
        
        if (!empty($key)) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value; // Ajout pour plus de compatibilité
        }
    }
}

// Charger les variables d'environnement
try {
    loadEnv();
} catch (Exception $e) {
    // En cas d'erreur, on peut continuer mais afficher l'erreur
    error_log('Erreur chargement .env: ' . $e->getMessage());
}
?>
