# Guide d'utilisation - Upload Multiple d'Images dans l'Admin

## 📋 Vue d'ensemble

Le système d'administration d'Atelier de Listaro a été amélioré pour permettre l'upload et la gestion de plusieurs images par produit. Cette fonctionnalité offre une expérience utilisateur moderne et intuitive.

## 🎯 Fonctionnalités principales

### ✅ Upload Multiple
- **Sélection multiple** : Choisissez plusieurs images en une seule fois
- **Aperçu en temps réel** : Visualisez les images avant l'upload
- **Formats supportés** : JPG, JPEG, PNG, GIF
- **Validation automatique** : Filtrage des formats non supportés

### ✅ Gestion des Images
- **Image principale** : La première image sélectionnée devient automatiquement l'image principale
- **Suppression avant upload** : Retirez des images de la sélection avant validation
- **Réorganisation** : Changez l'ordre des images (la première reste principale)

### ✅ Interface d'Édition
- **Images existantes** : Visualisez toutes les images actuelles du produit
- **Suppression individuelle** : Supprimez des images existantes une par une
- **Changement d'image principale** : Définissez une nouvelle image principale
- **Ajout d'images** : Ajoutez de nouvelles images aux existantes

## 🚀 Comment utiliser

### Ajout d'un nouveau produit

1. **Accéder à l'admin** : Connectez-vous en tant qu'administrateur
2. **Section Produits** : Cliquez sur "Gestion des produits"
3. **Nouveau produit** : Cliquez sur "Ajouter un produit"
4. **Remplir les informations** : Nom, prix, catégorie, stock, description
5. **Sélectionner les images** :
   - Cliquez sur le champ "Images"
   - Sélectionnez plusieurs fichiers (Ctrl+clic ou Cmd+clic)
   - Vérifiez l'aperçu qui s'affiche
6. **Valider** : Cliquez sur "Ajouter"

### Modification d'un produit existant

1. **Sélectionner le produit** : Cliquez sur "Modifier" dans la liste
2. **Voir les images actuelles** : Section "Images actuelles"
3. **Gérer les images existantes** :
   - Supprimer : Cliquez sur le ✕ rouge
   - Définir comme principale : Cliquez sur "Principal"
4. **Ajouter de nouvelles images** :
   - Section "Ajouter de nouvelles images"
   - Sélectionnez les fichiers
   - Vérifiez l'aperçu
5. **Sauvegarder** : Cliquez sur "Modifier"

## 🔧 Aspects techniques

### Structure de la base de données

```sql
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    is_main TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
```

### Fichiers modifiés

1. **admin_sections/products.php** : Interface principale avec nouvelles fonctionnalités
2. **admin_sections/get_product_images.php** : API pour récupérer les images d'un produit
3. **admin_panel.php** : Styles CSS améliorés pour l'interface
4. **test_upload_multiple.html** : Page de test du système

### Fonctions JavaScript principales

- `previewImages()` : Aperçu des images sélectionnées
- `removePreviewImage()` : Suppression d'une image de l'aperçu
- `loadCurrentImages()` : Chargement des images existantes
- `deleteProductImage()` : Suppression d'une image existante
- `setMainImage()` : Définition de l'image principale

## 📁 Structure des fichiers

```
uploads/
└── products/
    ├── 64f8a9b2c1d3e.jpg (image principale)
    ├── 64f8a9b2c1d3f.png (image secondaire)
    └── 64f8a9b2c1d40.gif (image secondaire)
```

## 🎨 Interface utilisateur

### Ajout de produit
- Zone de sélection avec aperçu
- Badge "Principal" sur la première image
- Boutons de suppression (✕) sur chaque image
- Instruction claire sur les formats acceptés

### Modification de produit
- Section séparée pour les images existantes
- Section pour ajouter de nouvelles images
- Boutons d'action contextuels
- Feedback visuel immédiat

### Liste des produits
- Affichage de l'image principale
- Badge numérique indiquant le nombre total d'images
- Miniatures optimisées

## 🛡️ Sécurité

### Validation côté serveur
- Vérification des formats d'image
- Limitation de taille (via configuration PHP)
- Génération de noms uniques pour éviter les conflits
- Suppression physique des fichiers lors de la suppression

### Validation côté client
- Filtrage des formats non supportés
- Aperçu sécurisé via FileReader API
- Validation des champs obligatoires

## 🧪 Tests

### Test manuel
1. Ouvrir `test_upload_multiple.html`
2. Sélectionner plusieurs images
3. Vérifier l'aperçu et les fonctionnalités
4. Tester la suppression d'images

### Test en production
1. Accéder à l'admin en ligne
2. Créer un produit test avec plusieurs images
3. Modifier le produit et gérer les images
4. Vérifier l'affichage sur le site public

## 📈 Améliorations futures possibles

- [ ] Drag & drop pour réorganiser les images
- [ ] Redimensionnement automatique des images
- [ ] Compression des images avant upload
- [ ] Galerie avec zoom pour l'aperçu
- [ ] Upload par lots pour plusieurs produits
- [ ] Intégration avec un CDN

## 🔍 Dépannage

### Problèmes courants

**Images ne s'affichent pas**
- Vérifier les permissions du dossier `uploads/products/`
- Contrôler le chemin des images dans la base de données

**Upload échoue**
- Vérifier la configuration PHP (`upload_max_filesize`, `post_max_size`)
- Contrôler l'espace disque disponible
- Vérifier les permissions d'écriture

**Aperçu ne fonctionne pas**
- Vérifier que JavaScript est activé
- Contrôler la console pour les erreurs
- Tester avec différents navigateurs

## 📞 Support

Pour toute question ou problème :
1. Consulter la console développeur du navigateur
2. Vérifier les logs d'erreur PHP
3. Tester avec des images de petite taille
4. Contacter l'administrateur système si nécessaire

---

*Document créé le : <?php echo date('d/m/Y H:i'); ?>*
*Version : 1.0*
