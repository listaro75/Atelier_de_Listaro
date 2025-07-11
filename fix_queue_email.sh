#!/bin/bash

# =============================================================================
# Script de dépannage queue email - Raspberry Pi
# =============================================================================

echo "🔧 Dépannage Queue Email - Atelier de Listaro"
echo "=============================================="
echo ""

# Couleurs
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# 1. État actuel de la queue
echo "📮 1. État de la queue Postfix"
echo "------------------------------"
mailq

# 2. Analyse des logs détaillée
echo ""
echo "📋 2. Recherche des logs mail"
echo "-----------------------------"

LOG_FILES=()
if [ -f /var/log/mail.log ]; then
    LOG_FILES+=("/var/log/mail.log")
fi
if [ -f /var/log/maillog ]; then
    LOG_FILES+=("/var/log/maillog")
fi
if [ -f /var/log/syslog ]; then
    LOG_FILES+=("/var/log/syslog")
fi

if [ ${#LOG_FILES[@]} -eq 0 ]; then
    print_warning "Aucun fichier de log trouvé"
    echo "Activation du logging..."
    
    # Activer le logging
    if [ ! -f /var/log/mail.log ]; then
        sudo touch /var/log/mail.log
        sudo chmod 644 /var/log/mail.log
        sudo chown syslog:adm /var/log/mail.log
    fi
    
    # Redémarrer rsyslog
    sudo systemctl restart rsyslog
    print_info "Logging activé - redémarrage de rsyslog"
else
    echo "Fichiers de log trouvés:"
    for log in "${LOG_FILES[@]}"; do
        echo "  - $log"
    done
fi

# 3. Test de connectivité SMTP
echo ""
echo "🌐 3. Test de connectivité"
echo "--------------------------"

print_info "Test de connectivité vers Gmail SMTP..."

# Test port 25 (SMTP)
timeout 5 bash -c "</dev/tcp/smtp.gmail.com/25" 2>/dev/null
if [ $? -eq 0 ]; then
    print_status 0 "Port 25 (smtp.gmail.com) accessible"
else
    print_status 1 "Port 25 (smtp.gmail.com) bloqué/inaccessible"
fi

# Test port 587 (SMTP avec TLS)
timeout 5 bash -c "</dev/tcp/smtp.gmail.com/587" 2>/dev/null
if [ $? -eq 0 ]; then
    print_status 0 "Port 587 (smtp.gmail.com) accessible"
else
    print_status 1 "Port 587 (smtp.gmail.com) bloqué/inaccessible"
fi

# 4. Configuration DNS du serveur
echo ""
echo "🔍 4. Vérification DNS"
echo "----------------------"

print_info "Résolution DNS locale..."
nslookup atelierdelistaro.fr
echo ""
nslookup mail.atelierdelistaro.fr

# 5. Forcer la livraison des emails en queue
echo ""
echo "🚀 5. Tentative de livraison forcée"
echo "-----------------------------------"

print_info "Tentative de flush de la queue..."
sudo postfix flush

echo ""
echo "Queue après flush:"
mailq

# 6. Analyse détaillée des emails en queue
echo ""
echo "🔍 6. Analyse des emails en queue"
echo "---------------------------------"

# Lister les IDs des emails en queue
QUEUE_IDS=$(mailq | grep -E '^[A-F0-9]+' | awk '{print $1}' | sed 's/\*//')

if [ -n "$QUEUE_IDS" ]; then
    print_info "Emails en queue détectés:"
    
    for id in $QUEUE_IDS; do
        echo ""
        echo "📧 Email ID: $id"
        echo "-------------------"
        sudo postcat -vq $id 2>/dev/null || echo "Impossible de lire l'email $id"
    done
else
    print_status 0 "Aucun email en queue"
fi

# 7. Vérification configuration SMTP relay
echo ""
echo "📮 7. Configuration relais SMTP"
echo "-------------------------------"

relayhost=$(postconf -h relayhost)
if [ -z "$relayhost" ]; then
    print_warning "Aucun relayhost configuré"
    print_info "Recommandation: Configurer un relayhost pour améliorer la délivrabilité"
    
    echo ""
    echo "Options de configuration:"
    echo "1. Gmail SMTP: [smtp.gmail.com]:587"
    echo "2. OVH SMTP: [ssl0.ovh.net]:587"
    echo "3. Mailgun: [smtp.mailgun.org]:587"
    
    echo ""
    print_info "Pour configurer Gmail comme relayhost:"
    echo "sudo postconf -e 'relayhost = [smtp.gmail.com]:587'"
    echo "sudo postconf -e 'smtp_use_tls = yes'"
    echo "sudo postconf -e 'smtp_sasl_auth_enable = yes'"
    echo "sudo postfix reload"
    
else
    echo "Relayhost configuré: $relayhost"
fi

# 8. Recommandations
echo ""
echo "💡 8. Recommandations de correction"
echo "===================================="

print_info "Problèmes détectés et solutions:"

echo ""
echo "1. 🚨 EMAILS EN QUEUE - Solutions:"
echo "   a) Configurer un relayhost (Gmail/OVH/Mailgun)"
echo "   b) Configurer les enregistrements DNS (MX, A, SPF)"
echo "   c) Vérifier que les ports SMTP ne sont pas bloqués"

echo ""
echo "2. 📋 LOGS MANQUANTS - Solutions:"
echo "   sudo touch /var/log/mail.log"
echo "   sudo chmod 644 /var/log/mail.log"
echo "   sudo systemctl restart rsyslog"

echo ""
echo "3. 🌐 DNS - Configuration requise:"
echo "   MX:  atelierdelistaro.fr → [IP_PUBLIQUE]"
echo "   A:   mail.atelierdelistaro.fr → [IP_PUBLIQUE]"
echo "   SPF: v=spf1 ip4:[IP_PUBLIQUE] ~all"

echo ""
echo "4. 🔧 SOLUTION RAPIDE - Utiliser Gmail comme relayhost:"
echo "   sudo postconf -e 'relayhost = [smtp.gmail.com]:587'"
echo "   sudo postconf -e 'smtp_use_tls = yes'"
echo "   sudo postconf -e 'smtp_sasl_auth_enable = yes'"
echo "   # Puis configurer auth Gmail avec mot de passe d'application"

# 9. Test de correction automatique
echo ""
echo "🛠️ 9. Correction automatique (optionnel)"
echo "========================================="

read -p "Voulez-vous activer automatiquement le logging? (y/N): " activate_logging
if [[ $activate_logging =~ ^[Yy]$ ]]; then
    print_info "Activation du logging..."
    sudo touch /var/log/mail.log
    sudo chmod 644 /var/log/mail.log
    sudo chown syslog:adm /var/log/mail.log
    sudo systemctl restart rsyslog
    print_status 0 "Logging activé"
fi

echo ""
read -p "Voulez-vous vider la queue actuelle? (y/N): " flush_queue
if [[ $flush_queue =~ ^[Yy]$ ]]; then
    print_info "Vidage de la queue..."
    sudo postsuper -d ALL
    print_status 0 "Queue vidée"
fi

echo ""
echo "🎉 Dépannage terminé !"
echo "====================="
echo ""
print_info "Prochaines étapes recommandées:"
echo "1. Configurer un relayhost (Gmail recommandé)"
echo "2. Configurer les enregistrements DNS"
echo "3. Tester l'envoi d'email"
echo "4. Surveiller les logs: tail -f /var/log/mail.log"
