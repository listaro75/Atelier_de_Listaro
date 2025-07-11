<?php
/**
 * Vérification et configuration du hostname
 * Atelier de Listaro - Raspbe  rry Pi
 */

echo "<h1>🖥️ Configuration Hostname - Atelier de Listaro</h1>";

// Obtenir les informations système
$hostname = gethostname();
$server_name = $_SERVER['SERVER_NAME'] ?? 'Non défini';
$http_host = $_SERVER['HTTP_HOST'] ?? 'Non défini';

echo "<h2>📋 Informations actuelles du système</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e9ecef;'>";
echo "<th style='padding: 10px; text-align: left;'>Paramètre</th>";
echo "<th style='padding: 10px; text-align: left;'>Valeur</th>";
echo "</tr>";
echo "<tr><td style='padding: 8px;'><strong>Hostname système</strong></td><td style='padding: 8px;'>$hostname</td></tr>";
echo "<tr><td style='padding: 8px;'><strong>SERVER_NAME</strong></td><td style='padding: 8px;'>$server_name</td></tr>";
echo "<tr><td style='padding: 8px;'><strong>HTTP_HOST</strong></td><td style='padding: 8px;'>$http_host</td></tr>";
echo "<tr><td style='padding: 8px;'><strong>Adresse IP</strong></td><td style='padding: 8px;'>" . $_SERVER['SERVER_ADDR'] . "</td></tr>";
echo "<tr><td style='padding: 8px;'><strong>Port</strong></td><td style='padding: 8px;'>" . $_SERVER['SERVER_PORT'] . "</td></tr>";
echo "</table>";
echo "</div>";

// Explication pourquoi "mail"
echo "<h2>🤔 Pourquoi votre machine s'appelle 'mail' ?</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p>Plusieurs raisons possibles :</p>";
echo "<ul>";
echo "<li><strong>Configuration email :</strong> Le hostname a été défini pour les services de messagerie</li>";
echo "<li><strong>Postfix/Sendmail :</strong> Configuration automatique du serveur mail</li>";
echo "<li><strong>Installation précédente :</strong> Un script ou logiciel a modifié le hostname</li>";
echo "<li><strong>Configuration par défaut :</strong> Image Raspberry Pi préconfigurée</li>";
echo "</ul>";
echo "</div>";

// Guide de modification
echo "<h2>🔧 Comment changer le hostname</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Option 1 : Hostname simple (recommandé)</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# Changer en 'atelierlistaro'
sudo hostnamectl set-hostname atelierlistaro

# Modifier /etc/hosts
sudo nano /etc/hosts
# Remplacez la ligne : 127.0.1.1 mail
# Par : 127.0.1.1 atelierlistaro

# Redémarrer pour appliquer
sudo reboot
</pre>";

echo "<h3>Option 2 : Hostname avec domaine</h3>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# Changer en 'server.atelierdelistaro.fr'
sudo hostnamectl set-hostname server.atelierdelistaro.fr

# Modifier /etc/hosts
sudo nano /etc/hosts
# Ajouter : 127.0.1.1 server.atelierdelistaro.fr server

# Redémarrer
sudo reboot
</pre>";

echo "<h3>Option 3 : Garder 'mail' (si ça fonctionne)</h3>";
echo "<p>Si votre système fonctionne bien, vous pouvez garder 'mail' comme hostname. C'est juste un nom interne.</p>";
echo "</div>";

// Impact sur les services
echo "<h2>📧 Impact sur vos services</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Services qui utilisent le hostname :</h3>";
echo "<ul>";
echo "<li><strong>Postfix :</strong> Utilise le hostname pour l'envoi d'emails</li>";
echo "<li><strong>Apache :</strong> Peut utiliser le hostname dans les logs</li>";
echo "<li><strong>SSL :</strong> N'affecte pas les certificats SSL</li>";
echo "<li><strong>Votre site web :</strong> Fonctionne normalement</li>";
echo "</ul>";

echo "<h3>✅ Pas d'impact négatif sur :</h3>";
echo "<ul>";
echo "<li>Votre site atelierdelistaro.fr</li>";
echo "<li>Les certificats SSL</li>";
echo "<li>La base de données</li>";
echo "<li>Les emails (Gmail SMTP)</li>";
echo "</ul>";
echo "</div>";

// Vérification de la configuration email
echo "<h2>📬 Vérification configuration email</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";

// Vérifier si Postfix est installé
if (function_exists('shell_exec')) {
    $postfix_status = shell_exec('systemctl is-active postfix 2>/dev/null') ?: 'Non installé';
    $postfix_config = shell_exec('postconf myhostname 2>/dev/null') ?: 'Configuration non accessible';
    
    echo "<h3>État de Postfix :</h3>";
    echo "<ul>";
    echo "<li><strong>Statut :</strong> " . trim($postfix_status) . "</li>";
    echo "<li><strong>Configuration :</strong> " . trim($postfix_config) . "</li>";
    echo "</ul>";
    
    // Vérifier le fichier hosts
    $hosts_content = @file_get_contents('/etc/hosts');
    if ($hosts_content) {
        echo "<h3>Contenu de /etc/hosts :</h3>";
        echo "<pre style='background: white; padding: 10px; border-radius: 3px; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars($hosts_content);
        echo "</pre>";
    }
}
echo "</div>";

// Recommandations
echo "<h2>💡 Recommandations</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Pour Atelier de Listaro :</h3>";
echo "<ol>";
echo "<li><strong>Si tout fonctionne :</strong> Gardez 'mail' (pas de problème)</li>";
echo "<li><strong>Pour plus de clarté :</strong> Changez en 'atelierlistaro'</li>";
echo "<li><strong>Pour un setup professionnel :</strong> Utilisez 'server.atelierdelistaro.fr'</li>";
echo "</ol>";

echo "<h3>⚠️ Important :</h3>";
echo "<ul>";
echo "<li>Changement de hostname = redémarrage requis</li>";
echo "<li>Vos services continueront de fonctionner</li>";
echo "<li>Gmail SMTP n'est pas affecté</li>";
echo "<li>SSL et domaine restent inchangés</li>";
echo "</ul>";
echo "</div>";

// Script de changement automatique
echo "<h2>🚀 Script de changement automatique</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<p>Pour changer automatiquement vers 'atelierlistaro' :</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# Télécharger le script
wget http://88.124.91.246/change_hostname.sh

# Exécuter
chmod +x change_hostname.sh
sudo ./change_hostname.sh atelierlistaro
</pre>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin_panel.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Panel Admin</a> ";
echo "<a href='ssl_manager.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔐 SSL Manager</a>";
echo "</p>";
?>
