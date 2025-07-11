#!/bin/bash
# Configuration SSL pour Atelier de Listaro
# Script pour Raspberry Pi

echo "🔐 Configuration SSL pour atelierdelistaro.fr"
echo "=============================================="

# Créer le dossier SSL s'il n'existe pas
sudo mkdir -p /etc/ssl/atelierdelistaro

# Vérifier si les fichiers SSL existent
echo "📁 Vérification des fichiers SSL..."

if [ -f "/etc/ssl/atelierdelistaro/private.key" ]; then
    echo "✅ Clé privée trouvée"
else
    echo "❌ Clé privée manquante"
fi

if [ -f "/etc/ssl/atelierdelistaro/certificate.crt" ]; then
    echo "✅ Certificat SSL trouvé"
else
    echo "❌ Certificat SSL manquant"
fi

if [ -f "/etc/ssl/atelierdelistaro/ca_bundle.crt" ]; then
    echo "✅ Bundle CA trouvé"
else
    echo "⚠️  Bundle CA manquant (optionnel)"
fi

echo ""
echo "📋 Instructions pour copier vos fichiers SSL:"
echo "=============================================="
echo "1. Copiez votre clé privée:"
echo "   sudo nano /etc/ssl/atelierdelistaro/private.key"
echo ""
echo "2. Copiez votre certificat SSL:"
echo "   sudo nano /etc/ssl/atelierdelistaro/certificate.crt"
echo ""
echo "3. Si vous avez un bundle CA:"
echo "   sudo nano /etc/ssl/atelierdelistaro/ca_bundle.crt"
echo ""
echo "4. Définir les permissions:"
echo "   sudo chmod 600 /etc/ssl/atelierdelistaro/private.key"
echo "   sudo chmod 644 /etc/ssl/atelierdelistaro/*.crt"
echo "   sudo chown root:root /etc/ssl/atelierdelistaro/*"
echo ""

# Configuration Apache
echo "🌐 Configuration Apache SSL..."
if [ -f "/etc/apache2/sites-available/atelierdelistaro-ssl.conf" ]; then
    echo "✅ Configuration SSL Apache existe"
else
    echo "⚠️  Création de la configuration SSL Apache..."
    sudo tee /etc/apache2/sites-available/atelierdelistaro-ssl.conf > /dev/null << 'EOF'
<VirtualHost *:443>
    ServerName atelierdelistaro.fr
    ServerAlias www.atelierdelistaro.fr
    DocumentRoot /var/www/html
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/ssl/atelierdelistaro/certificate.crt
    SSLCertificateKeyFile /etc/ssl/atelierdelistaro/private.key
    # SSLCertificateChainFile /etc/ssl/atelierdelistaro/ca_bundle.crt
    
    # Security headers
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
    Header always set X-Frame-Options DENY
    Header always set X-Content-Type-Options nosniff
    
    # Logs
    ErrorLog ${APACHE_LOG_DIR}/atelierdelistaro-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/atelierdelistaro-ssl-access.log combined
    
    # PHP Configuration
    <Directory /var/www/html>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # PHP settings
        php_admin_value upload_max_filesize 50M
        php_admin_value post_max_size 50M
        php_admin_value max_execution_time 300
        php_admin_value memory_limit 256M
    </Directory>
</VirtualHost>
EOF
    echo "✅ Configuration SSL créée"
fi

# Configuration HTTP avec redirection
echo "🔄 Configuration redirection HTTP vers HTTPS..."
if [ -f "/etc/apache2/sites-available/atelierdelistaro-redirect.conf" ]; then
    echo "✅ Configuration redirection existe"
else
    sudo tee /etc/apache2/sites-available/atelierdelistaro-redirect.conf > /dev/null << 'EOF'
<VirtualHost *:80>
    ServerName atelierdelistaro.fr
    ServerAlias www.atelierdelistaro.fr
    
    # Redirection permanente vers HTTPS
    Redirect permanent / https://atelierdelistaro.fr/
    
    ErrorLog ${APACHE_LOG_DIR}/atelierdelistaro-redirect-error.log
    CustomLog ${APACHE_LOG_DIR}/atelierdelistaro-redirect-access.log combined
</VirtualHost>
EOF
    echo "✅ Configuration redirection créée"
fi

# Activer les modules SSL
echo "🔧 Activation des modules Apache..."
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod rewrite

echo ""
echo "📋 Prochaines étapes:"
echo "===================="
echo "1. Copiez vos certificats SSL dans /etc/ssl/atelierdelistaro/"
echo "2. Activez les sites:"
echo "   sudo a2ensite atelierdelistaro-ssl"
echo "   sudo a2ensite atelierdelistaro-redirect"
echo "3. Désactivez le site par défaut:"
echo "   sudo a2dissite 000-default"
echo "4. Testez la configuration:"
echo "   sudo apache2ctl configtest"
echo "5. Redémarrez Apache:"
echo "   sudo systemctl restart apache2"
echo ""
echo "🔍 Test SSL:"
echo "   https://atelierdelistaro.fr"
echo "   https://www.ssllabs.com/ssltest/"
