#!/bin/bash

# =============================================================================
# Installation et configuration Postfix pour atelierdelistaro.fr
# Script pour Raspberry Pi OS
# =============================================================================

echo "🍓 Installation du serveur mail pour Raspberry Pi"
echo "📧 Domaine: atelierdelistaro.fr"
echo ""

# Vérification des droits root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

# Mise à jour du système
echo "📦 Mise à jour du système..."
apt update && apt upgrade -y

# Installation de Postfix et des dépendances
echo "📧 Installation de Postfix..."
DEBIAN_FRONTEND=noninteractive apt install -y postfix mailutils

# Sauvegarde de la configuration originale
cp /etc/postfix/main.cf /etc/postfix/main.cf.backup

# Configuration Postfix pour atelierdelistaro.fr
echo "⚙️ Configuration de Postfix..."

cat > /etc/postfix/main.cf << EOF
# Configuration Postfix pour atelierdelistaro.fr
# Généré automatiquement le $(date)

# Compatibilité
compatibility_level = 2

# Configuration de base
myhostname = mail.atelierdelistaro.fr
mydomain = atelierdelistaro.fr
myorigin = \$mydomain
inet_interfaces = all
inet_protocols = ipv4
mydestination = \$myhostname, localhost.\$mydomain, localhost, \$mydomain

# Réseau local
mynetworks = 127.0.0.0/8 [::ffff:127.0.0.0]/104 [::1]/128 192.168.0.0/16 10.0.0.0/8

# Boîte aux lettres
home_mailbox = Maildir/
mailbox_command = 

# Limitations
message_size_limit = 25600000
mailbox_size_limit = 512000000

# Sécurité
smtpd_banner = \$myhostname ESMTP
biff = no
append_dot_mydomain = no
readme_directory = no

# TLS/SSL (optionnel - à configurer avec certificat)
# smtpd_tls_cert_file=/etc/ssl/certs/ssl-cert-snakeoil.pem
# smtpd_tls_key_file=/etc/ssl/private/ssl-cert-snakeoil.key
# smtpd_use_tls=yes
# smtpd_tls_session_cache_database = btree:\${data_directory}/smtpd_scache
# smtp_tls_session_cache_database = btree:\${data_directory}/smtp_scache

# Alias et transport
alias_maps = hash:/etc/aliases
alias_database = hash:/etc/aliases
relayhost = 

# Anti-spam basique
smtpd_recipient_restrictions = permit_mynetworks, permit_sasl_authenticated, reject_unauth_destination
EOF

# Configuration des aliases
echo "📝 Configuration des aliases..."
cat > /etc/aliases << EOF
# Aliases pour atelierdelistaro.fr
postmaster: root
webmaster: root
abuse: root
noreply: root
contact: www-data
admin: www-data
root: www-data
EOF

# Mise à jour des aliases
newaliases

# Configuration du hostname
echo "🏠 Configuration du hostname..."
echo "mail.atelierdelistaro.fr" > /etc/hostname
hostnamectl set-hostname mail.atelierdelistaro.fr

# Ajout dans /etc/hosts
if ! grep -q "atelierdelistaro.fr" /etc/hosts; then
    echo "127.0.1.1 mail.atelierdelistaro.fr atelierdelistaro.fr" >> /etc/hosts
fi

# Redémarrage des services
echo "🔄 Redémarrage des services..."
systemctl enable postfix
systemctl restart postfix

# Test de la configuration
echo "🧪 Test de la configuration..."
postfix check
if [ $? -eq 0 ]; then
    echo "✅ Configuration Postfix valide"
else
    echo "❌ Erreur dans la configuration Postfix"
fi

# Installation d'outils de test
echo "🛠️ Installation d'outils de test..."
apt install -y telnet swaks

# Création d'un script de test
cat > /home/pi/test_email.sh << 'EOF'
#!/bin/bash

echo "🧪 Test d'envoi d'email depuis Raspberry Pi"
echo ""

if [ -z "$1" ]; then
    echo "Usage: $0 email@destinataire.com"
    echo "Exemple: $0 test@gmail.com"
    exit 1
fi

DEST_EMAIL="$1"
SUBJECT="Test Email - Atelier de Listaro"
MESSAGE="Bonjour,

Ceci est un email de test envoyé depuis votre Raspberry Pi.

Configuration:
- Serveur: $(hostname)
- Date: $(date)
- IP locale: $(hostname -I)

Cordialement,
Atelier de Listaro"

echo "📧 Envoi d'un email de test à: $DEST_EMAIL"
echo "$MESSAGE" | mail -s "$SUBJECT" "$DEST_EMAIL"

if [ $? -eq 0 ]; then
    echo "✅ Email envoyé avec succès"
    echo "📋 Vérifiez les logs: tail -f /var/log/mail.log"
else
    echo "❌ Erreur lors de l'envoi"
    echo "📋 Vérifiez les logs: tail -f /var/log/mail.log"
fi
EOF

chmod +x /home/pi/test_email.sh
chown pi:pi /home/pi/test_email.sh

# Création d'un script de monitoring
cat > /home/pi/mail_status.sh << 'EOF'
#!/bin/bash

echo "📊 Statut du serveur mail - Atelier de Listaro"
echo "==============================================="
echo ""

echo "🔧 Statut Postfix:"
systemctl status postfix --no-pager -l

echo ""
echo "📋 Derniers logs mail:"
tail -10 /var/log/mail.log

echo ""
echo "📦 Queue des emails:"
mailq

echo ""
echo "🌐 Configuration réseau:"
echo "Hostname: $(hostname)"
echo "IP locale: $(hostname -I)"

echo ""
echo "💾 Espace disque:"
df -h /var/spool/postfix
EOF

chmod +x /home/pi/mail_status.sh
chown pi:pi /home/pi/mail_status.sh

# Informations finales
echo ""
echo "🎉 Installation terminée !"
echo "==========================================="
echo ""
echo "📧 Votre serveur mail est configuré pour: atelierdelistaro.fr"
echo ""
echo "🛠️ Scripts disponibles:"
echo "  • Test email: /home/pi/test_email.sh your@email.com"
echo "  • Statut serveur: /home/pi/mail_status.sh"
echo ""
echo "📋 Logs importants:"
echo "  • Logs mail: tail -f /var/log/mail.log"
echo "  • Queue: mailq"
echo ""
echo "⚠️ IMPORTANT - Configuration DNS nécessaire:"
echo "  • MX record: atelierdelistaro.fr -> [IP_DE_VOTRE_RASPBERRY]"
echo "  • A record: mail.atelierdelistaro.fr -> [IP_DE_VOTRE_RASPBERRY]"
echo "  • SPF record: v=spf1 ip4:[IP_DE_VOTRE_RASPBERRY] ~all"
echo ""
echo "🔄 Redémarrage recommandé: sudo reboot"
echo ""
EOF
