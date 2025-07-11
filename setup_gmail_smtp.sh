#!/bin/bash

# =============================================================================
# Configuration Gmail SMTP pour Postfix - Solution rapide
# =============================================================================

echo "📧 Configuration Gmail SMTP - Atelier de Listaro"
echo "================================================"
echo ""

# Couleurs
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

function print_info() {
    echo -e "${BLUE}ℹ️ $1${NC}"
}

function print_warning() {
    echo -e "${YELLOW}⚠️ $1${NC}"
}

function print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Vérification des droits
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

print_info "Ce script va configurer Gmail comme relayhost pour Postfix"
print_warning "Vous devez avoir un compte Gmail et créer un mot de passe d'application"

echo ""
echo "📋 Prérequis:"
echo "1. Compte Gmail actif"
echo "2. Authentification à 2 facteurs activée"
echo "3. Mot de passe d'application généré"
echo ""
echo "🔗 Pour créer un mot de passe d'application:"
echo "   https://support.google.com/accounts/answer/185833"
echo ""

read -p "Avez-vous un mot de passe d'application Gmail? (y/N): " has_app_password
if [[ ! $has_app_password =~ ^[Yy]$ ]]; then
    echo ""
    print_warning "Créez d'abord un mot de passe d'application Gmail puis relancez ce script"
    exit 1
fi

# Collecte des informations
echo ""
print_info "Configuration Gmail SMTP"
echo ""
read -p "Votre email Gmail: " gmail_email
read -p "Mot de passe d'application Gmail: " -s gmail_password
echo ""

if [ -z "$gmail_email" ] || [ -z "$gmail_password" ]; then
    echo "❌ Email et mot de passe requis"
    exit 1
fi

# Sauvegarde de la configuration actuelle
print_info "Sauvegarde de la configuration Postfix actuelle..."
cp /etc/postfix/main.cf /etc/postfix/main.cf.backup.$(date +%Y%m%d_%H%M%S)

# Configuration Postfix pour Gmail
print_info "Configuration de Postfix pour Gmail..."

# Configuration relayhost et TLS
postconf -e "relayhost = [smtp.gmail.com]:587"
postconf -e "smtp_use_tls = yes"
postconf -e "smtp_sasl_auth_enable = yes"
postconf -e "smtp_sasl_security_options = noanonymous"
postconf -e "smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd"
postconf -e "smtp_tls_security_level = encrypt"
postconf -e "smtp_tls_note_starttls_offer = yes"
postconf -e "smtp_tls_CAfile = /etc/ssl/certs/ca-certificates.crt"

# Création du fichier d'authentification
print_info "Configuration de l'authentification Gmail..."
cat > /etc/postfix/sasl_passwd << EOF
[smtp.gmail.com]:587 $gmail_email:$gmail_password
EOF

# Sécurisation du fichier de mots de passe
chmod 600 /etc/postfix/sasl_passwd
chown root:root /etc/postfix/sasl_passwd

# Génération de la base de données hash
postmap /etc/postfix/sasl_passwd

# Installation des packages nécessaires si manquants
print_info "Vérification des dépendances..."
apt update -qq
apt install -y libsasl2-modules ca-certificates

# Test de la configuration
print_info "Test de la configuration..."
postfix check
if [ $? -eq 0 ]; then
    print_success "Configuration Postfix valide"
else
    echo "❌ Erreurs dans la configuration Postfix"
    exit 1
fi

# Redémarrage de Postfix
print_info "Redémarrage de Postfix..."
systemctl reload postfix
sleep 2

if systemctl is-active --quiet postfix; then
    print_success "Postfix redémarré avec succès"
else
    echo "❌ Erreur lors du redémarrage de Postfix"
    exit 1
fi

# Vider la queue actuelle
print_info "Tentative de livraison des emails en queue..."
postfix flush

# Test d'envoi
echo ""
print_info "Test d'envoi d'email..."
read -p "Email de test (appuyez sur Entrée pour $gmail_email): " test_email
test_email=${test_email:-$gmail_email}

# Envoi d'un email de test
cat > /tmp/test_email.txt << EOF
Subject: Test Gmail SMTP - Atelier de Listaro
From: noreply@atelierdelistaro.fr
To: $test_email

Félicitations !

Votre configuration Gmail SMTP fonctionne correctement.

Configuration:
- Serveur: $(hostname)
- Date: $(date)
- Relayhost: Gmail SMTP
- Email: $gmail_email

Cordialement,
Atelier de Listaro
EOF

sendmail -t < /tmp/test_email.txt
rm /tmp/test_email.txt

print_success "Email de test envoyé"

# Affichage de la queue
echo ""
print_info "État de la queue après configuration:"
mailq

# Instructions finales
echo ""
echo "🎉 Configuration Gmail SMTP terminée !"
echo "====================================="
echo ""
print_success "Configuration appliquée:"
echo "  • Relayhost: [smtp.gmail.com]:587"
echo "  • TLS: Activé"
echo "  • Authentification: $gmail_email"
echo ""
print_info "Surveillance:"
echo "  • Queue: mailq"
echo "  • Logs: tail -f /var/log/mail.log"
echo "  • Status: systemctl status postfix"
echo ""
print_info "Sécurité:"
echo "  • Fichier de mots de passe sécurisé: /etc/postfix/sasl_passwd"
echo "  • Sauvegarde config: /etc/postfix/main.cf.backup.*"
echo ""
print_warning "Important:"
echo "  • Gardez votre mot de passe d'application Gmail secret"
echo "  • Surveillez les logs pour vérifier la livraison"
echo "  • Les emails devraient maintenant être livrés via Gmail"
echo ""

# Test de vérification
echo "🧪 Tests recommandés:"
echo "1. Vérifiez la queue: mailq"
echo "2. Testez l'envoi: echo 'Test' | mail -s 'Test' $test_email"
echo "3. Surveillez les logs: tail -f /var/log/mail.log"
