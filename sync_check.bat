@echo off
echo ====================================
echo    PROTOCOLE RASPBERRY PI - LISTARO
echo ====================================
echo.
echo ETAPE 1: Verification des fichiers locaux
echo.
echo [PRIORITE 1] admin_sections\products.php
if exist "admin_sections\products.php" (
    echo   ✅ Fichier trouve
) else (
    echo   ❌ Fichier manquant - CRITIQUE
)
echo.
echo [OPTIONNEL] test_multi_images.html
if exist "test_multi_images.html" (
    echo   ✅ Fichier trouve
) else (
    echo   ⚠️  Fichier manquant - sera cree automatiquement
)
echo.
echo [OPTIONNEL] admin_images.php
if exist "admin_images.php" (
    echo   ✅ Fichier trouve
) else (
    echo   ⚠️  Fichier manquant - optionnel
)
echo.
echo ====================================
echo    INSTALLATION GIT (PREMIÈRE FOIS)
echo ====================================
echo.
echo ETAPE 2a: Installation Git sur Raspberry Pi
echo   Commande: scp install_git_raspi.sh admin@192.168.1.95:/tmp/
echo   Commande: ssh admin@192.168.1.95
echo   Commande: sudo bash /tmp/install_git_raspi.sh
echo.
echo ====================================
echo    PROTOCOLE DE DEPLOIEMENT
echo ====================================
echo.
echo ETAPE 2: Transfert via SCP
echo   Commande: scp -r admin_sections\products.php admin@192.168.1.95:/tmp/
echo   Commande: scp test_multi_images.html admin@192.168.1.95:/tmp/
echo   Commande: scp admin_images.php admin@192.168.1.95:/tmp/
echo.
echo ETAPE 3: Connexion SSH au Raspberry Pi
echo   Commande: ssh admin@192.168.1.95
echo.
echo ETAPE 4: Deploiement sur le Raspberry Pi
echo   sudo cp /tmp/products.php /var/www/html/atelier_de_listaro/admin_sections/
echo   sudo cp /tmp/test_multi_images.html /var/www/html/atelier_de_listaro/
echo   sudo cp /tmp/admin_images.php /var/www/html/atelier_de_listaro/
echo   sudo chown -R www-data:www-data /var/www/html/atelier_de_listaro/
echo   sudo chmod -R 644 /var/www/html/atelier_de_listaro/*.php
echo.
echo ====================================
echo    INFORMATIONS DE CONNEXION
echo ====================================
echo Raspberry Pi IP: 192.168.1.95
echo Utilisateur: admin
echo Destination: /var/www/html/atelier_de_listaro/
echo.
echo ====================================
echo    TESTS APRES DEPLOIEMENT
echo ====================================
echo Panel admin local: http://192.168.1.95/atelier_de_listaro/admin_panel.php
echo Panel admin public: http://88.124.91.246/admin_panel.php
echo Page test: http://192.168.1.95/atelier_de_listaro/test_multi_images.html
echo.
echo ====================================
echo    VERIFICATION RAPIDE
echo ====================================
echo 1. Connectez-vous au panel admin
echo 2. Cliquez sur "Produits"  
echo 3. Cliquez sur "Ajouter un produit"
echo 4. Verifiez le bouton: "Selectionner plusieurs images (Ctrl+clic)"
echo.
pause
