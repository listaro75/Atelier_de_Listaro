#!/bin/bash
# Script de redémarrage sécurisé pour Raspberry Pi
# Fichier: restart_server.sh

echo "🔄 Redémarrage du serveur demandé..."
echo "Timestamp: $(date)"

# Log de l'action
echo "$(date): Server restart requested via admin panel" >> /var/log/admin_actions.log

# Attendre 5 secondes pour permettre à la réponse HTTP d'être envoyée
sleep 5

# Redémarrer les services web
if systemctl is-active --quiet apache2; then
    echo "Redémarrage d'Apache2..."
    sudo systemctl restart apache2
    echo "Apache2 redémarré"
elif systemctl is-active --quiet nginx; then
    echo "Redémarrage de Nginx..."
    sudo systemctl restart nginx
    echo "Nginx redémarré"
fi

# Redémarrer PHP-FPM si présent
if systemctl is-active --quiet php*-fpm; then
    echo "Redémarrage de PHP-FPM..."
    sudo systemctl restart php*-fpm
    echo "PHP-FPM redémarré"
fi

echo "✅ Redémarrage des services terminé"
