#!/bin/bash

# =============================================================================
# Diagnostic approfondi du serveur mail - Raspberry Pi
# =============================================================================

echo "🍓 Diagnostic du serveur mail - Atelier de Listaro"
echo "=================================================="
echo ""

# Couleurs pour l'affichage
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

function print_status() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✅ $2${NC}"
    else
        echo -e "${RED}❌ $2${NC}"
    fi
}

function print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

function print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

# 1. Vérification des services
echo "🔧 1. Statut des services"
echo "------------------------"

systemctl is-active --quiet postfix
print_status $? "Postfix"

systemctl is-enabled --quiet postfix
print_status $? "Postfix (démarrage automatique)"

# 2. Configuration système
echo ""
echo "🌐 2. Configuration système"
echo "---------------------------"

echo "Hostname: $(hostname)"
echo "Domaine configuré: $(hostname -d 2>/dev/null || echo 'Non configuré')"
echo "IP locale: $(hostname -I)"

# Vérification DNS
echo ""
echo "🔍 DNS Configuration:"
if [ -f /etc/hosts ]; then
    grep -E "(atelierdelistaro|mail)" /etc/hosts || echo "Aucune entrée atelierdelistaro dans /etc/hosts"
else
    echo "/etc/hosts non trouvé"
fi

# 3. Configuration Postfix
echo ""
echo "📧 3. Configuration Postfix"
echo "---------------------------"

if [ -f /etc/postfix/main.cf ]; then
    echo "Configuration Postfix trouvée:"
    echo "myhostname = $(postconf -h myhostname 2>/dev/null || echo 'Non configuré')"
    echo "mydomain = $(postconf -h mydomain 2>/dev/null || echo 'Non configuré')"
    echo "myorigin = $(postconf -h myorigin 2>/dev/null || echo 'Non configuré')"
    echo "mydestination = $(postconf -h mydestination 2>/dev/null || echo 'Non configuré')"
else
    print_warning "Fichier /etc/postfix/main.cf non trouvé"
fi

# Test de configuration
echo ""
echo "🧪 Test de configuration Postfix:"
postfix check
if [ $? -eq 0 ]; then
    print_status 0 "Configuration Postfix valide"
else
    print_status 1 "Erreurs dans la configuration Postfix"
fi

# 4. Permissions et fichiers
echo ""
echo "📁 4. Permissions et fichiers"
echo "-----------------------------"

# Vérification sendmail
if [ -f /usr/sbin/sendmail ]; then
    print_status 0 "/usr/sbin/sendmail existe"
    ls -la /usr/sbin/sendmail
else
    print_status 1 "/usr/sbin/sendmail non trouvé"
fi

# Vérification des répertoires Postfix
if [ -d /var/spool/postfix ]; then
    print_status 0 "Répertoire Postfix spool existe"
    echo "Espace disponible:"
    df -h /var/spool/postfix
else
    print_status 1 "Répertoire Postfix spool non trouvé"
fi

# 5. Logs et queue
echo ""
echo "📋 5. Logs et queue"
echo "-------------------"

# Queue Postfix
echo "Queue Postfix:"
mailq

echo ""
echo "Derniers logs mail (10 lignes):"
if [ -f /var/log/mail.log ]; then
    tail -10 /var/log/mail.log
elif [ -f /var/log/maillog ]; then
    tail -10 /var/log/maillog
else
    print_warning "Aucun fichier de log mail trouvé"
fi

# 6. Test d'envoi simple
echo ""
echo "🧪 6. Test d'envoi simple"
echo "-------------------------"

if [ ! -z "$1" ]; then
    TEST_EMAIL="$1"
    echo "Test d'envoi vers: $TEST_EMAIL"
    
    # Test avec echo et sendmail
    echo "Test via sendmail direct:"
    echo -e "Subject: Test Diagnostic Raspberry Pi\nFrom: noreply@atelierdelistaro.fr\nTo: $TEST_EMAIL\n\nCeci est un test d'envoi depuis le diagnostic Raspberry Pi.\n\nDate: $(date)\nHostname: $(hostname)" | /usr/sbin/sendmail -t
    
    if [ $? -eq 0 ]; then
        print_status 0 "Commande sendmail exécutée"
    else
        print_status 1 "Erreur avec sendmail"
    fi
    
    # Vérifier la queue après envoi
    echo ""
    echo "Queue après test:"
    mailq
    
else
    print_info "Pour tester l'envoi, relancez avec: $0 votre@email.com"
fi

# 7. Configuration PHP
echo ""
echo "🐘 7. Configuration PHP"
echo "-----------------------"

php -r "echo 'sendmail_path: ' . ini_get('sendmail_path') . PHP_EOL;"
php -r "echo 'mail() disponible: ' . (function_exists('mail') ? 'Oui' : 'Non') . PHP_EOL;"

# Test PHP mail
if [ ! -z "$1" ]; then
    echo ""
    echo "Test PHP mail():"
    php -r "
    \$to = '$TEST_EMAIL';
    \$subject = 'Test PHP mail() - Raspberry Pi';
    \$message = 'Test envoyé via PHP mail() depuis Raspberry Pi.\nDate: ' . date('Y-m-d H:i:s');
    \$headers = 'From: noreply@atelierdelistaro.fr';
    
    if (mail(\$to, \$subject, \$message, \$headers)) {
        echo 'PHP mail() retourne succès\n';
    } else {
        echo 'PHP mail() retourne échec\n';
    }
    "
fi

# 8. Recommandations
echo ""
echo "💡 8. Recommandations"
echo "--------------------"

# Vérifier si le hostname est correct
current_hostname=$(hostname)
if [[ "$current_hostname" != *"atelierdelistaro.fr"* ]]; then
    print_warning "Le hostname ne contient pas 'atelierdelistaro.fr'"
    echo "   Recommandation: sudo hostnamectl set-hostname mail.atelierdelistaro.fr"
fi

# Vérifier la configuration DNS
print_info "Assurez-vous que ces enregistrements DNS sont configurés:"
echo "   MX: atelierdelistaro.fr → [IP_PUBLIQUE_RASPBERRY]"
echo "   A:  mail.atelierdelistaro.fr → [IP_PUBLIQUE_RASPBERRY]"
echo "   SPF: v=spf1 ip4:[IP_PUBLIQUE_RASPBERRY] ~all"

echo ""
echo "🎉 Diagnostic terminé !"
echo "======================"
