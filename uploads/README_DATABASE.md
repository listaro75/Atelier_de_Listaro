# 🎨 Atelier de Listaro - Base de Données

Ce dossier contient les scripts nécessaires pour initialiser la base de données de votre site e-commerce **Atelier de Listaro**.

## 📋 Contenu

- `atelier_listaro_db.sql` - Script SQL pour phpMyAdmin (RECOMMANDÉ)
- `test_simple.php` - Test de connexion simplifié (NOUVEAU)
- `diagnostic_connexion.php` - Diagnostic avancé de connexion
- `test_connexion.php` - Testeur de connexion simple
- `install.php` - Script PHP d'installation automatique
- `GUIDE_INSTALLATION_RAPIDE.md` - Guide express pour hébergeur distant
- `CHECKLIST.md` - Liste de vérification étape par étape
- `.env.example` - Modèle de configuration
- `README_DATABASE.md` - Ce fichier d'instructions

## 🌐 Votre site : http://atelierdelistaro.great-site.net
## 🗄️ Votre base de données : if0_39368207_atelier_de_listaro (InfinityFree)

## 🚀 Installation Rapide - Configuration détectée

### ✅ Situation actuelle
- **Hébergeur :** InfinityFree
- **Base de données :** `if0_39368207_atelier_de_listaro` (créée ✅)
- **phpMyAdmin :** Accessible ✅
- **Site web :** http://atelierdelistaro.great-site.net

### Étape 1 : Créer le fichier .env
Créez un fichier `.env` à la racine avec ces données exactes :
```env
DB_HOST=sql302.infinityfree.com
DB_NAME=if0_39368207_atelier_de_listaro
DB_USERNAME=if0_39368207
DB_PASSWORD=VOTRE_MOT_DE_PASSE_MYSQL_INFINITYFREE
```

### Étape 2 : Importer la base via phpMyAdmin
1. **Vous êtes déjà dans phpMyAdmin** ✅
2. **Votre base `if0_39368207_atelier_de_listaro` est sélectionnée**
3. **Cliquez sur "Importer"**
4. **Choisissez le fichier** `atelier_listaro_db.sql`
5. **Cliquez sur "Exécuter"**

### Étape 3 : Test de connexion
Allez sur : **http://atelierdelistaro.great-site.net/test_simple.php**

### Option 3 : Installation manuelle (hébergement local uniquement)

