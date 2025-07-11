#!/bin/bash
# Script de récupération réseau pour Raspberry Pi
# Atelier de Listaro - Diagnostic et réparation

echo "🚨 Script de récupération réseau - Atelier de Listaro"
echo "===================================================="

# Fonction de log
log_action() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a /var/log/network_recovery.log
}

log_action "🔍 Début du diagnostic réseau"

# Vérifier si on est root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

echo "📊 ÉTAT ACTUEL DU SYSTÈME"
echo "========================="

# IP locale
echo "🔍 Adresses IP locales :"
ip addr show | grep "inet " | grep -v "127.0.0.1"

# IP publique
echo ""
echo "🌐 IP publique actuelle :"
PUBLIC_IP=$(curl -s --connect-timeout 10 ifconfig.me 2>/dev/null || echo "Non accessible")
echo "IP publique : $PUBLIC_IP"
log_action "IP publique détectée : $PUBLIC_IP"

# État des services réseau
echo ""
echo "⚙️ ÉTAT DES SERVICES"
echo "==================="

services=("networking" "apache2" "ssh" "postfix")
for service in "${services[@]}"; do
    status=$(systemctl is-active $service 2>/dev/null)
    if [ "$status" = "active" ]; then
        echo "✅ $service : Actif"
    else
        echo "❌ $service : $status"
        log_action "Service $service non actif : $status"
    fi
done

# Test des ports
echo ""
echo "🔌 PORTS RÉSEAU"
echo "==============="
netstat -tlnp 2>/dev/null | grep -E ":80|:443|:22" | head -10

# Diagnostic réseau avancé
echo ""
echo "🌐 DIAGNOSTIC RÉSEAU AVANCÉ"
echo "==========================="

# Test de connectivité internet
echo "🧪 Test de connectivité internet :"
if ping -c 3 8.8.8.8 >/dev/null 2>&1; then
    echo "✅ Internet accessible"
else
    echo "❌ Pas d'accès internet"
    log_action "ALERTE : Pas d'accès internet"
fi

# Test DNS
echo ""
echo "🧪 Test de résolution DNS :"
if nslookup google.com >/dev/null 2>&1; then
    echo "✅ DNS fonctionnel"
else
    echo "❌ Problème DNS"
    log_action "ALERTE : Problème DNS"
fi

# RÉPARATIONS AUTOMATIQUES
echo ""
echo "🛠️ RÉPARATIONS AUTOMATIQUES"
echo "==========================="

# Redémarrer les services réseau si nécessaire
if ! systemctl is-active --quiet networking; then
    echo "🔄 Redémarrage du service networking..."
    systemctl restart networking
    sleep 5
    log_action "Service networking redémarré"
fi

# Redémarrer Apache si nécessaire
if ! systemctl is-active --quiet apache2; then
    echo "🔄 Redémarrage d'Apache..."
    systemctl restart apache2
    sleep 3
    log_action "Apache redémarré"
fi

# Configuration du firewall
echo ""
echo "🔥 CONFIGURATION FIREWALL"
echo "========================="

# Vérifier si ufw est actif
if systemctl is-active --quiet ufw; then
    echo "🔍 UFW actif - Vérification des règles..."
    ufw status numbered
    
    # S'assurer que les ports essentiels sont ouverts
    ufw allow 22/tcp comment 'SSH'
    ufw allow 80/tcp comment 'HTTP'
    ufw allow 443/tcp comment 'HTTPS'
    
    echo "✅ Règles firewall mises à jour"
    log_action "Règles firewall vérifiées"
else
    echo "ℹ️ UFW non actif"
fi

# TESTS DE CONNECTIVITÉ
echo ""
echo "🧪 TESTS DE CONNECTIVITÉ POST-RÉPARATION"
echo "========================================"

# Re-test de l'IP publique
echo "🌐 Nouvelle IP publique :"
NEW_PUBLIC_IP=$(curl -s --connect-timeout 10 ifconfig.me 2>/dev/null || echo "Toujours non accessible")
echo "IP publique : $NEW_PUBLIC_IP"

if [ "$NEW_PUBLIC_IP" != "Non accessible" ] && [ "$NEW_PUBLIC_IP" != "Toujours non accessible" ]; then
    echo "✅ Connexion internet rétablie"
    log_action "Connexion rétablie - Nouvelle IP : $NEW_PUBLIC_IP"
    
    # Test du serveur web
    echo ""
    echo "🌐 Test du serveur web local :"
    if curl -s --connect-timeout 5 http://localhost >/dev/null; then
        echo "✅ Serveur web local accessible"
    else
        echo "❌ Serveur web local non accessible"
    fi
    
else
    echo "❌ Connexion internet toujours problématique"
    log_action "ÉCHEC : Connexion non rétablie"
fi

# INFORMATIONS DE RÉCUPÉRATION
echo ""
echo "📋 INFORMATIONS DE RÉCUPÉRATION"
echo "==============================="

echo "🏠 Accès local possible via :"
ip addr show | grep "inet " | grep -v "127.0.0.1" | while read line; do
    ip=$(echo $line | awk '{print $2}' | cut -d'/' -f1)
    echo "   • http://$ip"
done

echo ""
echo "🔗 URLs de test :"
echo "   • http://mail.local (si mDNS fonctionne)"
echo "   • http://192.168.1.100 (IP courante possible)"
echo "   • http://192.168.0.100 (réseau alternatif)"

# RECOMMANDATIONS
echo ""
echo "💡 RECOMMANDATIONS"
echo "=================="
echo ""
echo "Si le problème persiste :"
echo "1. 🔌 Vérifiez les connexions physiques (câbles, alimentation)"
echo "2. 📶 Redémarrez votre box internet"
echo "3. 🔄 Redémarrez le Raspberry Pi : sudo reboot"
echo "4. 📞 Contactez votre FAI si l'IP a changé"
echo "5. 🌐 Configurez un service DynDNS pour éviter ce problème"
echo ""

# CONFIGURATION DYNDNS
echo "🌐 CONFIGURATION DYNDNS RECOMMANDÉE"
echo "==================================="
echo ""
echo "Pour éviter ce problème à l'avenir, installez un client DynDNS :"
echo ""
echo "# Installation DuckDNS (gratuit)"
echo "mkdir -p /opt/duckdns"
echo "cd /opt/duckdns"
echo "curl -s 'https://www.duckdns.org/update?domains=VOTRE-DOMAINE&token=VOTRE-TOKEN&ip=' > /dev/null"
echo ""
echo "# Automatisation (crontab)"
echo "echo '*/5 * * * * /opt/duckdns/duck.sh >/dev/null 2>&1' | crontab -"
echo ""

# SAUVEGARDE D'URGENCE
echo "💾 SAUVEGARDE D'URGENCE"
echo "======================"
echo ""
echo "Sauvegardez vos données importantes :"
echo "• Base de données : mysqldump -u root -p atelier_de_listaro > backup.sql"
echo "• Site web : tar -czf site_backup.tar.gz /var/www/html/"
echo "• Configuration : cp -r /etc/apache2/sites-available/ ~/apache_backup/"
echo ""

log_action "Script de récupération terminé"

echo "✅ DIAGNOSTIC TERMINÉ"
echo "===================="
echo "Consultez le log complet : /var/log/network_recovery.log"
echo ""

# Proposer un redémarrage si nécessaire
read -p "Voulez-vous redémarrer le système maintenant ? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    log_action "Redémarrage système demandé par l'utilisateur"
    echo "🔄 Redémarrage en cours..."
    sleep 3
    reboot
fi
