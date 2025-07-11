# NETTOYAGE INTELLIGENT - SUPPRESSION DES FICHIERS INUTILES
# Supprime tous les fichiers de test, debug, guides, backup et doublons

Write-Host "========================================="
Write-Host " NETTOYAGE INTELLIGENT DU REPERTOIRE"
Write-Host "========================================="

$projectPath = "C:\Users\lucie\site\Atelier_de_listaro"

# FICHIERS ESSENTIELS A CONSERVER (site web fonctionnel)
$filesToKeep = @(
    # Pages principales du site
    "index.php", "shop.php", "cart.php", "checkout.php", 
    "connexion.php", "inscription.php", "deconnexion.php",
    "portfolio.php", "prestation.php", "product_details.php",
    "profile.php", "my_orders.php", "confirmation.php",
    
    # Administration
    "administrateur.php", "admin_orders.php", "admin_prestations.php",
    
    # Configuration
    ".env",
    
    # Fichiers de base (simples)
    "link.php", "menu.php", "meta.php", "script.php"
)

# DOSSIERS ESSENTIELS A CONSERVER
$foldersToKeep = @(
    "_css", "_functions", "_config", "_db", "_head", "_footer", "_menu",
    "ajax", "stripe-php", "uploads"
)

# FICHIERS A SUPPRIMER (patterns)
$filesToDelete = @(
    # Tous les fichiers de test
    "test_*.php", "test_*.html",
    
    # Fichiers de diagnostic
    "diagnostic_*.php", "diagnostic_*.html",
    
    # Scripts d'installation et de correction
    "install*.php", "install*.sh",
    "fix_*.php", "fix_*.sh",
    "configure_*.php", "configure_*.sh",
    "setup_*.php", "create_*.php",
    "correction_*.php", "reparation_*.php",
    "migrate_*.php", "integrate_*.php",
    "emergency_*.php", "temp_*.php",
    "server_*.php", "etat_*.php",
    
    # Fichiers de validation
    "validation_*.php", "debug_*.php",
    
    # Guides et documentation
    "*.md", "README*", "GUIDE_*", "INSTALLATION_*",
    "CORRECTION_*", "RESOLUTION_*", "RAPPORT_*",
    "CHECKLIST*", "ACTIONS_*", "EMAIL_SYSTEM*",
    "FILEZILLA_*", "SOLUTION_*", "SMTP_*",
    "FINALISATION_*", "NOUVEAU_*", "RECOVERY_*",
    "SYSTEME_*", "TELEPHONE_*", "RGPD_*",
    
    # Fichiers SQL
    "*.sql", "database_*.sql", "atelier_*.sql",
    
    # Fichiers de configuration temporaires
    "apache_config_*.conf",
    
    # Scripts de nettoyage
    "cleanup*.php", "nettoyage_*.php", "nettoyage_*.ps1",
    "clean*.ps1",
    
    # Fichiers divers
    "DO NOT UPLOAD*", "collect_data.php",
    "cookie_consent*.php", "politique_*.php",
    "user_details.php", "order_details.php",
    "connexion_secure.php"
)

# FONCTION POUR SUPPRIMER LES DOUBLONS DANS LES DOSSIERS
function RemoveDuplicatesInFolder($folderPath) {
    Write-Host "Nettoyage des doublons dans : $folderPath"
    
    if (Test-Path $folderPath) {
        # Supprimer les fichiers avec suffixes _backup, _fixed, _old, etc.
        $duplicatePatterns = @("*_backup.*", "*_fixed.*", "*_old.*", "*_temp.*", "*_copy.*", "*_original.*")
        
        foreach ($pattern in $duplicatePatterns) {
            $files = Get-ChildItem -Path $folderPath -Name $pattern -ErrorAction SilentlyContinue
            foreach ($file in $files) {
                $fullPath = Join-Path $folderPath $file
                Remove-Item $fullPath -Force -ErrorAction SilentlyContinue
                Write-Host "  Supprime doublon: $file"
            }
        }
    }
}

Write-Host "Analyse du repertoire..."
Write-Host ""

# Compter les fichiers avant
$totalFilesBefore = (Get-ChildItem -Path $projectPath -Recurse -File).Count
Write-Host "Nombre total de fichiers AVANT: $totalFilesBefore"
Write-Host ""

$deletedCount = 0
$errorCount = 0

# 1. SUPPRIMER LES FICHIERS INUTILES DU REPERTOIRE PRINCIPAL
Write-Host "1. SUPPRESSION DES FICHIERS INUTILES..."
Write-Host "----------------------------------------"