1. **Créez la base de données** :
   ```sql
   CREATE DATABASE atelier_listaro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

2. **Importez le script SQL** :
   ```bash
   mysql -u root -p atelier_listaro < database_setup.sql
   ```

3. **Configurez le fichier d'environnement** `_config/env.php`

## 🗄️ Structure de la Base de Données

### Tables Principales

| Table | Description |
|-------|-------------|
| `user` | Utilisateurs du site (clients et admin) |
| `products` | Produits de la boutique |
| `product_images` | Images des produits |
| `prestations` | Services proposés |
| `prestation_images` | Images des prestations |
| `orders` | Commandes des clients |
| `order_items` | Articles dans les commandes |
| `product_likes` | Likes des produits |
| `prestation_likes` | Likes des prestations |

### Vues Créées

- `products_with_main_image` - Produits avec leur image principale
- `prestations_with_main_image` - Prestations avec leur image principale  
- `orders_details` - Commandes avec détails complets

### Procédures Stockées

- `CalculateOrderTotal` - Calcule le total d'une commande
- `UpdateStockAfterOrder` - Met à jour le stock après commande

## 👤 Comptes par Défaut

### Administrateur
- **Pseudo :** `admin`
- **Email :** `admin@atelier-listaro.com`
- **Mot de passe :** `Admin123!`
- **Rôle :** `admin`

### Utilisateur Test
- **Pseudo :** `testuser`
- **Email :** `test@atelier-listaro.com`
- **Mot de passe :** `Test123!`
- **Rôle :** `user`

> ⚠️ **Important :** Changez ces mots de passe après la première connexion !

## 📁 Dossiers Créés

L'installation crée automatiquement :
- `uploads/` - Dossier principal des uploads
- `uploads/products/` - Images des produits
- `uploads/prestations/` - Images des prestations
- `_config/env.php` - Configuration d'environnement

## ⚙️ Configuration Post-Installation

### 1. Stripe (Paiements)
Modifiez dans `_config/env.php` :
```php
putenv('STRIPE_PUBLIC_KEY=pk_test_votre_clé_publique');
putenv('STRIPE_SECRET_KEY=sk_test_votre_clé_secrète');
```

### 2. Email
Configurez vos paramètres SMTP :
```php
putenv('MAIL_HOST=smtp.gmail.com');
putenv('MAIL_USERNAME=votre-email@gmail.com');
putenv('MAIL_PASSWORD=votre-mot-de-passe-app');
```

### 3. Permissions
Assurez-vous que les dossiers uploads ont les bonnes permissions :
```bash
chmod 755 uploads/
chmod 755 uploads/products/
chmod 755 uploads/prestations/
```

## 🔧 Données d'Exemple

Le script inclut des données d'exemple :

### Produits
- Figurine Dragon Rouge (45,99€)
- Peinture Acrylique Premium Set (29,99€)
- Socle Hexagonal Deluxe (8,50€)
- Figurine Guerrier Elfe (32,99€)
- Kit de Pinceaux Professionnels (19,99€)

### Prestations
- Peinture Figurine Standard (40,00€)
- Peinture Figurine Premium (80,00€)
- Impression 3D Figurine (25,00€)
- Site Web Vitrine (500,00€)
- Boutique E-commerce (1200,00€)

## 🛡️ Sécurité

### Triggers Implémentés
- Image principale unique par produit
- Image principale unique par prestation

### Index de Performance
- Index sur catégories, prix, stock
- Index composites pour requêtes fréquentes
- Index sur les relations foreign key

### Contraintes
- Clés étrangères avec CASCADE
- Contraintes UNIQUE sur pseudo et email
- Types ENUM pour les statuts

## 🐛 Dépannage

### ⚠️ Erreur "No such file or directory" (SQLSTATE[HY000] [2002])

**Diagnostic rapide :**
1. **Lancez le diagnostic complet** : `diagnostic_connexion.php`
2. **Vérifiez votre panel InfinityFree** : base créée et active ?
3. **Testez phpMyAdmin** : accessible depuis votre panel ?

**Solutions par ordre de priorité :**

#### Solution 1 : Vérification InfinityFree
```bash
✅ Connectez-vous à votre panel InfinityFree
✅ Section "MySQL Databases" 
✅ Vérifiez que votre base existe : if0_39368207_atelier
✅ Attendez 10-15 minutes après création
✅ Testez phpMyAdmin depuis le panel
```

#### Solution 2 : Import direct via phpMyAdmin
Si les scripts PHP ne fonctionnent pas :
1. **Ouvrez phpMyAdmin** depuis votre panel InfinityFree
2. **Sélectionnez votre base** de données
3. **Importez directement** `atelier_listaro_db.sql`
4. **Configurez manuellement** `_config/env.php`

#### Solution 3 : Hébergeur alternatif
Si InfinityFree ne fonctionne pas :
- **000webhost** (gratuit, plus stable)
- **Hostinger** (gratuit avec pub)
- **XAMPP local** (pour développement)

### Erreur de connexion InfinityFree
Vérifiez vos paramètres :
```php
'host' => 'sql100.infinityfree.com',    // Peut être sql100, sql101, etc.
'dbname' => 'if0_39368207_atelier',     // Remplacez XXX par votre suffixe
'username' => 'if0_39368207',
'password' => 'HqYnwuxOm3Po',
'port' => 3306
```

### Base de données non trouvée
1. **Créez votre base** dans le panel InfinityFree
2. **Notez le nom complet** (ex: if0_39368207_atelier)
3. **Attendez 5-10 minutes** pour la propagation

### Erreur d'upload
Vérifiez les permissions des dossiers :
```bash
ls -la uploads/
```

### Tables manquantes
Réimportez le script SQL :
1. Allez dans phpMyAdmin
2. Sélectionnez votre base de données  
3. Importez `atelier_listaro_db.sql`

## 📞 Support

Si vous rencontrez des problèmes :

### 🔍 Outils de diagnostic :
1. **`diagnostic_connexion.php`** - Diagnostic complet automatique
2. **`test_connexion.php`** - Test simple de connexion
3. **phpMyAdmin** depuis votre panel d'hébergement

### 📋 Checklist de dépannage :
- [ ] Base de données créée dans le panel InfinityFree
- [ ] Attente de 10-15 minutes après création
- [ ] phpMyAdmin accessible depuis le panel
- [ ] Paramètres de connexion corrects
- [ ] Extensions PHP PDO et PDO_MySQL activées

### 🆘 Si rien ne fonctionne :
1. **Utilisez phpMyAdmin directement** pour importer le SQL
2. **Testez un autre hébergeur** (000webhost, Hostinger)
3. **Installez en local** avec XAMPP/WAMP
4. **Contactez le support** de votre hébergeur

### 🌐 Hébergeurs alternatifs testés :
- **000webhost** : Plus stable, configuration similaire
- **Hostinger gratuit** : Très fiable
- **XAMPP local** : Parfait pour le développement

## 🔧 Configuration Spécifique InfinityFree

### Créer votre base de données :
1. Connectez-vous à votre panel InfinityFree
2. Allez dans "MySQL Databases"
3. Créez une nouvelle base (ex: atelier)
4. Notez le nom complet généré (ex: if0_39368207_atelier)

### Paramètres de connexion type :
```php
$db_config = [
    'host' => 'sql100.infinityfree.com',  // Vérifiez votre numéro de serveur
    'dbname' => 'if0_39368207_atelier',   // Votre base complète
    'username' => 'if0_39368207',         // Votre nom d'utilisateur
    'password' => 'HqYnwuxOm3Po',         // Votre mot de passe
    'charset' => 'utf8mb4',
    'port' => 3306
];
```

## 🔄 Mise à Jour

Pour mettre à jour la structure :
1. Sauvegardez vos données
2. Modifiez le script SQL
3. Réexécutez les parties modifiées

---

**Bonne utilisation de votre site Atelier de Listaro ! 🎨**
