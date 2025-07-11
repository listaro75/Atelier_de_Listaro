# 🚀 Installation Rapide - InfinityFree

Guide d'installation spécifique pour **InfinityFree** avec vos paramètres.

## ✅ Vos Paramètres de Connexion

```
Host: sql100.infinityfree.com
Username: if0_39368207
Password: HqYnwuxOm3Po
Base: if0_39368207_XXX (remplacez XXX par le nom de votre choix)
Port: 3306
```

## 🎯 Installation en 3 Étapes

### Étape 1 : Créer votre base de données

1. **Connectez-vous** à votre panel InfinityFree
2. **Allez dans** "MySQL Databases"
3. **Créez une nouvelle base** :
   - Nom suggéré : `atelier`
   - Nom complet généré : `if0_39368207_atelier`
4. **Attendez 5-10 minutes** pour la propagation

### Étape 2 : Tester la connexion

1. **Ouvrez** `test_connexion.php` dans votre navigateur
2. **Vérifiez** que la connexion fonctionne
3. **Si échec** : vérifiez que votre base de données est bien créée

### Étape 3 : Installer la base de données

**Option A - Import direct (Recommandé) :**
1. **Ouvrez phpMyAdmin** depuis votre panel InfinityFree
2. **Sélectionnez** votre base `if0_39368207_atelier`
3. **Cliquez** sur "Importer"
4. **Choisissez** le fichier `atelier_listaro_db.sql`
5. **Cliquez** "Exécuter"

**Option B - Script automatique :**
1. **Ouvrez** `install.php?confirm=yes` dans votre navigateur
2. **Suivez** les instructions

## 🔧 Configuration du site

### Mise à jour du fichier de connexion

Modifiez `_config/env.php` :
```php
putenv('DB_HOST=sql100.infinityfree.com');
putenv('DB_NAME=if0_39368207_atelier');
putenv('DB_USER=if0_39368207');
putenv('DB_PASS=HqYnwuxOm3Po');
```

### Vérification

**Comptes créés :**
- Admin : `admin` / `Admin123!`
- Test : `testuser` / `Test123!`

**Tables créées :**
- user, products, prestations, orders, etc.

## 🛡️ Sécurité

**Après installation :**
1. ✅ Supprimez `test_connexion.php`
2. ✅ Supprimez `install.php`
3. ✅ Changez le mot de passe admin
4. ✅ Configurez Stripe et email

## 🐛 Problèmes Courants

**"Base de données non trouvée" :**
- Vérifiez que vous avez créé la base dans le panel
- Attendez 5-10 minutes après création
- Vérifiez le nom exact (avec le préfixe if0_39368207_)

**"Erreur de connexion" :**
- Vérifiez que le serveur est sql100 (pas sql108 ou autre)
- Vérifiez votre mot de passe
- Testez avec `test_connexion.php`

**"Tables déjà existantes" :**
- Normal si vous relancez l'installation
- Le script gère les doublons automatiquement

## 💡 Conseils InfinityFree

- **Limitations** : 10 bases de données max
- **Performance** : Peut être lente en gratuit
- **Uptime** : Suspensions possibles si inactif
- **Backup** : Pas de sauvegarde automatique

---

**Votre site sera accessible une fois l'installation terminée ! 🎨**
