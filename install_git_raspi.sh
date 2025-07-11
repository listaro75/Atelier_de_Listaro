#!/bin/bash
# =====================================================
#     INSTALLATION ET CONFIGURATION GIT - RASPBERRY PI
#     Pour Atelier de Listaro
# =====================================================

clear
echo "🐙 ========================================"
echo "   INSTALLATION GIT - RASPBERRY PI"
echo "   Configuration pour développement web"
echo "========================================"
echo

# Vérification des permissions
if [ "$EUID" -ne 0 ]; then
    echo "❌ Ce script doit être exécuté avec sudo"
    echo "   Relancez avec: sudo bash $0"
    exit 1
fi

# Variables
REPO_DIR="/var/www/html/atelier_de_listaro"
GIT_USER="admin"
BACKUP_DIR="/home/admin/backups/git_install_$(date +%Y%m%d_%H%M%S)"

echo "🔧 ÉTAPE 1: Mise à jour du système"
echo "----------------------------------------"
apt update
echo "✅ Mise à jour des paquets terminée"
echo

echo "🐙 ÉTAPE 2: Installation de Git"
echo "----------------------------------------"
apt install -y git
echo "✅ Git installé"
echo

echo "📋 ÉTAPE 3: Vérification de l'installation"
echo "----------------------------------------"
git --version
echo "✅ Version de Git affichée"
echo

echo "💾 ÉTAPE 4: Sauvegarde du site actuel"
echo "----------------------------------------"
mkdir -p "$BACKUP_DIR"
if [ -d "$REPO_DIR" ]; then
    cp -r "$REPO_DIR" "$BACKUP_DIR/"
    echo "✅ Sauvegarde créée dans: $BACKUP_DIR"
else
    echo "⚠️  Aucun site existant à sauvegarder"
fi
echo

echo "📁 ÉTAPE 5: Préparation du dossier Git"
echo "----------------------------------------"
# Créer le dossier s'il n'existe pas
mkdir -p "$REPO_DIR"
cd "$REPO_DIR"

# Initialiser le dépôt Git
echo "🏁 Initialisation du dépôt Git..."
git init
echo "✅ Dépôt Git initialisé"
echo

echo "👤 ÉTAPE 6: Configuration Git"
echo "----------------------------------------"
echo "Configuration de l'utilisateur Git..."

# Configuration globale
git config --global init.defaultBranch main
git config --global user.name "Atelier de Listaro Admin"
git config --global user.email "admin@atelier-listaro.local"

# Configuration locale pour ce dépôt
git config user.name "Atelier de Listaro Admin"
git config user.email "admin@atelier-listaro.local"

echo "✅ Configuration Git terminée"
echo

echo "📄 ÉTAPE 7: Création des fichiers Git"
echo "----------------------------------------"

# Créer .gitignore
cat > .gitignore << 'EOF'
# Fichiers temporaires
*.tmp
*.log
*.bak
*~

# Fichiers de configuration sensibles
**/env.php
**/config_private.php
**/*password*
**/*secret*

# Cache et sessions
cache/
sessions/
tmp/

# Uploads utilisateurs (optionnel - à adapter selon vos besoins)
# uploads/

# Fichiers système
.DS_Store
Thumbs.db
*.swp
*.swo

# Dossiers de développement
node_modules/
vendor/
.vscode/
.idea/

# Sauvegardes
backups/
*.backup.*
EOF

echo "✅ .gitignore créé"

# Créer README.md
cat > README.md << 'EOF'
# Atelier de Listaro

Site web de l'Atelier de Listaro - Plateforme e-commerce artisanale

## 🚀 Fonctionnalités récentes

### Sélection Multiple d'Images
- Upload de jusqu'à 5 images par produit
- Interface intuitive avec Ctrl+clic
- Prévisualisation en temps réel
- Gestion des images principales

## 📁 Structure du projet

```
atelier_de_listaro/
├── admin_sections/          # Modules d'administration
│   └── products.php         # Gestion des produits (avec upload multiple)
├── _functions/              # Fonctions PHP
├── _css/                    # Feuilles de style
├── uploads/                 # Fichiers uploadés
└── admin_panel.php          # Panel d'administration principal
```

## 🔧 Déploiement

1. **Développement local** → **Git** → **Raspberry Pi**
2. **Tests sur** `http://192.168.1.95/atelier_de_listaro/`
3. **Production sur** `http://88.124.91.246/`

## 📝 Changelog

### 2025-07-11
- ✅ Ajout de la sélection multiple d'images
- ✅ Limitation à 5 images par produit
- ✅ Interface utilisateur améliorée
- ✅ Validation côté client et serveur

## 🛠️ Technologies

