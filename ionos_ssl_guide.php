<?php
/**
 * Configuration adaptée pour Raspberry Pi + IONOS SSL
 * Atelier de Listaro
 */

echo "<h1>🔧 Configuration SSL IONOS + Raspberry Pi</h1>";

// Vérifications du système
echo "<h2>📊 État actuel du système</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
$currentUrl = ($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];

echo "<ul>";
echo "<li><strong>Serveur actuel :</strong> " . $currentUrl . "</li>";
echo "<li><strong>IP :</strong> " . $_SERVER['SERVER_ADDR'] . "</li>";
echo "<li><strong>Port :</strong> " . $_SERVER['SERVER_PORT'] . "</li>";
echo "<li><strong>SSL :</strong> " . ($isHttps ? '✅ Activé' : '❌ Non configuré') . "</li>";
echo "</ul>";
echo "</div>";

// Instructions spécifiques IONOS
echo "<h2>🔐 Instructions IONOS SSL</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>📥 1. Télécharger vos certificats IONOS</h3>";
echo "<ol>";
echo "<li>Connectez-vous à votre espace IONOS</li>";
echo "<li>Allez dans 'Domaines' → 'atelierdelistaro.fr'</li>";
echo "<li>Cliquez sur <strong>'Gérer'</strong> à côté du certificat SSL</li>";
echo "<li>Téléchargez tous les fichiers de certificat</li>";
echo "</ol>";

echo "<p><strong>Fichiers à récupérer :</strong></p>";
echo "<ul>";
echo "<li>✅ <code>_.atelierdelistaro.fr_private_key.key</code> (vous l'avez)</li>";
echo "<li>📥 <code>_.atelierdelistaro.fr.crt</code> (à télécharger)</li>";
echo "<li>📥 <code>_.atelierdelistaro.fr.ca-bundle</code> (à télécharger)</li>";
echo "</ul>";
echo "</div>";

// Script d'installation automatique
echo "<h2>🚀 Installation automatique</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px;'>";
echo "<p>Une fois vos certificats téléchargés, exécutez sur votre Raspberry Pi :</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# 1. Télécharger le script d'installation
wget http://88.124.91.246/install_ssl_ionos.sh

# 2. Rendre exécutable
chmod +x install_ssl_ionos.sh

# 3. Lancer l'installation
sudo ./install_ssl_ionos.sh
</pre>";
echo "</div>";

// Configuration DNS
echo "<h2>🌐 Vérification DNS</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h3>Configuration DNS actuelle (IONOS) :</h3>";
echo "<ul>";
echo "<li><strong>Type :</strong> Domaine supplémentaire</li>";
echo "<li><strong>Redirection :</strong> http://88.124.91.246</li>";
echo "<li><strong>SSL :</strong> Certificat attribué</li>";
echo "</ul>";

echo "<h3>⚠️ Modification requise :</h3>";
echo "<p>Pour que le SSL fonctionne, vous devez modifier la redirection IONOS :</p>";
echo "<ol>";
echo "<li>Dans IONOS, changez la redirection de <code>http://88.124.91.246</code> vers <code>https://88.124.91.246</code></li>";
echo "<li>Ou mieux : configurez un enregistrement A qui pointe directement vers 88.124.91.246</li>";
echo "</ol>";
echo "</div>";

// Test de connectivité
echo "<h2>🧪 Tests de connectivité</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";

// Test de ping vers le domaine
$domain = 'atelierdelistaro.fr';
$ip = gethostbyname($domain);

echo "<h3>Résolution DNS :</h3>";
echo "<ul>";
echo "<li><strong>$domain</strong> → $ip</li>";
echo "<li><strong>Statut :</strong> " . ($ip !== $domain ? '✅ Résolu' : '❌ Non résolu') . "</li>";
echo "</ul>";

// Test HTTP
echo "<h3>Tests d'accès :</h3>";
echo "<ul>";
echo "<li><a href='http://88.124.91.246' target='_blank'>http://88.124.91.246</a> (IP directe)</li>";
echo "<li><a href='http://atelierdelistaro.fr' target='_blank'>http://atelierdelistaro.fr</a> (domaine HTTP)</li>";
echo "<li><a href='https://atelierdelistaro.fr' target='_blank'>https://atelierdelistaro.fr</a> (domaine HTTPS - après config)</li>";
echo "</ul>";
echo "</div>";

// Étapes suivantes
echo "<h2>📋 Plan d'action</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<h3>Étapes à suivre dans l'ordre :</h3>";
echo "<ol>";
echo "<li>✅ <strong>Récupérer les certificats IONOS</strong> (certificat + bundle)</li>";
echo "<li>🔧 <strong>Installer SSL sur Raspberry Pi</strong> (script automatique)</li>";
echo "<li>🌐 <strong>Modifier la redirection IONOS</strong> (HTTP → HTTPS)</li>";
echo "<li>🧪 <strong>Tester le SSL</strong> (atelierdelistaro.fr en HTTPS)</li>";
echo "<li>📧 <strong>Mettre à jour les emails</strong> (liens HTTPS)</li>";
echo "</ol>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='ssl_manager.php' style='background: #007cba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🔙 Retour SSL Manager</a> ";
echo "<a href='admin_panel.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px;'>🏠 Panel Admin</a>";
echo "</p>";
?>
