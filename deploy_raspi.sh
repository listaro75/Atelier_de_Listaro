#!/bin/bash
# =====================================================
#     PROTOCOLE DE DEPLOIEMENT - RASPBERRY PI
#     Synchronisation vers /var/www/html
# =====================================================

clear
echo "🍓 ========================================"
echo "   DEPLOIEMENT SUR RASPBERRY PI"
echo "   Destination: /var/www/html/atelier_de_listaro"
echo "========================================"
echo

# Configuration
REPO_LOCAL="/tmp/atelier_listaro_update"
WEB_DIR="/var/www/html/atelier_de_listaro"
BACKUP_DIR="/home/admin/backups/$(date +%Y%m%d_%H%M%S)"

# Vérification des permissions
echo "🔐 Vérification des permissions..."
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté avec sudo"
    echo "   Relancez avec: sudo bash $0"
    exit 1
fi
echo "✅ Permissions OK"
echo

# Créer le dossier de sauvegarde
echo "💾 Création de la sauvegarde..."
mkdir -p "$BACKUP_DIR"
if [ -d "$WEB_DIR" ]; then
    cp -r "$WEB_DIR" "$BACKUP_DIR/"
    echo "✅ Sauvegarde créée dans: $BACKUP_DIR"
else
    echo "⚠️  Dossier web non trouvé, création..."
    mkdir -p "$WEB_DIR"
fi
echo

# Fonction pour afficher le statut des fichiers
check_files() {
    echo "📋 STATUT DES FICHIERS À DÉPLOYER:"
    echo "----------------------------------------"
    
    # Fichier principal (products.php)
    if [ -f "$REPO_LOCAL/admin_sections/products.php" ]; then
        echo "✅ admin_sections/products.php - Prêt"
    else
        echo "❌ admin_sections/products.php - MANQUANT"
    fi
    
    # Fichier de test
    if [ -f "$REPO_LOCAL/test_multi_images.html" ]; then
        echo "✅ test_multi_images.html - Prêt"
    else
        echo "⚠️  test_multi_images.html - Optionnel"
    fi
    
    # Fichier admin images
    if [ -f "$REPO_LOCAL/admin_images.php" ]; then
        echo "✅ admin_images.php - Prêt"
    else
        echo "⚠️  admin_images.php - Optionnel"
    fi
    
    echo "----------------------------------------"
    echo
}

# Fonction de déploiement
deploy_files() {
    echo "🚀 DÉPLOIEMENT EN COURS..."
    echo "----------------------------------------"
    
    # Déployer products.php (PRIORITÉ 1)
    if [ -f "$REPO_LOCAL/admin_sections/products.php" ]; then
        echo "📄 Déploiement de admin_sections/products.php..."
        mkdir -p "$WEB_DIR/admin_sections"
        cp "$REPO_LOCAL/admin_sections/products.php" "$WEB_DIR/admin_sections/"
        chown www-data:www-data "$WEB_DIR/admin_sections/products.php"
        chmod 644 "$WEB_DIR/admin_sections/products.php"
        echo "   ✅ products.php déployé"
    else
        echo "   ❌ products.php manquant - CRITIQUE"
        return 1
    fi
    
    # Déployer test_multi_images.html
    if [ -f "$REPO_LOCAL/test_multi_images.html" ]; then
        echo "📄 Déploiement de test_multi_images.html..."
        cp "$REPO_LOCAL/test_multi_images.html" "$WEB_DIR/"
        chown www-data:www-data "$WEB_DIR/test_multi_images.html"
        chmod 644 "$WEB_DIR/test_multi_images.html"
        echo "   ✅ test_multi_images.html déployé"
    fi
    
    # Déployer admin_images.php
    if [ -f "$REPO_LOCAL/admin_images.php" ]; then
        echo "📄 Déploiement de admin_images.php..."
        cp "$REPO_LOCAL/admin_images.php" "$WEB_DIR/"
        chown www-data:www-data "$WEB_DIR/admin_images.php"
        chmod 644 "$WEB_DIR/admin_images.php"
        echo "   ✅ admin_images.php déployé"
    fi
    
    echo "----------------------------------------"
    echo "🎉 DÉPLOIEMENT TERMINÉ AVEC SUCCÈS!"
    echo
}

