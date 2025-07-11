# Configuration sudo pour permettre le redémarrage depuis l'admin panel
# Instructions pour Raspberry Pi

## 1. Pour permettre le redémarrage des services web :

# Ouvrir le fichier sudoers
sudo visudo

# Ajouter ces lignes à la fin du fichier :
# Permettre à www-data de redémarrer les services web sans mot de passe
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart apache2
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart nginx
www-data ALL=(ALL) NOPASSWD: /bin/systemctl restart php*-fpm
www-data ALL=(ALL) NOPASSWD: /bin/pkill -HUP apache2
www-data ALL=(ALL) NOPASSWD: /bin/pkill -HUP nginx

## 2. Pour permettre le redémarrage complet du système (OPTIONNEL - À utiliser avec PRUDENCE) :
www-data ALL=(ALL) NOPASSWD: /sbin/shutdown -r +1

## 3. Rendre le script exécutable :
chmod +x restart_server.sh
sudo chown www-data:www-data restart_server.sh

## 4. Test des permissions :
# Tester en tant que www-data :
sudo -u www-data sudo systemctl status apache2

## 5. Alternative plus sécurisée - Service systemd personnalisé :

# Créer un service pour le redémarrage
sudo nano /etc/systemd/system/web-restart.service

# Contenu du service :
[Unit]
Description=Web Server Restart Service
After=network.target

[Service]
Type=oneshot
ExecStart=/bin/systemctl restart apache2
ExecStart=/bin/systemctl restart php7.4-fpm
User=root

[Install]
WantedBy=multi-user.target

# Activer le service
sudo systemctl daemon-reload
sudo systemctl enable web-restart.service

# Puis autoriser www-data à déclencher ce service :
www-data ALL=(ALL) NOPASSWD: /bin/systemctl start web-restart.service

## Sécurité :
- Ces permissions permettent uniquement le redémarrage des services web
- Pas d'accès shell complet
- Actions loggées dans /var/log/auth.log
- Recommandé de limiter ces permissions au strict nécessaire

## Alternative sans sudo :
Si vous ne voulez pas donner de permissions sudo, le panel affichera simplement
un message informatif sans exécuter le redémarrage.