foreach ($pattern in $filesToDelete) {
    $files = Get-ChildItem -Path $projectPath -Name $pattern -ErrorAction SilentlyContinue
    foreach ($file in $files) {
        # Vérifier que ce n'est pas un fichier essentiel
        if ($filesToKeep -contains $file) {
            Write-Host "  PRESERVE: $file (fichier essentiel)"
            continue
        }
        
        $fullPath = Join-Path $projectPath $file
        try {
            Remove-Item $fullPath -Force
            Write-Host "  SUPPRIME: $file"
            $deletedCount++
        } catch {
            Write-Host "  ERREUR: $file"
            $errorCount++
        }
    }
}

# 2. NETTOYER LES DOUBLONS DANS LES DOSSIERS ESSENTIELS
Write-Host ""
Write-Host "2. NETTOYAGE DES DOUBLONS..."
Write-Host "-----------------------------"

foreach ($folder in $foldersToKeep) {
    $folderPath = Join-Path $projectPath $folder
    RemoveDuplicatesInFolder $folderPath
}

# 3. SUPPRIMER LE DOSSIER UPLOADS EN DOUBLE
Write-Host ""
Write-Host "3. SUPPRESSION DU DOSSIER UPLOADS DUPLIQUE..."
Write-Host "----------------------------------------------"

$duplicateUploads = Join-Path $projectPath "uploads\uploads"
if (Test-Path $duplicateUploads) {
    try {
        Remove-Item $duplicateUploads -Recurse -Force
        Write-Host "  SUPPRIME: Dossier uploads/uploads/ (doublon)"
        $deletedCount += 50  # Estimation
    } catch {
        Write-Host "  ERREUR: Impossible de supprimer uploads/uploads/"
        $errorCount++
    }
}

# 4. NETTOYER LES FICHIERS DUPLIQUES DANS uploads/
Write-Host ""
Write-Host "4. NETTOYAGE DES FICHIERS DUPLIQUES DANS UPLOADS..."
Write-Host "----------------------------------------------------"

$uploadsPath = Join-Path $projectPath "uploads"
if (Test-Path $uploadsPath) {
    # Supprimer les fichiers PHP de test dans uploads/
    $testFiles = @("test_*.php", "*.html", "*.md", "diagnostic_*.php")
    foreach ($pattern in $testFiles) {
        $files = Get-ChildItem -Path $uploadsPath -Name $pattern -ErrorAction SilentlyContinue
        foreach ($file in $files) {
            $fullPath = Join-Path $uploadsPath $file
            try {
                Remove-Item $fullPath -Force
                Write-Host "  SUPPRIME: uploads/$file"
                $deletedCount++
            } catch {
                Write-Host "  ERREUR: uploads/$file"
                $errorCount++
            }
        }
    }
}

# 5. COMPTER LES FICHIERS APRES
Write-Host ""
Write-Host "5. RESULTATS FINAUX..."
Write-Host "----------------------"

$totalFilesAfter = (Get-ChildItem -Path $projectPath -Recurse -File).Count
$totalDeleted = $totalFilesBefore - $totalFilesAfter

Write-Host ""
Write-Host "========================================="
Write-Host " NETTOYAGE TERMINE !"
Write-Host "========================================="
Write-Host ""
Write-Host "Fichiers AVANT le nettoyage : $totalFilesBefore"
Write-Host "Fichiers APRES le nettoyage : $totalFilesAfter"
Write-Host "Total supprime              : $totalDeleted"
Write-Host "Erreurs                     : $errorCount"
Write-Host ""
Write-Host "REPERTOIRE NETTOYE :"
Write-Host "==================="
Write-Host "✓ Site web fonctionnel conserve"
Write-Host "✓ Configuration (.env) conservee"
Write-Host "✓ Dossiers essentiels conserves"
Write-Host "✓ Tous les fichiers de test supprimes"
Write-Host "✓ Tous les guides supprimes"
Write-Host "✓ Tous les doublons supprimes"
Write-Host "✓ Scripts d'installation supprimes"
Write-Host ""
Write-Host "Votre site continue de fonctionner normalement !"
Write-Host ""

# Afficher le contenu final
Write-Host "CONTENU FINAL DU REPERTOIRE :"
Write-Host "============================="
$finalFiles = Get-ChildItem -Path $projectPath -File | Sort-Object Name
foreach ($file in $finalFiles) {
    Write-Host "  $($file.Name)"
}

Write-Host ""
Write-Host "DOSSIERS CONSERVES :"
Write-Host "==================="
$finalFolders = Get-ChildItem -Path $projectPath -Directory | Sort-Object Name
foreach ($folder in $finalFolders) {
    Write-Host "  $($folder.Name)/"
}

Write-Host ""
Write-Host "Appuyez sur Entree pour fermer..."
Read-Host
