#!/bin/bash
# Script de changement de hostname pour Raspberry Pi
# Atelier de Listaro

NEW_HOSTNAME="${1:-atelierlistaro}"
OLD_HOSTNAME=$(hostname)

echo "🖥️ Changement de hostname pour Atelier de Listaro"
echo "================================================="
echo "Hostname actuel : $OLD_HOSTNAME"
echo "Nouveau hostname : $NEW_HOSTNAME"
echo ""

# Vérifier si on est root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté en tant que root (sudo)"
    exit 1
fi

# Sauvegarder la configuration actuelle
echo "💾 Sauvegarde de la configuration actuelle..."
cp /etc/hostname /etc/hostname.backup.$(date +%Y%m%d)
cp /etc/hosts /etc/hosts.backup.$(date +%Y%m%d)

# Changer le hostname système
echo "🔧 Modification du hostname système..."
hostnamectl set-hostname "$NEW_HOSTNAME"

# Modifier /etc/hostname
echo "$NEW_HOSTNAME" > /etc/hostname

# Modifier /etc/hosts
echo "📝 Modification de /etc/hosts..."
sed -i "s/127.0.1.1.*$OLD_HOSTNAME.*/127.0.1.1\t$NEW_HOSTNAME/" /etc/hosts

# Ajouter une entrée si elle n'existe pas
if ! grep -q "127.0.1.1" /etc/hosts; then
    echo "127.0.1.1	$NEW_HOSTNAME" >> /etc/hosts
fi

# Vérifier la configuration Postfix si elle existe
if systemctl is-active --quiet postfix; then
    echo "📧 Mise à jour de la configuration Postfix..."
    
    # Sauvegarder la config Postfix
    cp /etc/postfix/main.cf /etc/postfix/main.cf.backup.$(date +%Y%m%d)
    
    # Mettre à jour myhostname dans Postfix
    if grep -q "^myhostname" /etc/postfix/main.cf; then
        sed -i "s/^myhostname.*/myhostname = $NEW_HOSTNAME/" /etc/postfix/main.cf
    else
        echo "myhostname = $NEW_HOSTNAME" >> /etc/postfix/main.cf
    fi
    
    echo "✅ Configuration Postfix mise à jour"
fi

# Afficher le résumé
echo ""
echo "✅ CHANGEMENT DE HOSTNAME TERMINÉ"
echo "================================="
echo "Ancien hostname : $OLD_HOSTNAME"
echo "Nouveau hostname : $NEW_HOSTNAME"
echo ""
echo "📁 Sauvegardes créées :"
echo "• /etc/hostname.backup.$(date +%Y%m%d)"
echo "• /etc/hosts.backup.$(date +%Y%m%d)"
if [ -f "/etc/postfix/main.cf.backup.$(date +%Y%m%d)" ]; then
    echo "• /etc/postfix/main.cf.backup.$(date +%Y%m%d)"
fi
echo ""
echo "⚠️  REDÉMARRAGE REQUIS"
echo "====================="
echo "Pour appliquer complètement les changements :"
echo "sudo reboot"
echo ""
echo "Après redémarrage, votre machine s'appellera : $NEW_HOSTNAME"
echo ""

# Proposer le redémarrage
read -p "Voulez-vous redémarrer maintenant ? (y/N) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🔄 Redémarrage en cours..."
    sleep 2
    reboot
else
    echo "ℹ️  Redémarrez manuellement avec : sudo reboot"
fi
