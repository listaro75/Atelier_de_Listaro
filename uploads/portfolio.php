<?php
    include_once('_db/connexion_DB.php');
    include_once('_functions/auth.php');
    session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Portfolio</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <h1>Portfolio</h1>
        
        <!-- Section Programmation -->
        <section class="portfolio-section" id="programming">
            <h2>Programmation</h2>
            <div class="portfolio-grid">
                <!-- Projets C -->
                <div class="portfolio-category">
                    <h3>Projets en C</h3>
                    <div class="project-list">
                        <div class="project-card">
                            <h4>Projet 1</h4>
                            <p>Description du projet...</p>
                            <a href="https://github.com/votre-compte/projet1" target="_blank" class="github-link">
                                <i class="fab fa-github"></i> Voir sur GitHub
                            </a>
                        </div>
                        <!-- Ajoutez d'autres projets C ici -->
                    </div>
                </div>

                <!-- Projets Web -->
                <div class="portfolio-category">
                    <h3>Projets Web</h3>
                    <div class="project-list">
                        <div class="project-card">
                            <h4>Projet Web 1</h4>
                            <p>Description du projet...</p>
                            <a href="https://github.com/votre-compte/projetweb1" target="_blank" class="github-link">
                                <i class="fab fa-github"></i> Voir sur GitHub
                            </a>
                        </div>
                        <!-- Ajoutez d'autres projets Web ici -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Section Impression 3D -->
        <section class="portfolio-section" id="3dprinting">
            <h2>Impression 3D</h2>
            <div class="portfolio-grid">
                <div class="print-card">
                    <img src="path/to/3d-print1.jpg" alt="Impression 3D 1">
                    <div class="print-info">
                        <h4>Nom du projet</h4>
                        <p>Description de l'impression...</p>
                        <ul class="print-details">
                            <li><i class="fas fa-clock"></i> Temps d'impression: 2h30</li>
                            <li><i class="fas fa-layer-group"></i> Matériau: PLA</li>
                            <li><i class="fas fa-ruler-combined"></i> Dimensions: 10x10x10cm</li>
                        </ul>
                    </div>
                </div>
                <!-- Ajoutez d'autres impressions 3D ici -->
            </div>
        </section>

        <!-- Section Modélisation -->
        <section class="portfolio-section" id="modeling">
            <h2>Modélisation 3D</h2>
            <div class="portfolio-grid">
                <div class="model-card">
                    <div class="model-preview">
                        <img src="path/to/model1.jpg" alt="Modèle 3D 1">
                    </div>
                    <div class="model-info">
                        <h4>Nom du modèle</h4>
                        <p>Description du modèle...</p>
                        <div class="model-details">
                            <span><i class="fas fa-cube"></i> Logiciel: Fusion 360</span>
                            <span><i class="fas fa-calendar"></i> Date: Janvier 2024</span>
                        </div>
                    </div>
                </div>
                <!-- Ajoutez d'autres modèles 3D ici -->
            </div>
        </section>
    </div>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html>

