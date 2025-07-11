@echo off
echo 🐙 =====================================
echo     PROTOCOLE GIT - RASPBERRY PI
echo     Workflow avec versioning
echo =====================================
echo.

echo ⚡ INSTALLATION INITIALE (une seule fois)
echo ========================================
echo.
echo 1. Transférer le script d'installation:
echo    scp install_git_raspi.sh admin@192.168.1.95:/tmp/
echo.
echo 2. Se connecter au Raspberry Pi:
echo    ssh admin@192.168.1.95
echo.
echo 3. Installer Git:
echo    sudo bash /tmp/install_git_raspi.sh
echo.
echo    ✅ Git sera installé et configuré
echo    ✅ Dépôt initialisé dans /var/www/html/atelier_de_listaro/
echo    ✅ Scripts utiles créés (deploy.sh, backup.sh)
echo.

echo 🔄 WORKFLOW DE DÉVELOPPEMENT QUOTIDIEN
echo ====================================
echo.
echo Option A: MISE À JOUR SIMPLE (fichiers modifiés)
echo -------------------------------------------------
echo 1. Transférer les fichiers modifiés:
echo    scp admin_sections\products.php admin@192.168.1.95:/tmp/
echo    scp test_multi_images.html admin@192.168.1.95:/tmp/
echo.
echo 2. Sur le Raspberry Pi:
echo    ssh admin@192.168.1.95
echo    cd /var/www/html/atelier_de_listaro
echo    sudo cp /tmp/products.php admin_sections/
echo    sudo cp /tmp/test_multi_images.html .
echo    sudo chown -R www-data:www-data .
echo    ./deploy.sh
echo.

echo Option B: SYNCHRONISATION COMPLÈTE (recommandé)
echo -----------------------------------------------
echo 1. Créer une archive locale:
echo    git archive --format=zip --output=atelier_listaro.zip HEAD
echo.
echo 2. Transférer l'archive:
echo    scp atelier_listaro.zip admin@192.168.1.95:/tmp/
echo.
echo 3. Sur le Raspberry Pi:
echo    ssh admin@192.168.1.95
echo    cd /var/www/html/
echo    sudo ./backup.sh
echo    sudo unzip -o /tmp/atelier_listaro.zip -d atelier_de_listaro/
echo    sudo chown -R www-data:www-data atelier_de_listaro/
echo    cd atelier_de_listaro
echo    git add .
echo    git commit -m "Synchronisation depuis développement local"
echo.

echo 💾 COMMANDES GIT UTILES
echo =======================
echo.
echo Sur le Raspberry Pi (/var/www/html/atelier_de_listaro/):
echo.
echo   git status                    # Voir les modifications
echo   git log --oneline            # Historique des commits
echo   git diff                     # Voir les changements
echo   ./deploy.sh                  # Déploiement rapide
echo   ./backup.sh                  # Sauvegarde
echo.
echo   git branch                   # Voir les branches
echo   git checkout development     # Basculer en développement
echo   git checkout main           # Basculer en production
echo.

echo 🌐 TESTS APRÈS DÉPLOIEMENT
echo ==========================
echo.
echo Panel admin: http://88.124.91.246/admin_panel.php
echo Test images: http://88.124.91.246/test_multi_images.html
echo.
echo 🔍 Vérifications:
echo 1. Bouton "Sélectionner plusieurs images" présent
echo 2. Fonction Ctrl+clic operative
echo 3. Prévisualisation des images
echo 4. Limitation à 5 images
echo.

echo 🚨 DÉPANNAGE
echo =============
echo.
echo Si Git n'est pas encore installé:
echo   sudo apt update
echo   sudo apt install -y git
echo.
echo Si problème de permissions:
echo   sudo chown -R www-data:www-data /var/www/html/atelier_de_listaro
echo   sudo chgrp -R admin /var/www/html/atelier_de_listaro/.git
echo   sudo chmod -R g+w /var/www/html/atelier_de_listaro/.git
echo.
echo Si le site ne fonctionne pas:
echo   sudo systemctl restart apache2
echo   sudo tail -f /var/log/apache2/error.log
echo.

echo 📋 RÉCAPITULATIF RAPIDE
echo =======================
echo.
echo PREMIÈRE INSTALLATION:
echo 1. scp install_git_raspi.sh admin@192.168.1.95:/tmp/
echo 2. ssh admin@192.168.1.95
echo 3. sudo bash /tmp/install_git_raspi.sh
echo.
echo MISE À JOUR QUOTIDIENNE:
echo 1. scp fichier_modifie.php admin@192.168.1.95:/tmp/
echo 2. ssh admin@192.168.1.95
echo 3. cd /var/www/html/atelier_de_listaro
echo 4. sudo cp /tmp/fichier_modifie.php destination/
echo 5. ./deploy.sh
echo.
pause