- **Backend:** PHP 8+
- **Base de données:** MySQL/MariaDB
- **Frontend:** HTML5, CSS3, JavaScript
- **Serveur:** Apache2 sur Raspberry Pi
EOF

echo "✅ README.md créé"
echo

echo "🔐 ÉTAPE 8: Configuration des permissions"
echo "----------------------------------------"
chown -R www-data:www-data "$REPO_DIR"
# Permettre à admin de modifier les fichiers Git
chgrp -R admin "$REPO_DIR/.git"
chmod -R g+w "$REPO_DIR/.git"
echo "✅ Permissions configurées"
echo

echo "📦 ÉTAPE 9: Premier commit"
echo "----------------------------------------"
# Ajouter tous les fichiers existants
git add .
git commit -m "🎉 Premier commit - Atelier de Listaro avec sélection multiple d'images

✨ Fonctionnalités:
- Panel d'administration
- Gestion des produits
- Upload multiple d'images (max 5)
- Interface utilisateur moderne

🔧 Technique:
- PHP/MySQL
- Apache2 sur Raspberry Pi
- Git pour le versioning"

echo "✅ Premier commit effectué"
echo

echo "🌿 ÉTAPE 10: Création des branches"
echo "----------------------------------------"
# Créer une branche de développement
git branch development
git branch production
echo "✅ Branches créées: main, development, production"
echo

echo "📋 ÉTAPE 11: Création des scripts utiles"
echo "----------------------------------------"

# Script de déploiement rapide
cat > deploy.sh << 'EOF'
#!/bin/bash
# Script de déploiement rapide

echo "🚀 Déploiement Atelier de Listaro"
echo "=================================="

# Aller dans le dossier du projet
cd /var/www/html/atelier_de_listaro

# Afficher le statut
echo "📋 Statut Git:"
git status --short

# Proposer d'ajouter les changements
read -p "Ajouter tous les changements? (y/N): " add_all
if [ "$add_all" = "y" ] || [ "$add_all" = "Y" ]; then
    git add .
    echo "✅ Changements ajoutés"
fi

# Proposer de faire un commit
read -p "Message de commit: " commit_msg
if [ -n "$commit_msg" ]; then
    git commit -m "$commit_msg"
    echo "✅ Commit effectué"
fi

# Corriger les permissions
sudo chown -R www-data:www-data /var/www/html/atelier_de_listaro
sudo chgrp -R admin /var/www/html/atelier_de_listaro/.git
sudo chmod -R g+w /var/www/html/atelier_de_listaro/.git

echo "🎉 Déploiement terminé!"
EOF

chmod +x deploy.sh
chown admin:admin deploy.sh

echo "✅ Script deploy.sh créé"

# Script de sauvegarde
cat > backup.sh << 'EOF'
#!/bin/bash
# Script de sauvegarde avec Git

BACKUP_DIR="/home/admin/backups/$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

echo "💾 Sauvegarde en cours..."
cd /var/www/html/atelier_de_listaro

# Commit automatique avant sauvegarde
git add .
git commit -m "🔄 Sauvegarde automatique $(date '+%Y-%m-%d %H:%M:%S')"

# Créer une archive
git archive --format=tar.gz --output="$BACKUP_DIR/atelier_listaro_$(date +%Y%m%d_%H%M%S).tar.gz" HEAD

echo "✅ Sauvegarde créée: $BACKUP_DIR"
EOF

chmod +x backup.sh
chown admin:admin backup.sh

echo "✅ Script backup.sh créé"
echo

echo "🎯 INSTALLATION TERMINÉE!"
echo "=================================="
echo
echo "📍 Dépôt Git initialisé dans: $REPO_DIR"
echo "🌿 Branches disponibles: main, development, production"
echo "⚙️  Scripts créés:"
echo "   - deploy.sh  (déploiement rapide)"
echo "   - backup.sh  (sauvegarde avec Git)"
echo
echo "🔗 Prochaines étapes:"
echo "1. Configurer un dépôt distant (GitHub, GitLab, etc.)"
echo "2. Utiliser ./deploy.sh pour les déploiements"
echo "3. Utiliser ./backup.sh pour les sauvegardes"
echo
echo "💻 Commandes utiles:"
echo "   git status           # Voir l'état des fichiers"
echo "   git log --oneline    # Voir l'historique"
echo "   git branch          # Voir les branches"
echo "   ./deploy.sh         # Déploiement rapide"
echo "   ./backup.sh         # Sauvegarde"
echo
echo "🌐 URLs de test:"
echo "   Local:  http://192.168.1.95/atelier_de_listaro/"
echo "   Public: http://88.124.91.246/"
echo
echo "🎉 Git est maintenant prêt pour Atelier de Listaro!"