# Fonction de test
test_deployment() {
    echo "🧪 TESTS POST-DÉPLOIEMENT:"
    echo "----------------------------------------"
    
    # Test Apache
    if systemctl is-active --quiet apache2; then
        echo "✅ Apache2 actif"
    else
        echo "❌ Apache2 inactif - Redémarrage..."
        systemctl restart apache2
        sleep 2
        if systemctl is-active --quiet apache2; then
            echo "✅ Apache2 redémarré avec succès"
        else
            echo "❌ Problème avec Apache2"
        fi
    fi
    
    # Test des fichiers
    if [ -f "$WEB_DIR/admin_sections/products.php" ]; then
        echo "✅ products.php accessible"
    else
        echo "❌ products.php non trouvé"
    fi
    
    # Test des permissions
    if [ "$(stat -c %U "$WEB_DIR/admin_sections/products.php" 2>/dev/null)" = "www-data" ]; then
        echo "✅ Permissions correctes"
    else
        echo "⚠️  Permissions à vérifier"
    fi
    
    echo "----------------------------------------"
    echo
}

# Affichage des URLs de test
show_urls() {
    echo "🌐 URLS DE TEST:"
    echo "----------------------------------------"
    echo "🏠 Site principal:"
    echo "   http://$(hostname -I | awk '{print $1}')/atelier_de_listaro/"
    echo "   http://88.124.91.246/atelier_de_listaro/"
    echo
    echo "👨‍💼 Panel d'administration:"
    echo "   http://$(hostname -I | awk '{print $1}')/atelier_de_listaro/admin_panel.php"
    echo "   http://88.124.91.246/atelier_de_listaro/admin_panel.php"
    echo
    echo "🧪 Page de test images multiples:"
    echo "   http://$(hostname -I | awk '{print $1}')/atelier_de_listaro/test_multi_images.html"
    echo "   http://88.124.91.246/atelier_de_listaro/test_multi_images.html"
    echo "----------------------------------------"
    echo
}

# Menu principal
show_menu() {
    echo "📋 ACTIONS DISPONIBLES:"
    echo "----------------------------------------"
    echo "1) Vérifier les fichiers à déployer"
    echo "2) Déployer les fichiers"
    echo "3) Tester le déploiement"
    echo "4) Afficher les URLs de test"
    echo "5) Déploiement complet (1+2+3+4)"
    echo "6) Restaurer la sauvegarde"
    echo "7) Quitter"
    echo "----------------------------------------"
    echo
}

# Fonction de restauration
restore_backup() {
    echo "🔄 RESTAURATION DE LA SAUVEGARDE:"
    echo "----------------------------------------"
    
    # Lister les sauvegardes disponibles
    echo "Sauvegardes disponibles:"
    ls -la /home/admin/backups/ 2>/dev/null | grep "^d" | awk '{print $9}' | grep -v "^\.$\|^\.\.$" | nl
    
    echo
    read -p "Entrez le numéro de la sauvegarde à restaurer (ou 'q' pour annuler): " choice
    
    if [ "$choice" = "q" ]; then
        echo "Restauration annulée"
        return
    fi
    
    backup_folder=$(ls -la /home/admin/backups/ 2>/dev/null | grep "^d" | awk '{print $9}' | grep -v "^\.$\|^\.\.$" | sed -n "${choice}p")
    
    if [ -n "$backup_folder" ] && [ -d "/home/admin/backups/$backup_folder" ]; then
        echo "Restauration de la sauvegarde: $backup_folder"
        cp -r "/home/admin/backups/$backup_folder/atelier_de_listaro/"* "$WEB_DIR/"
        chown -R www-data:www-data "$WEB_DIR"
        echo "✅ Restauration terminée"
    else
        echo "❌ Sauvegarde non trouvée"
    fi
    echo
}

# Boucle principale
while true; do
    show_menu
    read -p "Choisissez une action (1-7): " choice
    
    case $choice in
        1)
            check_files
            ;;
        2)
            deploy_files
            ;;
        3)
            test_deployment
            ;;
        4)
            show_urls
            ;;
        5)
            echo "🚀 DÉPLOIEMENT COMPLET EN COURS..."
            echo
            check_files
            deploy_files
            test_deployment
            show_urls
            echo "🎯 Déploiement complet terminé!"
            echo
            ;;
        6)
            restore_backup
            ;;
        7)
            echo "👋 Au revoir!"
            exit 0
            ;;
        *)
            echo "❌ Option invalide. Choisissez entre 1 et 7."
            ;;
    esac
    
    read -p "Appuyez sur Entrée pour continuer..."
    clear
done
