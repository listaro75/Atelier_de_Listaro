<?php
/**
 * Script de vérification et configuration SSL
 * Atelier de Listaro
 */

echo "<h1>🔐 Configuration SSL - Atelier de Listaro</h1>";

// Vérifier si on est en HTTPS
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$protocol = $isHttps ? 'https' : 'http';
$currentUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

echo "<div style='background: " . ($isHttps ? '#d4edda' : '#f8d7da') . "; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>" . ($isHttps ? '✅' : '❌') . " Statut SSL actuel</h3>";
echo "<p><strong>URL actuelle:</strong> $currentUrl</p>";
echo "<p><strong>Protocole:</strong> " . strtoupper($protocol) . "</p>";
echo "<p><strong>Port:</strong> " . $_SERVER['SERVER_PORT'] . "</p>";
echo "</div>";

// Informations sur les certificats nécessaires
echo "<h2>📋 Fichiers SSL requis</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>Vous devez avoir ces fichiers :</h3>";
echo "<ol>";
echo "<li><strong>Clé privée :</strong> _.atelierdelistaro.fr_private_key.key ✅ (vous l'avez)</li>";
echo "<li><strong>Certificat SSL :</strong> _.atelierdelistaro.fr.crt ❓</li>";
echo "<li><strong>Certificat intermédiaire :</strong> _.atelierdelistaro.fr.ca-bundle ❓</li>";
echo "</ol>";
echo "</div>";

// Guide d'installation
echo "<h2>🛠️ Guide d'installation SSL</h2>";
echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>Étapes pour configurer SSL :</h3>";
echo "<ol>";
echo "<li><strong>Transférer les fichiers SSL sur le Raspberry Pi</strong>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# Créer le dossier SSL
sudo mkdir -p /etc/ssl/atelierdelistaro

# Copier la clé privée (depuis votre PC)
sudo nano /etc/ssl/atelierdelistaro/private.key
# Collez le contenu de _.atelierdelistaro.fr_private_key.key

# Copier le certificat SSL
sudo nano /etc/ssl/atelierdelistaro/certificate.crt
# Collez le contenu de _.atelierdelistaro.fr.crt

# Copier le bundle CA (si vous l'avez)
sudo nano /etc/ssl/atelierdelistaro/ca_bundle.crt
# Collez le contenu de _.atelierdelistaro.fr.ca-bundle
</pre>";

echo "<li><strong>Définir les permissions</strong>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
sudo chmod 600 /etc/ssl/atelierdelistaro/private.key
sudo chmod 644 /etc/ssl/atelierdelistaro/*.crt
sudo chown root:root /etc/ssl/atelierdelistaro/*
</pre>";

echo "<li><strong>Configurer Apache</strong>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# Activer les modules SSL
sudo a2enmod ssl
sudo a2enmod headers

# Activer les sites SSL
sudo a2ensite atelierdelistaro-ssl
sudo a2ensite atelierdelistaro-redirect

# Tester la configuration
sudo apache2ctl configtest

# Redémarrer Apache
sudo systemctl restart apache2
</pre>";

echo "<li><strong>Tester SSL</strong>";
echo "<ul>";
echo "<li>Accédez à <a href='https://atelierdelistaro.fr' target='_blank'>https://atelierdelistaro.fr</a></li>";
echo "<li>Vérifiez avec <a href='https://www.ssllabs.com/ssltest/analyze.html?d=atelierdelistaro.fr' target='_blank'>SSL Labs</a></li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Vérification de l'état du serveur
echo "<h2>🔍 Diagnostic du serveur</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px;'>";

// Vérifier Apache
if (function_exists('shell_exec')) {
    echo "<h3>État d'Apache :</h3>";
    $apache_status = shell_exec('systemctl is-active apache2 2>/dev/null') ?: 'Indéterminé';
    echo "<p><strong>Apache :</strong> " . trim($apache_status) . "</p>";
    
    // Vérifier les modules SSL
    $ssl_module = shell_exec('apache2ctl -M 2>/dev/null | grep ssl') ?: 'Module SSL non détecté';
    echo "<p><strong>Module SSL :</strong> " . (strpos($ssl_module, 'ssl_module') !== false ? '✅ Activé' : '❌ Non activé') . "</p>";
    
    // Vérifier les ports ouverts
    echo "<h3>Ports réseau :</h3>";
    $ports = shell_exec('netstat -tlnp 2>/dev/null | grep ":80\|:443" | head -5') ?: 'Impossible de vérifier les ports';
    echo "<pre style='background: white; padding: 10px; border-radius: 3px;'>$ports</pre>";
}

// Informations PHP
echo "<h3>Configuration PHP/Serveur :</h3>";
echo "<ul>";
echo "<li><strong>Version PHP :</strong> " . PHP_VERSION . "</li>";
echo "<li><strong>Serveur :</strong> " . $_SERVER['SERVER_SOFTWARE'] . "</li>";
echo "<li><strong>Document Root :</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "</ul>";
echo "</div>";

// Test de connectivité
echo "<h2>🌐 Test de connectivité</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<h3>URLs de test :</h3>";
echo "<ul>";
echo "<li><a href='http://88.124.91.246' target='_blank'>http://88.124.91.246</a> (IP direct)</li>";
echo "<li><a href='http://atelierdelistaro.fr' target='_blank'>http://atelierdelistaro.fr</a> (HTTP)</li>";
echo "<li><a href='https://atelierdelistaro.fr' target='_blank'>https://atelierdelistaro.fr</a> (HTTPS - après config SSL)</li>";
echo "</ul>";
echo "</div>";

// Section email avec SSL
echo "<h2>📧 Impact sur la configuration email</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
echo "<p>Une fois SSL configuré, vous pourrez :</p>";
echo "<ul>";
echo "<li>✅ Envoyer des emails sécurisés depuis votre domaine</li>";
echo "<li>✅ Améliorer la réputation de vos emails</li>";
echo "<li>✅ Utiliser des webhooks sécurisés pour Stripe</li>";
echo "<li>✅ Avoir une meilleure note de confiance</li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin_panel.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Panel Admin</a> ";
echo "<a href='check_newsletter_db.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>📧 Test Newsletter</a>";
echo "</p>";
?>
