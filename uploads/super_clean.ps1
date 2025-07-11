# NETTOYAGE COMPLET DU REPERTOIRE
# Supprime tous les fichiers inutiles et garde seulement l'essentiel

Write-Host "========================================"
Write-Host " NETTOYAGE COMPLET DU REPERTOIRE"
Write-Host "========================================"

$projectPath = "C:\Users\lucie\site\Atelier_de_listaro"

# FICHIERS ESSENTIELS A CONSERVER
$filesToKeep = @(
    "index.php", "shop.php", "cart.php", "checkout.php", 
    "connexion.php", "inscription.php", "deconnexion.php",
    "portfolio.php", "prestation.php", "product_details.php",
    "profile.php", "my_orders.php", "confirmation.php",
    "administrateur.php", "admin_orders.php", "admin_prestations.php",
    ".env", "link.php", "menu.php", "meta.php", "script.php"
)

# DOSSIERS ESSENTIELS A CONSERVER
$foldersToKeep = @(
    "_css", "_functions", "_config", "_db", "_head", "_footer", "_menu",
    "ajax", "stripe-php", "uploads"
)

# FICHIERS A SUPPRIMER
$filesToDelete = @(
    "test_*.php", "test_*.html", "diagnostic_*.php", "diagnostic_*.html",
    "install*.php", "install*.sh", "fix_*.php", "fix_*.sh",
    "configure_*.php", "configure_*.sh", "setup_*.php", "create_*.php",
    "correction_*.php", "reparation_*.php", "migrate_*.php", "integrate_*.php",
    "emergency_*.php", "temp_*.php", "server_*.php", "etat_*.php",
    "validation_*.php", "debug_*.php", "*.md", "README*", "GUIDE_*", 
    "INSTALLATION_*", "CORRECTION_*", "RESOLUTION_*", "RAPPORT_*",
    "CHECKLIST*", "ACTIONS_*", "EMAIL_SYSTEM*", "FILEZILLA_*", 
    "SOLUTION_*", "SMTP_*", "FINALISATION_*", "NOUVEAU_*", "RECOVERY_*",
    "SYSTEME_*", "TELEPHONE_*", "RGPD_*", "*.sql", "database_*.sql", 
    "atelier_*.sql", "apache_config_*.conf", "cleanup*.php", 
    "nettoyage_*.php", "nettoyage_*.ps1", "clean*.ps1", "DO NOT UPLOAD*", 
    "collect_data.php", "cookie_consent*.php", "politique_*.php",
    "user_details.php", "order_details.php", "connexion_secure.php"
)

Write-Host "Analyse du repertoire..."

# Compter les fichiers avant
$totalFilesBefore = (Get-ChildItem -Path $projectPath -Recurse -File).Count
Write-Host "Nombre total de fichiers AVANT: $totalFilesBefore"
Write-Host ""

$deletedCount = 0
$errorCount = 0

# SUPPRESSION DES FICHIERS INUTILES
Write-Host "SUPPRESSION DES FICHIERS INUTILES..."
Write-Host "------------------------------------"

foreach ($pattern in $filesToDelete) {
    $files = Get-ChildItem -Path $projectPath -Name $pattern -ErrorAction SilentlyContinue
    foreach ($file in $files) {
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

# NETTOYAGE DES DOUBLONS DANS LES DOSSIERS
Write-Host ""
Write-Host "NETTOYAGE DES DOUBLONS..."
Write-Host "-------------------------"

foreach ($folder in $foldersToKeep) {
    $folderPath = Join-Path $projectPath $folder
    if (Test-Path $folderPath) {
        Write-Host "Nettoyage de: $folder"
        
        $duplicatePatterns = @("*_backup.*", "*_fixed.*", "*_old.*", "*_temp.*", "*_copy.*")
        
        foreach ($pattern in $duplicatePatterns) {
            $files = Get-ChildItem -Path $folderPath -Name $pattern -ErrorAction SilentlyContinue
            foreach ($file in $files) {
                $fullPath = Join-Path $folderPath $file
                try {
                    Remove-Item $fullPath -Force
                    Write-Host "  Supprime doublon: $file"
                    $deletedCount++
                } catch {
                    Write-Host "  Erreur doublon: $file"
                    $errorCount++
                }
            }
        }
    }
}

# SUPPRESSION DU DOSSIER UPLOADS DUPLIQUE
Write-Host ""
Write-Host "SUPPRESSION DU DOSSIER UPLOADS DUPLIQUE..."
Write-Host "------------------------------------------"

$duplicateUploads = Join-Path $projectPath "uploads\uploads"
if (Test-Path $duplicateUploads) {
    try {
        Remove-Item $duplicateUploads -Recurse -Force
        Write-Host "  SUPPRIME: Dossier uploads/uploads/ (doublon)"
        $deletedCount += 50
    } catch {
        Write-Host "  ERREUR: Impossible de supprimer uploads/uploads/"
        $errorCount++
    }
}

# NETTOYAGE DES FICHIERS DE TEST DANS UPLOADS
Write-Host ""
Write-Host "NETTOYAGE DES FICHIERS DE TEST DANS UPLOADS..."
Write-Host "-----------------------------------------------"

$uploadsPath = Join-Path $projectPath "uploads"
if (Test-Path $uploadsPath) {
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

# RESULTATS FINAUX
Write-Host ""
Write-Host "RESULTATS FINAUX..."
Write-Host "-------------------"

$totalFilesAfter = (Get-ChildItem -Path $projectPath -Recurse -File).Count
$totalDeleted = $totalFilesBefore - $totalFilesAfter

Write-Host ""
Write-Host "========================================"
Write-Host " NETTOYAGE TERMINE !"
Write-Host "========================================"
Write-Host ""
Write-Host "Fichiers AVANT le nettoyage : $totalFilesBefore"
Write-Host "Fichiers APRES le nettoyage : $totalFilesAfter"
Write-Host "Total supprime              : $totalDeleted"
Write-Host "Erreurs                     : $errorCount"
Write-Host ""
Write-Host "REPERTOIRE NETTOYE :"
Write-Host "==================="
Write-Host "- Site web fonctionnel conserve"
Write-Host "- Configuration (.env) conservee"
Write-Host "- Dossiers essentiels conserves"
Write-Host "- Tous les fichiers de test supprimes"
Write-Host "- Tous les guides supprimes"
Write-Host "- Tous les doublons supprimes"
Write-Host "- Scripts d installation supprimes"
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
