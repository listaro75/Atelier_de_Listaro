# 📁 LISTE D'UPLOAD FILEZILLA - Atelier de Listaro

## 🎯 Votre configuration InfinityFree
- **Site :** http://atelierdelistaro.great-site.net
- **Base :** if0_39368207_atelier_de_listaro
- **Host FTP :** Vérifiez dans votre panel InfinityFree

## 📋 FICHIERS À UPLOADER (Par ordre de priorité)

### 🔥 PRIORITÉ 1 - Test de base
```
test_serveur.php          ← Test que le serveur fonctionne
.env                      ← Configuration DB (renommé depuis .env.ready)
```

### 🔥 PRIORITÉ 2 - Fichiers essentiels
```
index.php                 ← Page d'accueil
test_simple.php           ← Test de connexion DB
atelier_listaro_db.sql    ← Base de données à importer
```

### 🔥 PRIORITÉ 3 - Configuration système
```
_config/
├── env.php              ← Chargement des variables d'environnement
_db/
├── connexion_DB.php     ← Connexion à la base de données  
_functions/
├── auth.php             ← Fonctions d'authentification
├── cart.php             ← Fonctions panier
├── mail.php             ← Fonctions email
```

### 🔥 PRIORITÉ 4 - Interface utilisateur
```
_css/                     ← Tous les fichiers CSS
_head/                    ← Headers HTML
_footer/                  ← Footers HTML
_menu/                    ← Menus de navigation
```

### 🔥 PRIORITÉ 5 - Pages principales
```
connexion.php             ← Page de connexion
inscription.php           ← Page d'inscription
shop.php                  ← Boutique
portfolio.php             ← Portfolio
prestation.php            ← Prestations
cart.php                  ← Panier
checkout.php              ← Commande
profile.php               ← Profil utilisateur
```

### 🔥 PRIORITÉ 6 - Administration
```
administrateur.php        ← Panel admin
admin_orders.php          ← Gestion commandes
admin_prestations.php     ← Gestion prestations
```

### 🔥 PRIORITÉ 7 - AJAX et fonctionnalités
```
ajax/                     ← Tous les fichiers AJAX
stripe-php/               ← Système de paiement
uploads/                  ← Dossier des images (vide au début)
```

## 🚀 PROCÉDURE D'UPLOAD RECOMMANDÉE

### Étape 1 : Test de base
1. **Uploadez :** `test_serveur.php`
2. **Testez :** http://atelierdelistaro.great-site.net/test_serveur.php
3. **Résultat attendu :** "✅ PHP fonctionne !"

### Étape 2 : Configuration
1. **Renommez :** `.env.ready` → `.env`
2. **Uploadez :** `.env`
3. **Uploadez :** Dossier `_config/` complet
4. **Uploadez :** Dossier `_db/` complet

### Étape 3 : Test de connexion
1. **Uploadez :** `test_simple.php`
2. **Testez :** http://atelierdelistaro.great-site.net/test_simple.php
3. **Si erreur :** Vérifiez le fichier .env

### Étape 4 : Site principal
1. **Uploadez :** `index.php`
2. **Uploadez :** Dossiers `_css/`, `_functions/`, `_head/`, `_footer/`, `_menu/`
3. **Testez :** http://atelierdelistaro.great-site.net/

### Étape 5 : Base de données
1. **Ouvrez phpMyAdmin** depuis votre panel
2. **Importez :** `atelier_listaro_db.sql`
3. **Re-testez :** test_simple.php

### Étape 6 : Fonctionnalités complètes
1. **Uploadez :** Tous les autres fichiers PHP
2. **Uploadez :** Dossiers `ajax/`, `stripe-php/`
3. **Créez :** Dossier `uploads/` (vide)

## 📍 Structure finale sur le serveur
```
htdocs/ (ou public_html/)
├── index.php
├── .env
├── test_simple.php
├── test_serveur.php
├── connexion.php
├── inscription.php
├── shop.php
├── [...autres fichiers PHP...]
├── _config/
│   └── env.php
├── _db/
│   └── connexion_DB.php
├── _css/
├── _functions/
├── ajax/
├── uploads/
└── [...autres dossiers...]
```

## 🔍 Tests après chaque étape

1. **Serveur :** http://atelierdelistaro.great-site.net/test_serveur.php
2. **Connexion DB :** http://atelierdelistaro.great-site.net/test_simple.php  
3. **Site principal :** http://atelierdelistaro.great-site.net/

## ⚠️ Points importants

- **Respectez la casse** des noms de fichiers
- **Uploadez dans htdocs/** (ou public_html/)
- **Attendez 2-3 minutes** après upload
- **Le fichier .env ne doit PAS avoir d'extension**
- **Vérifiez les permissions** si problème

---
**Premier test obligatoire :** http://atelierdelistaro.great-site.net/test_serveur.php
