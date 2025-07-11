# 🚀 INSTALLATION EXPRESS - HÉBERGEUR DISTANT

## Votre situation actuelle
Vous avez uploadé votre site via FileZilla sur un hébergeur distant et vous obtenez l'erreur :
`SQLSTATE[HY000] [2002] No such file or directory`

## ⚠️ PROBLÈME IDENTIFIÉ
Les hébergeurs comme InfinityFree bloquent les connexions MySQL depuis l'extérieur pour la sécurité. Vos scripts PHP ne peuvent se connecter à la base que s'ils s'exécutent **depuis le serveur de votre hébergeur**.

## ✅ SOLUTION EN 4 ÉTAPES

### ÉTAPE 1 : Configurer vos identifiants de base de données

1. **Connectez-vous à votre panel d'hébergement** (InfinityFree, 000webhost, etc.)
2. **Trouvez vos identifiants MySQL** dans la section "Base de données" :
   - Nom d'hôte (ex: `sql302.infinityfree.com`)
   - Nom de la base (ex: `if0_12345678_atelier_listaro`)
   - Nom d'utilisateur (ex: `if0_12345678`)
   - Mot de passe

3. **Modifiez le fichier `.env`** sur votre serveur avec ces vraies valeurs :
   ```
   DB_HOST=sql302.infinityfree.com
   DB_NAME=if0_12345678_atelier_listaro
   DB_USERNAME=if0_12345678
   DB_PASSWORD=votre_vrai_mot_de_passe
   ```

### ÉTAPE 2 : Importer la base de données

**Méthode recommandée - phpMyAdmin :**
1. Connectez-vous à phpMyAdmin depuis votre panel d'hébergement
2. Sélectionnez votre base de données
3. Cliquez sur "Importer"
4. Uploadez le fichier `atelier_listaro_db.sql`
5. Cliquez sur "Exécuter"

### ÉTAPE 3 : Tester la connexion

Depuis votre navigateur, allez sur :
`https://votre-site.com/test_connexion.php`

Si ça fonctionne, vous verrez : "✅ Connexion à la base de données réussie !"

### ÉTAPE 4 : Supprimer les fichiers temporaires

Une fois que tout fonctionne, supprimez ces fichiers par sécurité :
- `test_connexion.php`
- `diagnostic_connexion.php`
- `install.php`
- `atelier_listaro_db.sql`

## 🔧 SI PROBLÈME PERSISTE

1. **Vérifiez que le fichier `.env` est bien présent** sur votre serveur
2. **Testez avec le diagnostic avancé** : `https://votre-site.com/diagnostic_connexion.php`
3. **Vérifiez les logs d'erreur** dans votre panel d'hébergement
4. **Contactez le support de votre hébergeur** si les identifiants sont corrects

## 📋 FICHIERS À UPLOADER

Assurez-vous d'avoir uploadé :
- ✅ `.env` (avec vos vraies données)
- ✅ `atelier_listaro_db.sql` (pour l'import)
- ✅ `test_connexion.php` (pour tester)
- ✅ Tous les dossiers de votre projet

---
**Rappel important :** Les scripts PHP ne fonctionnent que depuis le serveur hébergeur, pas depuis votre ordinateur local.
