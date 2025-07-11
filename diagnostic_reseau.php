<?php
/**
 * Diagnostic de connectivité réseau
 * Atelier de Listaro - Dépannage IP publique
 */

echo "<h1>🚨 Diagnostic de connectivité - Atelier de Listaro</h1>";

// Informations actuelles
echo "<h2>📍 Informations de connexion actuelles</h2>";
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #dc3545;'>";
echo "<h3>⚠️ Problème rapporté :</h3>";
echo "<p><strong>IP publique 88.124.91.246 non accessible</strong></p>";
echo "<p>Date du problème : " . date('d/m/Y à H:i:s') . "</p>";
echo "</div>";

// Tests de connectivité
echo "<h2>🔍 Tests de connectivité</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";

// Test de la connexion actuelle
$current_ip = $_SERVER['SERVER_ADDR'] ?? 'Non défini';
$current_host = $_SERVER['HTTP_HOST'] ?? 'Non défini';
$user_ip = $_SERVER['REMOTE_ADDR'] ?? 'Non défini';

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #e9ecef;'><th style='padding: 10px;'>Paramètre</th><th style='padding: 10px;'>Valeur</th><th style='padding: 10px;'>Statut</th></tr>";
echo "<tr><td style='padding: 8px;'>IP du serveur</td><td style='padding: 8px;'>$current_ip</td><td style='padding: 8px;'>" . ($current_ip !== 'Non défini' ? '✅' : '❌') . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Host actuel</td><td style='padding: 8px;'>$current_host</td><td style='padding: 8px;'>" . ($current_host !== 'Non défini' ? '✅' : '❌') . "</td></tr>";
echo "<tr><td style='padding: 8px;'>Votre IP</td><td style='padding: 8px;'>$user_ip</td><td style='padding: 8px;'>" . ($user_ip !== 'Non défini' ? '✅' : '❌') . "</td></tr>";
echo "</table>";
echo "</div>";

// Causes possibles
echo "<h2>🤔 Causes possibles</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Problèmes réseau possibles :</h3>";
echo "<ul>";
echo "<li><strong>Raspberry Pi éteint/redémarré :</strong> Panne de courant ou redémarrage</li>";
echo "<li><strong>Connexion internet coupée :</strong> Problème avec votre FAI</li>";
echo "<li><strong>IP dynamique changée :</strong> Votre FAI a changé l'IP publique</li>";
echo "<li><strong>Routeur/box redémarrée :</strong> Configuration NAT perdue</li>";
echo "<li><strong>Firewall/sécurité :</strong> Blocage par le FAI ou antivirus</li>";
echo "<li><strong>Configuration Apache :</strong> Service Apache arrêté</li>";
echo "</ul>";
echo "</div>";

// Solutions immédiates
echo "<h2>🛠️ Solutions immédiates</h2>";
echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";

echo "<h3>1️⃣ Vérifications de base</h3>";
echo "<ol>";
echo "<li><strong>Raspberry Pi allumé ?</strong> Vérifiez les LEDs d'activité</li>";
echo "<li><strong>Câble réseau connecté ?</strong> Vérifiez la connexion Ethernet</li>";
echo "<li><strong>Box internet fonctionnelle ?</strong> Testez avec d'autres appareils</li>";
echo "</ol>";

echo "<h3>2️⃣ Accès local au Raspberry Pi</h3>";
echo "<p>Si vous êtes chez vous, essayez ces IP locales :</p>";
echo "<ul>";
echo "<li><a href='http://192.168.1.100' target='_blank'>http://192.168.1.100</a></li>";
echo "<li><a href='http://192.168.1.101' target='_blank'>http://192.168.1.101</a></li>";
echo "<li><a href='http://192.168.1.50' target='_blank'>http://192.168.1.50</a></li>";
echo "<li><a href='http://192.168.0.100' target='_blank'>http://192.168.0.100</a></li>";
echo "<li><a href='http://mail.local' target='_blank'>http://mail.local</a> (si mDNS fonctionne)</li>";
echo "</ul>";

echo "<h3>3️⃣ Commandes de diagnostic SSH</h3>";
echo "<p>Si vous pouvez vous connecter en SSH localement :</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>
# Vérifier l'IP locale
ip addr show

# Vérifier l'IP publique
curl ifconfig.me

# Statut des services
sudo systemctl status apache2
sudo systemctl status networking

# Redémarrer Apache si nécessaire
sudo systemctl restart apache2

# Vérifier les ports ouverts
sudo netstat -tlnp | grep :80
</pre>";
echo "</div>";

// Solutions de secours
echo "<h2>🆘 Solutions de secours</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0;'>";

