# ✅ CHECKLIST - RÉSOLUTION ERREUR DE CONNEXION

## Votre erreur actuelle
```
SQLSTATE[HY000] [2002] No such file or directory
```

## 📋 Actions à effectuer dans l'ordre

### ☐ 1. VÉRIFIER VOS IDENTIFIANTS D'HÉBERGEUR

- [ ] Connectez-vous à votre panel d'hébergement (InfinityFree, 000webhost, etc.)
- [ ] Allez dans la section "Base de données" ou "MySQL"
- [ ] Notez vos vrais identifiants :
  - **Host :** `___________________________`
  - **Base :** `___________________________`
  - **User :** `___________________________`
  - **Pass :** `___________________________`

### ☐ 2. CRÉER/MODIFIER LE FICHIER .env

- [ ] Via FileZilla ou le gestionnaire de fichiers de votre hébergeur
- [ ] Créez un fichier nommé exactement `.env` (avec le point au début)
- [ ] Copiez le contenu de `.env.example` et remplacez par vos vraies données
- [ ] Uploadez ce fichier à la racine de votre site

### ☐ 3. IMPORTER LA BASE DE DONNÉES

**Option A - phpMyAdmin (recommandée) :**
- [ ] Ouvrez phpMyAdmin depuis votre panel d'hébergement
- [ ] Sélectionnez votre base de données
- [ ] Cliquez sur "Importer"
- [ ] Uploadez `atelier_listaro_db.sql`
- [ ] Cliquez "Exécuter"

**Option B - Script automatique :**
- [ ] Allez sur `https://votre-site.com/install.php` dans votre navigateur
- [ ] Suivez les instructions à l'écran

### ☐ 4. TESTER LA CONNEXION

- [ ] Ouvrez `https://votre-site.com/test_simple.php` dans votre navigateur
- [ ] Vérifiez que vous voyez "✅ Connexion à la base de données réussie !"

### ☐ 5. NETTOYER (APRÈS SUCCÈS)

- [ ] Supprimez `test_simple.php`
- [ ] Supprimez `test_connexion.php`
- [ ] Supprimez `diagnostic_connexion.php`
- [ ] Supprimez `install.php`
- [ ] Supprimez `atelier_listaro_db.sql`

## 🚨 ERREURS FRÉQUENTES

**❌ "No such file or directory"**
→ Vos scripts s'exécutent en local, pas sur l'hébergeur

**❌ "Access denied"**
→ Mauvais identifiants dans le fichier .env

**❌ "Unknown database"**
→ La base n'existe pas ou mauvais nom

**❌ ".env file not found"**
→ Le fichier .env n'est pas uploadé ou mal nommé

## 📞 AIDE SUPPLÉMENTAIRE

Si tout échoue :
1. Vérifiez les logs d'erreur dans votre panel d'hébergement
2. Testez avec `diagnostic_connexion.php`
3. Contactez le support de votre hébergeur
4. Envoyez-moi une capture d'écran du résultat de `test_simple.php`

---
**Rappel :** Tous les tests doivent être effectués dans votre navigateur via votre site hébergé, PAS en local !
