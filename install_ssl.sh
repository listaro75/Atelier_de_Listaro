#!/bin/bash
# Installation automatique SSL pour Atelier de Listaro
# Exécutez ce script sur votre Raspberry Pi

echo "🔐 Installation SSL pour atelierdelistaro.fr"
echo "============================================"

# Vérifier si on est root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

# Créer les dossiers SSL
echo "📁 Création des dossiers SSL..."
mkdir -p /etc/ssl/atelierdelistaro
mkdir -p /etc/apache2/sites-available

# Copier la clé privée que vous avez
echo "🔑 Copiez maintenant votre clé privée :"
echo "nano /etc/ssl/atelierdelistaro/private.key"
echo ""
echo "Collez le contenu de votre fichier _.atelierdelistaro.fr_private_key.key"
echo "Appuyez sur Entrée quand c'est fait..."
read -p ""

# Vérifier si la clé privée a été copiée
if [ ! -f "/etc/ssl/atelierdelistaro/private.key" ]; then
    echo "❌ Clé privée non trouvée. Création du fichier..."
    touch /etc/ssl/atelierdelistaro/private.key
    echo "⚠️  Vous devez maintenant copier votre clé privée dans ce fichier"
fi

# Demander le certificat SSL
echo ""
echo "📜 Maintenant, copiez votre certificat SSL :"
echo "nano /etc/ssl/atelierdelistaro/certificate.crt"
echo ""
echo "Vous devez avoir reçu un fichier .crt de votre fournisseur SSL"
echo "Appuyez sur Entrée quand c'est fait..."
read -p ""

# Créer la configuration Apache SSL
echo "🌐 Création de la configuration Apache SSL..."
cat > /etc/apache2/sites-available/atelierdelistaro-ssl.conf << 'EOF'
<VirtualHost *:443>
    ServerName atelierdelistaro.fr
    ServerAlias www.atelierdelistaro.fr
    DocumentRoot /var/www/html
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/atelierdelistaro/certificate.crt
    SSLCertificateKeyFile /etc/ssl/atelierdelistaro/private.key
    
    # Si vous avez un certificat intermédiaire, décommentez cette ligne
    # SSLCertificateChainFile /etc/ssl/atelierdelistaro/ca_bundle.crt
    
    # Configuration SSL moderne
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLCipherSuite ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384
    SSLHonorCipherOrder off
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/atelierdelistaro-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/atelierdelistaro-ssl-access.log combined
    
    # Configuration PHP et répertoire
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Optimisations PHP
        php_admin_value upload_max_filesize 50M
        php_admin_value post_max_size 50M
        php_admin_value max_execution_time 300
        php_admin_value memory_limit 256M
        
        # Cache statique
        <FilesMatch "\.(css|js|png|jpg|jpeg|gif|ico|svg)$">
            ExpiresActive On
            ExpiresDefault "access plus 1 month"
            Header append Cache-Control "public"
        </FilesMatch>
    </Directory>
    
    # Compression
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
    </IfModule>
</VirtualHost>
EOF

# Créer la redirection HTTP vers HTTPS
echo "🔄 Création de la redirection HTTP vers HTTPS..."
cat > /etc/apache2/sites-available/atelierdelistaro-redirect.conf << 'EOF'
<VirtualHost *:80>
    ServerName atelierdelistaro.fr
    ServerAlias www.atelierdelistaro.fr
    
    # Redirection permanente vers HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
    
    ErrorLog ${APACHE_LOG_DIR}/atelierdelistaro-redirect-error.log
    CustomLog ${APACHE_LOG_DIR}/atelierdelistaro-redirect-access.log combined
</VirtualHost>
EOF

# Activer les modules nécessaires
echo "🔧 Activation des modules Apache..."
a2enmod ssl
a2enmod headers
a2enmod rewrite
a2enmod expires
a2enmod deflate

# Définir les permissions
echo "🔒 Configuration des permissions..."
chmod 600 /etc/ssl/atelierdelistaro/private.key
chmod 644 /etc/ssl/atelierdelistaro/*.crt 2>/dev/null || true
chown -R root:root /etc/ssl/atelierdelistaro/

# Activer les sites
echo "🌐 Activation des sites SSL..."
a2ensite atelierdelistaro-ssl
a2ensite atelierdelistaro-redirect

# Désactiver le site par défaut si nécessaire
a2dissite 000-default 2>/dev/null || true

# Tester la configuration
echo "🧪 Test de la configuration Apache..."
if apache2ctl configtest; then
    echo "✅ Configuration Apache valide"
    
    echo "🔄 Redémarrage d'Apache..."
    systemctl restart apache2
    
    if systemctl is-active --quiet apache2; then
        echo "✅ Apache redémarré avec succès"
        echo ""
        echo "🎉 Installation SSL terminée !"
        echo "================================"
        echo "✅ Certificat SSL installé"
        echo "✅ Redirection HTTP → HTTPS activée"
        echo "✅ Headers de sécurité configurés"
        echo "✅ Optimisations activées"
        echo ""
        echo "🔍 Tests à effectuer :"
        echo "• https://atelierdelistaro.fr"
        echo "• https://www.ssllabs.com/ssltest/analyze.html?d=atelierdelistaro.fr"
        echo ""
        echo "📧 Votre système d'email bénéficiera aussi du SSL !"
    else
        echo "❌ Erreur lors du redémarrage d'Apache"
        systemctl status apache2
    fi
else
    echo "❌ Erreur dans la configuration Apache"
    echo "Vérifiez que vous avez bien copié le certificat SSL"
fi
