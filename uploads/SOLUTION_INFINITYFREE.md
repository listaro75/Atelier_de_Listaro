# 🚀 Solutions pour InfinityFree - Atelier de Listaro

## 🔍 Diagnostic du problème

L'erreur **"No such file or directory"** ou **"getaddrinfo failed"** que vous rencontrez est normale et attendue quand vous testez depuis un environnement local. InfinityFree restreint l'accès à ses serveurs MySQL uniquement aux scripts hébergés sur leur plateforme.

## ✅ Solution 1: Import direct via phpMyAdmin (RECOMMANDÉ)

### Étapes à suivre:

1. **Connectez-vous à votre panel InfinityFree**
   - Allez sur: https://app.infinityfree.com/
   - Connectez-vous avec vos identifiants

2. **Accédez à phpMyAdmin**
   - Dans le panel, cliquez sur "MySQL Databases"
   - Cliquez sur "phpMyAdmin" pour votre base de données

3. **Importez le script SQL**
   - Téléchargez le fichier `atelier_listaro_db.sql` depuis votre projet
   - Dans phpMyAdmin, sélectionnez votre base de données
   - Cliquez sur l'onglet "Import"
   - Sélectionnez le fichier `atelier_listaro_db.sql`
   - Cliquez sur "Go"

4. **Vérifiez l'import**
   - Vérifiez que toutes les tables ont été créées
   - Vérifiez que les données de test sont présentes

## ✅ Solution 2: Upload et installation sur InfinityFree

### Étapes à suivre:

1. **Uploadez tous les fichiers sur InfinityFree**
   - Utilisez le gestionnaire de fichiers ou FTP
   - Uploadez tout le contenu du dossier `Atelier_de_Listaro`

2. **Configurez les paramètres**
   - Éditez le fichier `_config/env.php` avec vos vrais paramètres InfinityFree
   
3. **Lancez l'installation**
   - Allez sur: `https://votre-domaine.infinityfree.com/install.php`
   - Suivez les instructions à l'écran

## ✅ Solution 3: Test de connexion sur le serveur

Une fois uploadé sur InfinityFree, testez la connexion:
- Allez sur: `https://votre-domaine.infinityfree.com/test_connexion.php`

## 🔧 Paramètres InfinityFree à utiliser

```php
// Dans _config/env.php
$host = 'sql100.infinityfree.com';  // Votre serveur MySQL
$dbname = 'if0_39368207_atelier';   // Nom de votre base
$username = 'if0_39368207';         // Votre nom d'utilisateur
$password = 'HqYnwuxOm3Po';         // Votre mot de passe
```

## 🚨 Points importants

1. **Les tests en local NE FONCTIONNERONT PAS** - C'est normal avec InfinityFree
2. **Utilisez toujours phpMyAdmin** pour l'administration de la base
3. **Testez uniquement sur le serveur InfinityFree** une fois uploadé

## 📋 Checklist de déploiement

- [ ] Base de données créée dans le panel InfinityFree
- [ ] Script SQL importé via phpMyAdmin
- [ ] Fichiers uploadés sur le serveur
- [ ] Paramètres de connexion configurés dans `_config/env.php`
- [ ] Test de connexion effectué sur `test_connexion.php`
- [ ] Site accessible et fonctionnel

## 🆘 En cas de problème

1. **Vérifiez les paramètres** dans le panel InfinityFree
2. **Consultez les logs d'erreur** dans le panel
3. **Contactez le support InfinityFree** si nécessaire
4. **Utilisez notre script de diagnostic** une fois sur le serveur

## 📧 Support

Si vous rencontrez des difficultés, n'hésitez pas à:
- Consulter la documentation InfinityFree
- Vérifier les forums de support
- Contacter l'équipe technique

---
**Note**: Ce guide est spécifiquement conçu pour InfinityFree. Les erreurs de connexion en local sont normales et attendues.
