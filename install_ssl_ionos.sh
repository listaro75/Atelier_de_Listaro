#!/bin/bash
# Installation SSL spécifique IONOS pour Atelier de Listaro
# Script pour Raspberry Pi avec certificats IONOS

echo "🔐 Installation SSL IONOS pour atelierdelistaro.fr"
echo "=================================================="

# Vérifier si on est root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

# Créer les dossiers SSL
echo "📁 Création des dossiers SSL..."
mkdir -p /etc/ssl/atelierdelistaro
mkdir -p /etc/apache2/sites-available

echo "🔐 INSTALLATION DES CERTIFICATS IONOS"
echo "====================================="
echo ""
echo "Vous devez maintenant copier vos certificats IONOS téléchargés."
echo "Ouvrez 3 terminaux séparés pour copier chaque fichier :"
echo ""

# Configuration de la clé privée
echo "1️⃣ COPIE DE LA CLÉ PRIVÉE"
echo "========================"
echo "Dans un terminal, exécutez :"
echo "sudo nano /etc/ssl/atelierdelistaro/private.key"
echo ""
echo "Collez le contenu de votre fichier : _.atelierdelistaro.fr_private_key.key"
echo "Sauvegardez avec Ctrl+X, Y, Entrée"
echo ""
read -p "Appuyez sur Entrée quand la clé privée est copiée..."

# Configuration du certificat SSL
echo ""
echo "2️⃣ COPIE DU CERTIFICAT SSL"
echo "=========================="
echo "Dans un second terminal, exécutez :"
echo "sudo nano /etc/ssl/atelierdelistaro/certificate.crt"
echo ""
echo "Collez le contenu de votre fichier : _.atelierdelistaro.fr.crt"
echo "Sauvegardez avec Ctrl+X, Y, Entrée"
echo ""
read -p "Appuyez sur Entrée quand le certificat est copié..."

# Configuration du bundle CA
echo ""
echo "3️⃣ COPIE DU BUNDLE CA (si vous l'avez)"
echo "======================================"
echo "Dans un troisième terminal, exécutez :"
echo "sudo nano /etc/ssl/atelierdelistaro/ca_bundle.crt"
echo ""
echo "Collez le contenu de votre fichier : _.atelierdelistaro.fr.ca-bundle"
echo "Sauvegardez avec Ctrl+X, Y, Entrée"
echo ""
echo "Si vous n'avez pas ce fichier, appuyez simplement sur Entrée"
read -p "Appuyez sur Entrée pour continuer..."

# Vérifier les fichiers
echo ""
echo "🔍 Vérification des fichiers SSL..."

if [ -f "/etc/ssl/atelierdelistaro/private.key" ]; then
    if [ -s "/etc/ssl/atelierdelistaro/private.key" ]; then
        echo "✅ Clé privée trouvée et non vide"
    else
        echo "⚠️  Clé privée vide - vérifiez la copie"
    fi
else
    echo "❌ Clé privée manquante"
    exit 1
fi

if [ -f "/etc/ssl/atelierdelistaro/certificate.crt" ]; then
    if [ -s "/etc/ssl/atelierdelistaro/certificate.crt" ]; then
        echo "✅ Certificat SSL trouvé et non vide"
    else
        echo "⚠️  Certificat SSL vide - vérifiez la copie"
    fi
else
    echo "❌ Certificat SSL manquant"
    exit 1
fi

if [ -f "/etc/ssl/atelierdelistaro/ca_bundle.crt" ]; then
    if [ -s "/etc/ssl/atelierdelistaro/ca_bundle.crt" ]; then
        echo "✅ Bundle CA trouvé"
        USE_CA_BUNDLE=true
    else
        echo "⚠️  Bundle CA vide - sera ignoré"
        USE_CA_BUNDLE=false
    fi
else
    echo "ℹ️  Bundle CA non fourni (optionnel)"
    USE_CA_BUNDLE=false
fi

# Créer la configuration Apache SSL
echo ""
echo "🌐 Création de la configuration Apache SSL..."

if [ "$USE_CA_BUNDLE" = true ]; then
    CA_BUNDLE_LINE="    SSLCertificateChainFile /etc/ssl/atelierdelistaro/ca_bundle.crt"
else
    CA_BUNDLE_LINE="    # SSLCertificateChainFile /etc/ssl/atelierdelistaro/ca_bundle.crt"
fi

cat > /etc/apache2/sites-available/atelierdelistaro-ssl.conf << EOF
<VirtualHost *:443>
    ServerName atelierdelistaro.fr
    ServerAlias www.atelierdelistaro.fr
    DocumentRoot /var/www/html
    
    # SSL Configuration IONOS
    SSLEngine on
    SSLCertificateFile /etc/ssl/atelierdelistaro/certificate.crt
    SSLCertificateKeyFile /etc/ssl/atelierdelistaro/private.key
