# NETTOYAGE COMPLET DU REPERTOIRE
# Supprime tous les fichiers de test, debug, guides
# Garde seulement les fichiers essentiels du site web

Write-Host "Nettoyage complet du repertoire Atelier de Listaro..."

$projectPath = "C:\Users\lucie\site\Atelier_de_listaro"

# Fichiers a SUPPRIMER
$filesToDelete = @(
    "test_*.html",
    "test_*.php",
    "diagnostic_*.php",
    "install*.sh",
    "fix_*.sh", 
    "configure_*.sh",
    "cleanup*.sh",
    "*.ps1",
    "GUIDE_*.md",
    "INSTALLATION_*.md", 
    "CONFIGURATION_*.md",
    "CHECKLIST.md",
    "ACTIONS_*.md",
    "EMAIL_SYSTEM*.md",
    "FILEZILLA_*.md",
    "FINALISATION_*.md",
    "SOLUTION_*.md",
    "SMTP_*.md",
    "README_*.md",
    "*.sql",
    "database_*.sql",
    "atelier_*.sql",
    "apache_config_*.conf",
    "DO NOT UPLOAD*",
    "cleanup.php"
)

Write-Host "Fichiers qui seront supprimes :"
$totalFiles = 0
foreach ($pattern in $filesToDelete) {
    $files = Get-ChildItem -Path $projectPath -Name $pattern -ErrorAction SilentlyContinue
    foreach ($file in $files) {
        Write-Host "  - $file"
        $totalFiles++
    }
}

Write-Host ""
Write-Host "Total : $totalFiles fichiers a supprimer"
Write-Host ""
Write-Host "Fichiers qui seront CONSERVES :"
Write-Host "==============================="

$essentialFiles = @(
    "index.php", "shop.php", "cart.php", "checkout.php", 
    "confirmation.php", "connexion.php", "inscription.php",
    "administrateur.php", "admin_*.php", "my_orders.php",
    "portfolio.php", "prestation.php", "product_details.php",
    "profile.php", ".env"
)

foreach ($file in $essentialFiles) {
    if (Test-Path (Join-Path $projectPath $file)) {
        Write-Host "- $file"
    }
}

Write-Host "- _css/ (dossier complet)"
Write-Host "- _functions/ (dossier complet)" 
Write-Host "- _config/ (dossier complet)"
Write-Host "- _db/ (dossier complet)"
Write-Host "- _head/ (dossier complet)"
Write-Host "- _footer/ (dossier complet)"
Write-Host "- _menu/ (dossier complet)"
Write-Host "- ajax/ (dossier complet)"
Write-Host "- uploads/ (dossier complet)"
Write-Host "- stripe-php/ (dossier complet)"

Write-Host ""
$confirmation = Read-Host "ATTENTION : Voulez-vous vraiment supprimer tous ces fichiers ? (oui/non)"

if ($confirmation -eq "oui" -or $confirmation -eq "o") {
    Write-Host ""
    Write-Host "Suppression en cours..."
    
    $deletedCount = 0
    foreach ($pattern in $filesToDelete) {
        $files = Get-ChildItem -Path $projectPath -Name $pattern -ErrorAction SilentlyContinue
        foreach ($file in $files) {
            $fullPath = Join-Path $projectPath $file
            try {
                Remove-Item $fullPath -Force
                Write-Host "Supprime : $file"
                $deletedCount++
            } catch {
                Write-Host "Erreur lors de la suppression de : $file"
            }
        }
    }
    
    Write-Host ""
    Write-Host "Nettoyage termine !"
    Write-Host "Total : $deletedCount fichiers supprimes"
    Write-Host ""
    Write-Host "Votre repertoire est maintenant propre :"
    Write-Host "======================================="
    Write-Host "- Site web complet fonctionnel"
    Write-Host "- Configuration (.env)"
    Write-Host "- Tous les dossiers essentiels"
    Write-Host "- Plus de fichiers de test/debug"
    Write-Host ""
    Write-Host "Le site continue de fonctionner sur le Raspberry Pi !"
    
} else {
    Write-Host "Nettoyage annule."
    Write-Host "Aucun fichier supprime."
}

Write-Host ""
Write-Host "Appuyez sur Entree pour fermer..."
Read-Host
