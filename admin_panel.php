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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            z-index: 1000;
        }

        .sidebar-header {
            padding: 20px;
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            text-align: center;
        }

        .sidebar-header h2 {
            margin-bottom: 10px;
            font-size: 1.5em;
        }

        .sidebar-header .user-info {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .nav-menu {
            list-style: none;
            padding: 20px 0;
        }

        .nav-menu li {
            margin-bottom: 5px;
        }

        .nav-menu a {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: rgba(52, 152, 219, 0.1);
            border-left-color: #3498db;
            color: #3498db;
        }

        .nav-menu a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        /* Main content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 20px;
        }

        .content-header {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .content-header h1 {
            color: #333;
            margin-bottom: 10px;
        }

        .breadcrumb {
            color: #666;
            font-size: 0.9em;
        }

        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .icon {
            font-size: 3em;
            margin-bottom: 10px;
            color: #3498db;
        }

        .stat-card .number {
            font-size: 2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 0.9em;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .btn {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            font-size: 14px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }

        .btn-success {
            background: linear-gradient(135deg, #27ae60, #1e8449);
        }

        .btn-success:hover {
            box-shadow: 0 5px 15px rgba(39, 174, 96, 0.4);
        }

        .btn-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }

        .btn-danger:hover {
            box-shadow: 0 5px 15px rgba(231, 76, 60, 0.4);
        }

        .btn-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }

        .btn-warning:hover {
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.4);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        table th,
        table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        table tr:hover {
            background: #f8f9fa;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            display: none;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: black;
        }

        .image-preview {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 15px;
            padding: 15px;
            border: 2px dashed #ddd;
            border-radius: 8px;
            min-height: 120px;
            align-items: center;
            justify-content: flex-start;
        }

        .image-preview:empty::before {
            content: "Aucune image sélectionnée";
            color: #999;
            font-style: italic;
            width: 100%;
            text-align: center;
        }

        .image-item {
            position: relative;
            width: 120px;
            height: 120px;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease;
        }

        .image-item:hover {
            transform: scale(1.05);
        }

        .image-item img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-item .delete-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 50%;
            width: 24px;
            height: 24px;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s ease;
        }

        .image-item .delete-btn:hover {
            background: #c0392b;
        }

        .main-badge {
            position: absolute;
            bottom: 5px;
            left: 5px;
            background: linear-gradient(45deg, #27ae60, #2ecc71);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-sm {
            padding: 4px 8px;
            font-size: 11px;
            border-radius: 4px;
        }

        /* Style spéciaux pour le modal d'édition */
        .modal-content .image-preview {
            max-height: 200px;
            overflow-y: auto;
        }

        /* Amélioration du style des boutons */
        .image-item .btn {
            position: absolute;
            bottom: 5px;
            right: 5px;
            padding: 2px 6px;
            font-size: 10px;
            min-width: auto;
            white-space: nowrap;
        }
            font-size: 10px;
            border-radius: 0 5px 0 0;
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: relative;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .stats-cards {
                grid-template-columns: 1fr;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2>Administration</h2>
                <div class="user-info">
                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>
                </div>
            </div>
            
            <ul class="nav-menu">
                <li><a href="#" onclick="showSection('dashboard')" class="active">
                    <i class="fas fa-tachometer-alt"></i> Tableau de bord
                </a></li>
                <li><a href="#" onclick="showSection('products')">
                    <i class="fas fa-box"></i> Produits
                </a></li>
                <li><a href="#" onclick="showSection('prestations')">
                    <i class="fas fa-concierge-bell"></i> Prestations
                </a></li>
                <li><a href="#" onclick="showSection('users')">
                    <i class="fas fa-users"></i> Utilisateurs
                </a></li>
                <li><a href="#" onclick="showSection('orders')">
                    <i class="fas fa-shopping-cart"></i> Commandes
                </a></li>
                <li><a href="#" onclick="showSection('newsletter')">
                    <i class="fas fa-envelope"></i> Newsletter
                </a></li>
                <li><a href="#" onclick="showSection('rgpd')">
                    <i class="fas fa-shield-alt"></i> RGPD / Cookies
                </a></li>
                <li><a href="#" onclick="showSection('system')">
                    <i class="fas fa-server"></i> État du Système
                </a></li>
                <li><a href="#" onclick="showSection('settings')">
                    <i class="fas fa-cog"></i> Paramètres
                </a></li>
                <li><a href="deconnexion.php">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a></li>
            </ul>
        </div>

        <!-- Main content -->
        <div class="main-content">
            <div class="content-header">
                <h1 id="page-title">Tableau de bord</h1>
                <div class="breadcrumb">
                    <a href="#">Accueil</a> > <span id="page-breadcrumb">Tableau de bord</span>
                </div>
            </div>

            <!-- Dashboard Section -->
            <div id="dashboard-section" class="content-section active">
                <div class="stats-cards">
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-box"></i>
                        </div>
                        <div class="number"><?php echo $product_count; ?></div>
                        <div class="label">Produits</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="number"><?php echo $prestation_count; ?></div>
                        <div class="label">Prestations</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="number"><?php echo $user_count; ?></div>
                        <div class="label">Utilisateurs</div>
                    </div>
                    <div class="stat-card">
                        <div class="icon">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <div class="number"><?php echo $order_count; ?></div>
                        <div class="label">Commandes</div>
                    </div>
                </div>
                
                <!-- Section Statistiques Système Raspberry Pi -->
                <?php include 'admin_sections/system_stats.php'; ?>
                
                <h3>Actions rapides</h3>
                <div style="display: flex; gap: 15px; margin-top: 20px;">
                    <button class="btn btn-success" onclick="showSection('products')">
                        <i class="fas fa-plus"></i> Ajouter un produit
                    </button>
                    <button class="btn btn-success" onclick="showSection('prestations')">
                        <i class="fas fa-plus"></i> Ajouter une prestation
                    </button>
                    <button class="btn" onclick="showSection('orders')">
                        <i class="fas fa-eye"></i> Voir les commandes
                    </button>
                    <button class="btn" onclick="window.open('diagnostic_products.php', '_blank')">
                        <i class="fas fa-wrench"></i> Diagnostic système
                    </button>
                </div>
            </div>

            <!-- Products Section -->
            <div id="products-section" class="content-section">
                <div id="products-content">
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #3498db;"></i>
                        <p>Chargement des produits...</p>
                    </div>
                </div>
            </div>

            <!-- Prestations Section -->
            <div id="prestations-section" class="content-section">
                <div id="prestations-content">
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #3498db;"></i>
                        <p>Chargement des prestations...</p>
                    </div>
                </div>
            </div>

            <!-- Users Section -->
            <div id="users-section" class="content-section">
                <div id="users-content">
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #3498db;"></i>
                        <p>Chargement des utilisateurs...</p>
                    </div>
                </div>
            </div>

            <!-- Orders Section -->
            <div id="orders-section" class="content-section">
                <div id="orders-content">
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #3498db;"></i>
                        <p>Chargement des commandes...</p>
                    </div>
                </div>
            </div>

            <!-- Newsletter Section -->
            <div id="newsletter-section" class="content-section">
                <div id="newsletter-content">
                    <h3><i class="fas fa-envelope"></i> Gestion Newsletter</h3>
                    <div id="newsletter-alerts"></div>
                    
                    <!-- Statistiques Newsletter -->
                    <div class="stats-cards" style="margin-bottom: 30px;">
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-users"></i></div>
                            <div class="number" id="newsletter-subscribers">-</div>
                            <div class="label">Abonnés Newsletter</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-paper-plane"></i></div>
                            <div class="number" id="newsletter-sent">0</div>
                            <div class="label">Emails envoyés</div>
                        </div>
                        <div class="stat-card">
                            <div class="icon"><i class="fas fa-server"></i></div>
                            <div class="number">Gmail SMTP</div>
                            <div class="label">Service Email</div>
                        </div>
                    </div>

                    <!-- Formulaire d'envoi Newsletter -->
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
                        <h4><i class="fas fa-edit"></i> Envoyer une Newsletter</h4>
                        <form id="newsletter-form">
                            <div class="form-group">
                                <label for="newsletter-subject">Sujet de l'email :</label>
                                <input type="text" id="newsletter-subject" class="form-control" required 
                                       placeholder="Ex: Nouvelles créations - Atelier de Listaro">
                            </div>
                            
                            <div class="form-group">
                                <label for="newsletter-message">Message :</label>
                                <textarea id="newsletter-message" rows="8" class="form-control" required 
                                          placeholder="Votre message pour la newsletter..."></textarea>
                                <small class="form-text text-muted">
                                    Vous pouvez utiliser du HTML basique (balises &lt;p&gt;, &lt;strong&gt;, &lt;br&gt;, etc.)
                                </small>
                            </div>
                            
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" id="newsletter-test" style="margin-right: 10px;">
                                    Mode test (envoyer seulement à votre email)
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="send-newsletter-btn">
                                    <i class="fas fa-paper-plane"></i> Envoyer Newsletter
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="previewNewsletter()">
                                    <i class="fas fa-eye"></i> Aperçu
                                </button>
                                <button type="button" class="btn btn-info" onclick="testEmailConfig()">
                                    <i class="fas fa-cog"></i> Test Email
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Liste des abonnés -->
                    <div style="background: white; padding: 20px; border-radius: 10px;">
                        <h4><i class="fas fa-list"></i> Liste des Abonnés</h4>
                        <div id="newsletter-subscribers-list">
                            <div style="text-align: center; padding: 20px;">
                                <i class="fas fa-spinner fa-spin"></i> Chargement...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RGPD Section -->
            <div id="rgpd-section" class="content-section">
                <div id="rgpd-content">
                    <h3>Centre de contrôle RGPD</h3>
                    <p>Chargement du centre de contrôle RGPD...</p>
                </div>
            </div>

            <!-- System Stats Section -->
            <div id="system-section" class="content-section">
                <div id="system-content">
                    <h3>État du Système</h3>
                    <p>Chargement des statistiques système...</p>
                </div>
            </div>

            <!-- Settings Section -->
            <div id="settings-section" class="content-section">
                <h3>Paramètres du système</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nom du site</label>
                        <input type="text" value="Atelier de Listaro" readonly>
                    </div>
                    <div class="form-group">
                        <label>Email de contact</label>
                        <input type="email" value="contact@atelier-listaro.com" readonly>
                    </div>
                </div>
                <p><em>Paramètres en lecture seule pour cette version.</em></p>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentSection = 'dashboard';
        let sectionsLoaded = {
            dashboard: true,
            products: false,
            prestations: false,
            users: false,
            orders: false,
            newsletter: false,
            rgpd: false,
            system: false,
            settings: true
        };

        // Fonction pour afficher une section
        function showSection(sectionName) {
            // Masquer toutes les sections
            document.querySelectorAll('.content-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Retirer la classe active de tous les liens
            document.querySelectorAll('.nav-menu a').forEach(link => {
                link.classList.remove('active');
            });
            
            // Afficher la section demandée
            document.getElementById(sectionName + '-section').classList.add('active');
            
            // Activer le lien correspondant
            event.target.classList.add('active');
            
            // Mettre à jour le titre et le breadcrumb
            const titles = {
                dashboard: 'Tableau de bord',
                products: 'Gestion des produits',
                prestations: 'Gestion des prestations',
                users: 'Gestion des utilisateurs',
                orders: 'Gestion des commandes',
                newsletter: 'Gestion Newsletter',
                rgpd: 'Centre de contrôle RGPD',
                system: 'État du Système',
                settings: 'Paramètres'
            };
            
            document.getElementById('page-title').textContent = titles[sectionName];
            document.getElementById('page-breadcrumb').textContent = titles[sectionName];
            
            currentSection = sectionName;
            
            // Charger le contenu si ce n'est pas déjà fait
            if (!sectionsLoaded[sectionName]) {
                loadSectionContent(sectionName);
            }
        }

        // Fonction pour charger le contenu d'une section
        function loadSectionContent(sectionName) {
            const contentDiv = document.getElementById(sectionName + '-content');
            if (!contentDiv) return;
            
            // Afficher le loader
            contentDiv.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2em; color: #3498db;"></i>
                    <p>Chargement...</p>
                </div>
            `;
            
            // Charger le contenu via AJAX
            fetch(`admin_sections/${sectionName}.php`)
                .then(response => response.text())
                .then(html => {
                    contentDiv.innerHTML = html;
                    sectionsLoaded[sectionName] = true;
                    
                    // Exécuter les scripts contenus dans le HTML chargé
                    const scripts = contentDiv.querySelectorAll('script');
                    scripts.forEach(script => {
                        const newScript = document.createElement('script');
                        newScript.textContent = script.textContent;
                        document.head.appendChild(newScript);
                    });
                })
                .catch(error => {
                    console.error('Erreur lors du chargement:', error);
                    contentDiv.innerHTML = `
                        <div style="text-align: center; padding: 50px; color: #e74c3c;">
                            <i class="fas fa-exclamation-triangle" style="font-size: 2em; margin-bottom: 20px;"></i>
                            <p>Erreur lors du chargement de la section.</p>
                            <button class="btn" onclick="loadSectionContent('${sectionName}')">
                                <i class="fas fa-redo"></i> Réessayer
                            </button>
                        </div>
                    `;
                });
        }

        // Fonction utilitaire pour afficher une alerte
        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.textContent = message;
            alertDiv.style.display = 'block';
            
            // Insérer l'alerte en haut du contenu principal
            const mainContent = document.querySelector('.main-content');
            mainContent.insertBefore(alertDiv, mainContent.firstChild);
            
            // Supprimer l'alerte après 5 secondes
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Panel d\'administration chargé');
            
            // Charger automatiquement la section produits au démarrage
            setTimeout(() => {
                if (!sectionsLoaded.products) {
                    loadSectionContent('products');
                }
            }, 1000);
        });

        // ===== FONCTIONS NEWSLETTER =====
        
        // Charger les statistiques newsletter
        function loadNewsletterStats() {
            fetch('admin_actions/newsletter_stats.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('newsletter-subscribers').textContent = data.subscribers || 0;
                    document.getElementById('newsletter-sent').textContent = data.sent || 0;
                })
                .catch(error => console.error('Erreur stats newsletter:', error));
        }

        // Charger la liste des abonnés
        function loadNewsletterSubscribers() {
            fetch('admin_actions/newsletter_subscribers.php')
                .then(response => response.text())
                .then(html => {
                    document.getElementById('newsletter-subscribers-list').innerHTML = html;
                })
                .catch(error => {
                    document.getElementById('newsletter-subscribers-list').innerHTML = 
                        '<div class="alert alert-error">Erreur lors du chargement des abonnés</div>';
                });
        }

        // Envoyer la newsletter
        document.addEventListener('DOMContentLoaded', function() {
            const newsletterForm = document.getElementById('newsletter-form');
            if (newsletterForm) {
                newsletterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    
                    const subject = document.getElementById('newsletter-subject').value;
                    const message = document.getElementById('newsletter-message').value;
                    const testMode = document.getElementById('newsletter-test').checked;
                    const sendBtn = document.getElementById('send-newsletter-btn');
                    
                    if (!subject || !message) {
                        showNewsletterAlert('error', 'Veuillez remplir tous les champs');
                        return;
                    }
                    
                    // Confirmation
                    const confirmMessage = testMode ? 
                        'Envoyer un email de test ?' : 
                        'Êtes-vous sûr de vouloir envoyer cette newsletter à tous les abonnés ?';
                    
                    if (!confirm(confirmMessage)) return;
                    
                    // Désactiver le bouton et afficher le loader
                    sendBtn.disabled = true;
                    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Envoi en cours...';
                    
                    // Envoyer la newsletter
                    fetch('admin_actions/send_newsletter.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            subject: subject,
                            message: message,
                            test_mode: testMode
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNewsletterAlert('success', data.message);
                            if (!testMode) {
                                // Réinitialiser le formulaire
                                newsletterForm.reset();
                            }
                            // Recharger les stats
                            loadNewsletterStats();
                        } else {
                            showNewsletterAlert('error', data.message);
                        }
                    })
                    .catch(error => {
                        showNewsletterAlert('error', 'Erreur lors de l\'envoi: ' + error.message);
                    })
                    .finally(() => {
                        // Réactiver le bouton
                        sendBtn.disabled = false;
                        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Envoyer Newsletter';
                    });
                });
            }
        });

        // Afficher une alerte newsletter
        function showNewsletterAlert(type, message) {
            const alertsDiv = document.getElementById('newsletter-alerts');
            const alertClass = type === 'success' ? 'alert-success' : 'alert-error';
            
            alertsDiv.innerHTML = `
                <div class="alert ${alertClass}" style="display: block;">
                    ${message}
                </div>
            `;
            
            // Masquer l'alerte après 5 secondes
            setTimeout(() => {
                alertsDiv.innerHTML = '';
            }, 5000);
        }

        // Aperçu de la newsletter
        function previewNewsletter() {
            const subject = document.getElementById('newsletter-subject').value;
            const message = document.getElementById('newsletter-message').value;
            
            if (!subject || !message) {
                showNewsletterAlert('error', 'Veuillez remplir tous les champs pour l\'aperçu');
                return;
            }
            
            // Ouvrir l'aperçu dans une nouvelle fenêtre
            const previewWindow = window.open('', 'preview', 'width=800,height=600,scrollbars=yes');
            previewWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Aperçu - ${subject}</title>
                    <style>
                        body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; }
                        .email-preview { max-width: 600px; margin: 0 auto; border: 1px solid #ddd; }
                        .email-header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
                        .email-content { padding: 30px; }
                        .email-footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; color: #666; }
                    </style>
                </head>
                <body>
                    <h1>Aperçu Newsletter</h1>
                    <div class="email-preview">
                        <div class="email-header">
                            <h2>🎨 Atelier de Listaro</h2>
                            <h3>${subject}</h3>
                        </div>
                        <div class="email-content">
                            ${message}
                        </div>
                        <div class="email-footer">
                            <p>© 2025 Atelier de Listaro - Créations artisanales uniques</p>
                            <p>📧 contact@atelierdelistaro.fr | 🌐 atelierdelistaro.fr</p>
                        </div>
                    </div>
                </body>
                </html>
            `);
        }

        // Test de la configuration email
        function testEmailConfig() {
            fetch('test_gmail_smtp.php?email=lucien.dacunha@gmail.com')
                .then(response => response.text())
                .then(html => {
                    // Ouvrir les résultats dans une nouvelle fenêtre
                    const testWindow = window.open('', 'emailtest', 'width=900,height=700,scrollbars=yes');
                    testWindow.document.write(html);
                })
                .catch(error => {
                    showNewsletterAlert('error', 'Erreur lors du test email: ' + error.message);
                });
        }

        // Charger les données newsletter quand la section est affichée
        function loadNewsletterContent() {
            loadNewsletterStats();
            loadNewsletterSubscribers();
        }

        // Intercepter le chargement de la section newsletter
        const originalLoadSectionContent = loadSectionContent;
        function loadSectionContent(sectionName) {
            if (sectionName === 'newsletter') {
                sectionsLoaded.newsletter = true;
                loadNewsletterContent();
            } else {
                originalLoadSectionContent(sectionName);
            }
        }
    </script>
</body>
</html>