$CA_BUNDLE_LINE
    
    # Configuration SSL moderne et sécurisée
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305:DHE-RSA-AES128-GCM-SHA256:DHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder off
    SSLSessionTickets off
    
    # Headers de sécurité renforcés
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self'; frame-ancestors 'none';"
    
    # Logs SSL spécifiques
    ErrorLog \${APACHE_LOG_DIR}/atelierdelistaro-ssl-error.log
    CustomLog \${APACHE_LOG_DIR}/atelierdelistaro-ssl-access.log combined
    LogLevel info ssl:warn
    
    # Configuration PHP optimisée
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Optimisations PHP pour e-commerce
        php_admin_value upload_max_filesize 50M
        php_admin_value post_max_size 50M
        php_admin_value max_execution_time 300
        php_admin_value memory_limit 256M
        php_admin_value max_input_vars 3000
        
        # Protection des fichiers sensibles
        <FilesMatch "\.(env|ini|log|sh)$">
            Require all denied
        </FilesMatch>
        
        # Cache pour les ressources statiques
        <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$">
            ExpiresActive On
            ExpiresDefault "access plus 1 month"
            Header append Cache-Control "public, immutable"
        </FilesMatch>
    </Directory>
    
    # Compression pour améliorer les performances
    <IfModule mod_deflate.c>
        AddOutputFilterByType DEFLATE text/plain
        AddOutputFilterByType DEFLATE text/html
        AddOutputFilterByType DEFLATE text/xml
        AddOutputFilterByType DEFLATE text/css
        AddOutputFilterByType DEFLATE application/xml
        AddOutputFilterByType DEFLATE application/xhtml+xml
        AddOutputFilterByType DEFLATE application/rss+xml
        AddOutputFilterByType DEFLATE application/javascript
        AddOutputFilterByType DEFLATE application/x-javascript
        AddOutputFilterByType DEFLATE application/json
    </IfModule>
</VirtualHost>
EOF

# Créer la redirection HTTP vers HTTPS
echo "🔄 Configuration de la redirection HTTP → HTTPS..."
cat > /etc/apache2/sites-available/atelierdelistaro-redirect.conf << 'EOF'
<VirtualHost *:80>
    ServerName atelierdelistaro.fr
    ServerAlias www.atelierdelistaro.fr
    
    # Redirection permanente vers HTTPS avec préservation de l'URL
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
    
    # Logs pour debug
    ErrorLog ${APACHE_LOG_DIR}/atelierdelistaro-redirect-error.log
    CustomLog ${APACHE_LOG_DIR}/atelierdelistaro-redirect-access.log combined
</VirtualHost>
EOF

# Configurer les permissions
echo "🔒 Configuration des permissions de sécurité..."
chmod 600 /etc/ssl/atelierdelistaro/private.key
chmod 644 /etc/ssl/atelierdelistaro/*.crt 2>/dev/null || true
chown -R root:root /etc/ssl/atelierdelistaro/

# Activer les modules Apache nécessaires
echo "🔧 Activation des modules Apache..."
a2enmod ssl
a2enmod headers
a2enmod rewrite
a2enmod expires
a2enmod deflate

# Activer les sites
echo "🌐 Activation des sites SSL..."
a2ensite atelierdelistaro-ssl
a2ensite atelierdelistaro-redirect

# Désactiver le site par défaut pour éviter les conflits
echo "🚫 Désactivation du site par défaut..."
a2dissite 000-default 2>/dev/null || true

# Test de la configuration
echo ""
echo "🧪 Test de la configuration Apache..."
if apache2ctl configtest; then
    echo "✅ Configuration Apache valide"
    
    echo ""
    echo "🔄 Redémarrage d'Apache..."
    if systemctl restart apache2; then
        echo "✅ Apache redémarré avec succès"
        
        # Vérifier que Apache fonctionne
        sleep 2
        if systemctl is-active --quiet apache2; then
            echo ""
            echo "🎉 INSTALLATION SSL TERMINÉE AVEC SUCCÈS !"
            echo "========================================="
            echo ""
            echo "✅ Certificats SSL installés"
            echo "✅ Configuration Apache créée"
            echo "✅ Redirection HTTP → HTTPS activée"
            echo "✅ Headers de sécurité configurés"
            echo "✅ Optimisations e-commerce activées"
            echo ""
            echo "🔍 TESTS À EFFECTUER :"
            echo "====================="
            echo "1. Test local : https://88.124.91.246"
            echo "2. Test domaine : https://atelierdelistaro.fr"
            echo "3. Test SSL Labs : https://www.ssllabs.com/ssltest/analyze.html?d=atelierdelistaro.fr"
            echo ""
            echo "⚠️  IMPORTANT - CONFIGURATION IONOS :"
            echo "======================================"
            echo "Dans votre interface IONOS :"
            echo "1. Modifiez la redirection de 'http://88.124.91.246' vers 'https://88.124.91.246'"
            echo "2. Ou configurez un enregistrement A direct vers 88.124.91.246"
            echo ""
            echo "📧 Votre système email bénéficiera automatiquement du SSL !"
            echo ""
        else
            echo "❌ Apache ne fonctionne pas correctement après redémarrage"
            systemctl status apache2
        fi
    else
        echo "❌ Erreur lors du redémarrage d'Apache"
        systemctl status apache2
    fi
else
    echo "❌ Erreur dans la configuration Apache"
    echo ""
    echo "🔍 Vérifications suggérées :"
    echo "1. Vérifiez que tous les certificats sont bien copiés"
    echo "2. Vérifiez le format des certificats (pas d'espaces/caractères parasites)"
    echo "3. Consultez les logs : sudo tail -f /var/log/apache2/error.log"
fi

echo ""
echo "📋 Logs utiles pour le debug :"
echo "=============================="
echo "• Erreurs Apache : sudo tail -f /var/log/apache2/error.log"
echo "• Accès SSL : sudo tail -f /var/log/apache2/atelierdelistaro-ssl-access.log"
echo "• Erreurs SSL : sudo tail -f /var/log/apache2/atelierdelistaro-ssl-error.log"
