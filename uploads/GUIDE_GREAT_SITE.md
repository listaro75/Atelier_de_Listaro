# 🌐 Guide Spécifique GREAT-SITE.NET

## Votre site
**URL :** http://atelierdelistaro.great-site.net

## 🎯 Actions à effectuer maintenant

### 1. Test immédiat de connexion
Cliquez sur ce lien : **http://atelierdelistaro.great-site.net/test_simple.php**

### 2. Récupérer vos identifiants de base de données

1. **Connectez-vous à votre panel Great-Site.net**
2. **Trouvez la section "Base de données" ou "MySQL"**
3. **Notez ces informations :**
   - **Host :** (ex: sql302.great-site.net)
   - **Nom de la base :** (ex: great12345_atelier)
   - **Utilisateur :** (ex: great12345_user)
   - **Mot de passe :** (votre mot de passe MySQL)

### 3. Créer le fichier .env

1. **Via FileZilla ou le gestionnaire de fichiers de Great-Site.net**
2. **Créez un fichier nommé `.env`** à la racine de votre site
3. **Contenu du fichier :**
   ```env
   DB_HOST=sql302.great-site.net
   DB_NAME=great12345_atelier
   DB_USERNAME=great12345_user
   DB_PASSWORD=votre_mot_de_passe_mysql
   
   SITE_URL=http://atelierdelistaro.great-site.net
   ADMIN_EMAIL=admin@atelierdelistaro.great-site.net
   ```

### 4. Importer la base de données

**Méthode phpMyAdmin :**
1. **Ouvrez phpMyAdmin** depuis votre panel Great-Site.net
2. **Sélectionnez votre base de données**
3. **Onglet "Importer"**
4. **Choisir le fichier :** `atelier_listaro_db.sql`
5. **Cliquer "Exécuter"**

### 5. Test final

Retournez sur : **http://atelierdelistaro.great-site.net/test_simple.php**

Vous devriez voir : "✅ Connexion à la base de données réussie !"

## 🔧 Particularités Great-Site.net

### Format des identifiants typiques :
```
Host: sql302.great-site.net (ou sql301, sql303, etc.)
Base: great12345_nombase
User: great12345_user
```

### Temps de propagation :
- **Base de données :** 5-10 minutes après création
- **Fichiers :** Immédiat via FileZilla

### Limites connues :
- **Taille max :** 100 MB par base
- **Import phpMyAdmin :** 50 MB max par fichier
- **Connexions simultanées :** Limitées

## 🚨 Si problème persiste

### Test de diagnostic complet :
http://atelierdelistaro.great-site.net/diagnostic_connexion.php

### Vérifications supplémentaires :
1. **Panel Great-Site.net :** Base de données créée et active
2. **Fichier .env :** Bien présent et avec les bons identifiants
3. **phpMyAdmin :** Accessible et fonctionnel
4. **Permissions :** Dossier uploads accessible en écriture

### Contact support :
Si rien ne fonctionne, contactez le support Great-Site.net avec :
- Votre nom de domaine
- L'erreur exacte rencontrée
- Capture d'écran du test de connexion

---
**URL de test principal :** http://atelierdelistaro.great-site.net/test_simple.php
