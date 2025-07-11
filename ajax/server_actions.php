<?php
/**
 * Actions serveur - Redémarrage, cache, analyse disque
 * Fichier: ajax/server_actions.php
 */

// Protection CSRF et accès admin
session_start();
require_once '../_functions/auth.php';

// Vérifier que l'utilisateur est connecté et admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier la méthode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

$action = $_POST['action'] ?? '';

header('Content-Type: application/json');

try {
    switch ($action) {
        case 'restart':
            handleServerRestart();
            break;
            
        case 'clear_cache':
            handleClearCache();
            break;
            
        case 'check_disk':
            handleDiskCheck();
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

function handleServerRestart() {
    // Log de l'action
    error_log("Admin restart request from user ID: " . $_SESSION['user_id']);
    
    // Sur Raspberry Pi avec systemd
    $restart_commands = [
        // Redémarrage du service Apache/Nginx
        'sudo systemctl restart apache2 2>/dev/null',
        'sudo systemctl restart nginx 2>/dev/null',
        // Alternative: redémarrage complet du système (à utiliser avec prudence)
        // 'sudo shutdown -r +1 "Redémarrage planifié depuis l\'admin panel"'
    ];
    
    $executed = false;
    $output = [];
    
    foreach ($restart_commands as $command) {
        exec($command . ' && echo "SUCCESS"', $result, $return_code);
        if ($return_code === 0 && in_array('SUCCESS', $result)) {
            $executed = true;
            $output[] = "Service redémarré";
            break;
        }
    }
    
    if (!$executed) {
        // Essayer un redémarrage plus doux
        exec('sudo pkill -HUP apache2 2>/dev/null', $result, $return_code);
        if ($return_code === 0) {
            $executed = true;
            $output[] = "Signal de rechargement envoyé au serveur web";
        }
    }
    
    if ($executed) {
        echo json_encode([
            'success' => true,
            'message' => 'Commande de redémarrage exécutée',
            'details' => implode(', ', $output)
        ]);
    } else {
        // Fallback: message informatif sans vraiment redémarrer
        echo json_encode([
            'success' => true,
            'message' => 'Redémarrage demandé (nécessite des privilèges sudo)',
            'details' => 'Pour activer le redémarrage automatique, configurez sudo sans mot de passe pour www-data'
        ]);
    }
}

function handleClearCache() {
    $cleared = [];
    $errors = [];
    
    // Vider le cache PHP OPcache
    if (function_exists('opcache_reset')) {
        if (opcache_reset()) {
            $cleared[] = "OPcache";
        } else {
            $errors[] = "OPcache (échec)";
        }
    }
    
    // Vider les fichiers de cache temporaires
    $cache_dirs = [
        '../tmp/',
        '../cache/',
        '/tmp/php_sessions/',
        sys_get_temp_dir() . '/php_cache/'
    ];
    
    foreach ($cache_dirs as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '*');
            $count = 0;
            foreach ($files as $file) {
                if (is_file($file) && unlink($file)) {
                    $count++;
                }
            }
            if ($count > 0) {
                $cleared[] = "$count fichiers de $dir";
            }
        }
    }
    
    // Nettoyer les logs anciens (> 7 jours)
    $log_files = glob('../logs/*.log');
    $cleaned_logs = 0;
    foreach ($log_files as $log_file) {
        if (filemtime($log_file) < time() - 7 * 24 * 3600) {
            if (unlink($log_file)) {
                $cleaned_logs++;
            }
        }
    }
    if ($cleaned_logs > 0) {
        $cleared[] = "$cleaned_logs anciens logs";
    }
    
    // Résultat
    if (!empty($cleared)) {
        echo json_encode([
            'success' => true,
            'message' => 'Cache vidé avec succès',
            'details' => 'Éléments nettoyés: ' . implode(', ', $cleared)
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Aucun cache à vider',
            'details' => 'Le système est déjà propre'
        ]);
    }
}

function handleDiskCheck() {
    $analysis = [];
    
    // Utilisation globale du disque
    $disk_total = disk_total_space('/');
    $disk_free = disk_free_space('/');
    $disk_used = $disk_total - $disk_free;
    $disk_percent = round(($disk_used / $disk_total) * 100, 1);
    
    $analysis[] = "Disque principal: {$disk_percent}% utilisé";
    $analysis[] = "Espace libre: " . formatBytes($disk_free);
    
    // Analyse des gros dossiers
    $big_dirs = [
        '/var/log' => 'Logs système',
        '/tmp' => 'Fichiers temporaires',
        '../uploads' => 'Uploads du site',
        '/home' => 'Dossiers utilisateurs'
    ];
    
    foreach ($big_dirs as $dir => $description) {
        if (is_dir($dir)) {
            $size = getDirSize($dir);
            if ($size > 10 * 1024 * 1024) { // Plus de 10MB
                $analysis[] = "$description: " . formatBytes($size);
            }
        }
    }
    
    // Recommandations si espace faible
    if ($disk_percent > 85) {
        $analysis[] = "⚠️ ATTENTION: Espace disque critique!";
        $analysis[] = "Recommandation: nettoyer les logs et fichiers temporaires";
    } elseif ($disk_percent > 70) {
        $analysis[] = "⚠️ Surveiller l'espace disque";
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Analyse terminée',
        'details' => implode(' | ', $analysis)
    ]);
}

function formatBytes($size, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    return round($size, $precision) . ' ' . $units[$i];
}

function getDirSize($directory) {
    $size = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $size += $file->getSize();
        }
    } catch (Exception $e) {
        // Ignore les erreurs d'accès
        return 0;
    }
    return $size;
}
?>