echo "<h3>Option 1 : Hébergement temporaire</h3>";
echo "<p>En attendant que votre Raspberry Pi soit de nouveau accessible :</p>";
echo "<ul>";
echo "<li><strong>InfinityFree :</strong> Utilisez votre hébergement <code>atelierdelistaro.great-site.net</code></li>";
echo "<li><strong>GitHub Pages :</strong> Version statique temporaire</li>";
echo "<li><strong>Netlify/Vercel :</strong> Déploiement rapide gratuit</li>";
echo "</ul>";

echo "<h3>Option 2 : Configuration domaine IONOS</h3>";
echo "<p>Modifiez temporairement la redirection IONOS :</p>";
echo "<ol>";
echo "<li>Connectez-vous à votre interface IONOS</li>";
echo "<li>Changez la redirection vers votre site de secours</li>";
echo "<li>Ou configurez un enregistrement A vers une nouvelle IP</li>";
echo "</ol>";

echo "<h3>Option 3 : IP dynamique</h3>";
echo "<p>Si votre IP a changé :</p>";
echo "<ul>";
echo "<li>Configurez un service DynDNS (No-IP, DuckDNS)</li>";
echo "<li>Utilisez un script de mise à jour automatique</li>";
echo "<li>Contactez votre FAI pour une IP fixe</li>";
echo "</ul>";
echo "</div>";

// Récupération de données
echo "<h2>💾 Récupération de vos données</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Vos données importantes :</h3>";
echo "<ul>";
echo "<li><strong>Base de données :</strong> Accessible via phpMyAdmin local</li>";
echo "<li><strong>Images produits :</strong> Dossier /var/www/html/uploads/</li>";
echo "<li><strong>Configuration :</strong> Fichiers PHP dans /var/www/html/</li>";
echo "<li><strong>Emails :</strong> Configuration Gmail SMTP intacte</li>";
echo "</ul>";

echo "<h3>📧 Système email de secours :</h3>";
echo "<p>Votre configuration Gmail SMTP fonctionne indépendamment :</p>";
echo "<ul>";
echo "<li>✅ <strong>Gmail SMTP :</strong> lucien.dacunha@gmail.com</li>";
echo "<li>✅ <strong>Mot de passe :</strong> Configuré (xdiz iydk tisz jfop)</li>";
echo "<li>✅ <strong>Newsletter :</strong> Peut fonctionner depuis n'importe quel serveur</li>";
echo "</ul>";
echo "</div>";

// Plan d'action
echo "<h2>📋 Plan d'action</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Étapes immédiates :</h3>";
echo "<ol>";
echo "<li><strong>Vérification physique :</strong> Raspberry Pi, box, câbles</li>";
echo "<li><strong>Test local :</strong> Essayez les IP locales listées ci-dessus</li>";
echo "<li><strong>SSH local :</strong> Connectez-vous en SSH si possible</li>";
echo "<li><strong>IP publique :</strong> Vérifiez votre nouvelle IP avec <code>curl ifconfig.me</code></li>";
echo "<li><strong>Redirection IONOS :</strong> Mettez à jour si nécessaire</li>";
echo "</ol>";

echo "<h3>Solutions à moyen terme :</h3>";
echo "<ul>";
echo "<li>Configuration DynDNS pour éviter ce problème</li>";
echo "<li>Monitoring automatique de votre serveur</li>";
echo "<li>Sauvegarde automatique vers le cloud</li>";
echo "</ul>";
echo "</div>";

// Informations de contact
echo "<h2>📞 Ressources utiles</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
echo "<h3>Services de vérification IP :</h3>";
echo "<ul>";
echo "<li><a href='https://whatismyipaddress.com' target='_blank'>whatismyipaddress.com</a></li>";
echo "<li><a href='https://ifconfig.me' target='_blank'>ifconfig.me</a></li>";
echo "<li><a href='https://ipinfo.io' target='_blank'>ipinfo.io</a></li>";
echo "</ul>";

echo "<h3>Services DynDNS gratuits :</h3>";
echo "<ul>";
echo "<li><a href='https://www.noip.com' target='_blank'>No-IP</a></li>";
echo "<li><a href='https://www.duckdns.org' target='_blank'>DuckDNS</a></li>";
echo "<li><a href='https://freedns.afraid.org' target='_blank'>FreeDNS</a></li>";
echo "</ul>";
echo "</div>";

echo "<p style='text-align: center; margin-top: 30px; padding: 20px; background: #e7f3ff; border-radius: 5px;'>";
echo "<strong>🔄 Cette page se recharge automatiquement pour tester la connectivité</strong><br>";
echo "<small>Si vous voyez cette page, c'est que vous avez au moins un accès partiel !</small>";
echo "</p>";

// Auto-refresh pour tester la connectivité
echo "<script>setTimeout(() => location.reload(), 30000);</script>";
?>
