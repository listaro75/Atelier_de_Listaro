<?php
// Test simple des statistiques Raspberry Pi
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '_functions/raspi_stats.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Statistiques Raspberry Pi</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .stat { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { background: #ffcccc; color: #cc0000; }
        .success { background: #ccffcc; color: #006600; }
    </style>
</head>
<body>
    <h1>Test des Statistiques Raspberry Pi</h1>";

try {
    $stats = new RaspberryPiStats();
    
    echo "<div class='stat success'>✅ Classe RaspberryPiStats chargée avec succès</div>";
    
    // Test température CPU
    echo "<div class='stat'><strong>Température CPU:</strong> " . $stats->getCpuTemperature() . "</div>";
    
    // Test utilisation mémoire
    $memory = $stats->getMemoryUsage();
    echo "<div class='stat'><strong>Mémoire:</strong> " . $memory['used_percent'] . "% utilisée (" . $memory['used'] . " / " . $memory['total'] . ")</div>";
    
    // Test utilisation disque
    $disk = $stats->getDiskUsage();
    echo "<div class='stat'><strong>Disque:</strong> " . $disk['used_percent'] . "% utilisé (" . $disk['used'] . " / " . $disk['total'] . ")</div>";
    
    // Test uptime
    echo "<div class='stat'><strong>Uptime:</strong> " . $stats->getUptime() . "</div>";
    
    // Test infos système
    $systemInfo = $stats->getSystemInfo();
    echo "<div class='stat'><strong>Système:</strong> " . $systemInfo['os'] . " - " . $systemInfo['kernel'] . "</div>";
    echo "<div class='stat'><strong>Architecture:</strong> " . $systemInfo['architecture'] . "</div>";
    echo "<div class='stat'><strong>Hostname:</strong> " . $systemInfo['hostname'] . "</div>";
    
    // Test infos réseau
    $networkInfo = $stats->getNetworkInfo();
    echo "<div class='stat'><strong>Adresse IP:</strong> " . $networkInfo['ip_address'] . "</div>";
    echo "<div class='stat'><strong>Interface réseau:</strong> " . $networkInfo['interface'] . "</div>";
    
} catch (Exception $e) {
    echo "<div class='stat error'>❌ Erreur: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<p><a href='admin_panel.php'>← Retour au tableau de bord</a></p>";
echo "</body></html>";
?>
