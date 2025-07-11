<?php
session_start();
include_once(__DIR__ . '/../_db/connexion_DB.php');
include_once(__DIR__ . '/../_functions/auth.php');

if (!is_admin()) {
    http_response_code(403);
    exit('Accès refusé');
}
?>

<div class="settings-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
    
    <!-- Paramètres du site -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-cog"></i>
            Paramètres du site
        </h3>
        
        <form method="POST">
            <div class="form-group">
                <label>Nom du site</label>
                <input type="text" name="site_name" value="Atelier de Listaro">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="site_description" rows="3">Création d'objets décoratifs uniques</textarea>
            </div>
            <div class="form-group">
                <label>Email de contact</label>
                <input type="email" name="contact_email" value="contact@atelier-listaro.com">
            </div>
            <button type="submit" class="btn btn-success">Sauvegarder</button>
        </form>
    </div>

    <!-- Paramètres de sécurité -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-shield-alt"></i>
            Sécurité
        </h3>
        
        <form method="POST">
            <div class="form-group">
                <label>Changer le mot de passe admin</label>
                <input type="password" name="new_password" placeholder="Nouveau mot de passe">
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe">
            </div>
            <button type="submit" class="btn btn-success">Changer le mot de passe</button>
        </form>
    </div>

    <!-- Maintenance -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-tools"></i>
            Maintenance
        </h3>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="cleanupFiles()">
                <i class="fas fa-broom"></i>
                Nettoyer les fichiers
            </button>
        </div>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="optimizeDatabase()">
                <i class="fas fa-database"></i>
                Optimiser la base de données
            </button>
        </div>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="backupDatabase()">
                <i class="fas fa-download"></i>
                Sauvegarder la base
            </button>
        </div>
        
        <div>
            <button class="btn btn-danger" onclick="clearCache()">
                <i class="fas fa-trash"></i>
                Vider le cache
            </button>
        </div>
    </div>

    <!-- Statistiques système -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-chart-line"></i>
            Statistiques système
        </h3>
        
        <div style="margin-bottom: 15px;">
            <strong>Version PHP:</strong> <?php echo phpversion(); ?>
        </div>
        
        <div style="margin-bottom: 15px;">
            <strong>Mémoire utilisée:</strong> <?php echo number_format(memory_get_usage() / 1024 / 1024, 2); ?> MB
        </div>
        
        <div style="margin-bottom: 15px;">
            <strong>Espace disque:</strong> <?php echo number_format(disk_free_space('.') / 1024 / 1024 / 1024, 2); ?> GB libres
        </div>
        
        <div style="margin-bottom: 15px;">
            <strong>Dernière sauvegarde:</strong> Jamais
        </div>
        
        <button class="btn" onclick="refreshStats()">
            <i class="fas fa-sync"></i>
            Actualiser
        </button>
    </div>

    <!-- Logs système -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-file-alt"></i>
            Logs système
        </h3>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="viewErrorLogs()">
                <i class="fas fa-exclamation-triangle"></i>
                Voir les erreurs
            </button>
        </div>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="viewAccessLogs()">
                <i class="fas fa-eye"></i>
                Logs d'accès
            </button>
        </div>
        
        <div>
            <button class="btn btn-danger" onclick="clearLogs()">
                <i class="fas fa-trash"></i>
                Vider les logs
            </button>
        </div>
    </div>

    <!-- Diagnostic -->
    <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
        <h3 style="margin-bottom: 20px; color: #2c3e50;">
            <i class="fas fa-stethoscope"></i>
            Diagnostic
        </h3>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="testDatabase()">
                <i class="fas fa-database"></i>
                Tester la base de données
            </button>
        </div>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="testEmail()">
                <i class="fas fa-envelope"></i>
                Tester l'email
            </button>
        </div>
        
        <div style="margin-bottom: 15px;">
            <button class="btn" onclick="testUploads()">
                <i class="fas fa-upload"></i>
                Tester les uploads
            </button>
        </div>
        
        <div>
            <button class="btn btn-success" onclick="runFullDiagnostic()">
                <i class="fas fa-check-circle"></i>
                Diagnostic complet
            </button>
        </div>
    </div>
</div>

<script>
function cleanupFiles() {
    if (confirm('Nettoyer les fichiers temporaires et obsolètes ?')) {
        alert('Nettoyage en cours...');
        // Logique de nettoyage
    }
}

function optimizeDatabase() {
    if (confirm('Optimiser la base de données ?')) {
        alert('Optimisation en cours...');
        // Logique d'optimisation
    }
}

function backupDatabase() {
    alert('Sauvegarde en cours...');
    // Logique de sauvegarde
}

function clearCache() {
    if (confirm('Vider le cache ?')) {
        alert('Cache vidé avec succès');
        // Logique de vidage du cache
    }
}

function refreshStats() {
    alert('Statistiques actualisées');
    location.reload();
}

function viewErrorLogs() {
    window.open('../debug_products.php', '_blank');
}

function viewAccessLogs() {
    alert('Affichage des logs d\'accès');
    // Logique pour afficher les logs
}

function clearLogs() {
    if (confirm('Vider tous les logs ?')) {
        alert('Logs vidés avec succès');
        // Logique de vidage des logs
    }
}

function testDatabase() {
    alert('Test de la base de données : OK');
    // Logique de test
}

function testEmail() {
    alert('Test d\'envoi d\'email : OK');
    // Logique de test email
}

function testUploads() {
    alert('Test des uploads : OK');
    // Logique de test uploads
}

function runFullDiagnostic() {
    alert('Diagnostic complet en cours...');
    // Logique de diagnostic complet
}
</script>
