<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    header('Location: connexion.php?error=admin_required');
    exit();
}

// Récupérer quelques statistiques
try {
    $stmt = $DB->query("SELECT COUNT(*) as count FROM products");
    $product_count = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM prestations");
    $prestation_count = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM user");
    $user_count = $stmt->fetchColumn();
    
    $stmt = $DB->query("SELECT COUNT(*) as count FROM orders");
    $order_count = $stmt->fetchColumn();
} catch (Exception $e) {
    $product_count = 0;
    $prestation_count = 0;
    $user_count = 0;
    $order_count = 0;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administration - Atelier de Listaro</title>
    <link rel="stylesheet" href="_css/base.css">
    <link rel="stylesheet" href="_css/components.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-container { display: flex; min-height: 100vh; background: #f8f9fa; }
        .sidebar { width: 250px; background: #2c3e50; color: white; padding: 0; }
        .sidebar-header { padding: 20px; background: #34495e; text-align: center; }
        .sidebar-menu { list-style: none; padding: 0; margin: 0; }
        .sidebar-menu li { border-bottom: 1px solid #34495e; }
        .sidebar-menu a { 
            display: block; 
            padding: 15px 20px; 
            color: white; 
            text-decoration: none; 
            transition: background 0.3s;
        }
        .sidebar-menu a:hover, .sidebar-menu a.active { background: #3498db; }
        .main-content { flex: 1; padding: 20px; }
        .content-header { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section-content { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); min-height: 500px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); text-align: center; }
        .stat-number { font-size: 2em; font-weight: bold; color: #3498db; }
        .stat-label { color: #666; margin-top: 5px; }
        .loading { text-align: center; padding: 50px; color: #666; }
        .error { background: #ffe6e6; color: #d63031; padding: 15px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-tools"></i> Admin</h2>
            </div>
            <ul class="sidebar-menu">
                <li><a href="#" onclick="showSection('dashboard')" class="active"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a></li>
                <li><a href="#" onclick="showSection('products')"><i class="fas fa-box"></i> Produits</a></li>
                <li><a href="#" onclick="showSection('prestations')"><i class="fas fa-handshake"></i> Prestations</a></li>
                <li><a href="#" onclick="showSection('users')"><i class="fas fa-users"></i> Utilisateurs</a></li>
                <li><a href="#" onclick="showSection('orders')"><i class="fas fa-shopping-cart"></i> Commandes</a></li>
                <li><a href="#" onclick="showSection('settings')"><i class="fas fa-cog"></i> Paramètres</a></li>
                <li><a href="deconnexion.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="content-header">
                <h1 id="page-title">Tableau de bord</h1>
                <p id="page-breadcrumb">Accueil > Tableau de bord</p>
            </div>

            <!-- Dashboard -->
            <div id="dashboard-content" class="section-content">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $product_count; ?></div>
                        <div class="stat-label">Produits</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $prestation_count; ?></div>
                        <div class="stat-label">Prestations</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $user_count; ?></div>
                        <div class="stat-label">Utilisateurs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $order_count; ?></div>
                        <div class="stat-label">Commandes</div>
                    </div>
                </div>
                <h3>Bienvenue, <?php echo htmlspecialchars($_SESSION['pseudo'] ?? 'Admin'); ?> !</h3>
                <p>Utilisez le menu de gauche pour naviguer entre les différentes sections d'administration.</p>
            </div>

            <!-- Sections -->
            <div id="products-content" class="section-content" style="display: none;"></div>
            <div id="prestations-content" class="section-content" style="display: none;"></div>
            <div id="users-content" class="section-content" style="display: none;"></div>
            <div id="orders-content" class="section-content" style="display: none;"></div>
            <div id="settings-content" class="section-content" style="display: none;"></div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentSection = 'dashboard';
        let sectionsLoaded = {};

        // Configuration des sections
        const sectionConfig = {
            'dashboard': { title: 'Tableau de bord', breadcrumb: 'Accueil > Tableau de bord' },
            'products': { title: 'Gestion des produits', breadcrumb: 'Accueil > Gestion des produits' },
            'prestations': { title: 'Gestion des prestations', breadcrumb: 'Accueil > Gestion des prestations' },
            'users': { title: 'Gestion des utilisateurs', breadcrumb: 'Accueil > Gestion des utilisateurs' },
            'orders': { title: 'Gestion des commandes', breadcrumb: 'Accueil > Gestion des commandes' },
            'settings': { title: 'Paramètres', breadcrumb: 'Accueil > Paramètres' }
        };

        // Fonction pour afficher une section
        function showSection(sectionName) {
            // Masquer toutes les sections
            document.querySelectorAll('.section-content').forEach(section => {
                section.style.display = 'none';
            });

            // Mettre à jour le menu actif
            document.querySelectorAll('.sidebar-menu a').forEach(link => {
                link.classList.remove('active');
            });
            event.target.classList.add('active');

            // Mettre à jour le titre et breadcrumb
            const config = sectionConfig[sectionName];
            if (config) {
                document.getElementById('page-title').textContent = config.title;
                document.getElementById('page-breadcrumb').textContent = config.breadcrumb;
            }

            // Afficher la section demandée
            const contentDiv = document.getElementById(sectionName + '-content');
            if (contentDiv) {
                contentDiv.style.display = 'block';
                currentSection = sectionName;

                // Charger le contenu si pas encore chargé
                if (sectionName !== 'dashboard' && !sectionsLoaded[sectionName]) {
                    loadSectionContent(sectionName);
                }
            }
        }

        // Fonction pour charger le contenu d'une section
        function loadSectionContent(sectionName) {
            const contentDiv = document.getElementById(sectionName + '-content');
            if (!contentDiv) {
                console.error('Content div not found for section:', sectionName);
                return;
            }
            
            // Afficher le loader
            contentDiv.innerHTML = `
                <div class="loading">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #3498db;"></i>
                    <p>Chargement des ${sectionName}...</p>
                </div>
            `;
            
            // URL de la section
            const url = `admin_sections/${sectionName}.php`;
            console.log('Loading section:', url);
            
            // Charger le contenu via fetch
            fetch(url)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    return response.text();
                })
                .then(html => {
                    console.log('Content loaded, length:', html.length);
                    contentDiv.innerHTML = html;
                    sectionsLoaded[sectionName] = true;
                    
                    // Exécuter les scripts contenus dans le HTML chargé
                    const scripts = contentDiv.querySelectorAll('script');
                    scripts.forEach(script => {
                        try {
                            // Créer un nouveau script et l'exécuter
                            const newScript = document.createElement('script');
                            newScript.textContent = script.textContent;
                            document.head.appendChild(newScript);
                            document.head.removeChild(newScript);
                        } catch (error) {
                            console.error('Erreur lors de l\'exécution du script:', error);
                        }
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement:', error);
                    contentDiv.innerHTML = `
                        <div class="error">
                            <h3><i class="fas fa-exclamation-triangle"></i> Erreur de chargement</h3>
                            <p><strong>Erreur:</strong> ${error.message}</p>
                            <p><strong>URL:</strong> ${url}</p>
                            <button onclick="loadSectionContent('${sectionName}')" style="margin-top: 10px; padding: 10px 15px; background: #3498db; color: white; border: none; border-radius: 5px; cursor: pointer;">
                                <i class="fas fa-redo"></i> Réessayer
                            </button>
                            <a href="${url}" target="_blank" style="margin-left: 10px; padding: 10px 15px; background: #e74c3c; color: white; text-decoration: none; border-radius: 5px;">
                                <i class="fas fa-external-link-alt"></i> Ouvrir dans un nouvel onglet
                            </a>
                        </div>
                    `;
                });
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Admin panel loaded');
            // Section dashboard déjà affichée par défaut
        });
    </script>
</body>
</html>
