<?php
    include_once('_db/connexion_DB.php');
    include_once('_functions/auth.php');
    session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Nos Prestations</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <style>
        .prestations-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .prestation-section {
            margin-bottom: 50px;
            background: #f9f9f9;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .prestation-section h2 {
            color: #1d1d1f;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1d1d1f;
            font-size: 1.8em;
        }

        .prestation-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .prestation-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            text-align: left;
            transition: transform 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        .prestation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .prestation-card h3 {
            color: #1d1d1f;
            margin-bottom: 20px;
            font-size: 1.4em;
        }

        .prestation-card ul {
            list-style-type: none;
            padding: 0;
        }

        .prestation-card li {
            margin-bottom: 12px;
            padding-left: 25px;
            position: relative;
            color: #666;
        }

        .prestation-card li:before {
            content: "•";
            color: #1d1d1f;
            position: absolute;
            left: 0;
            font-size: 1.2em;
        }

        .price {
            font-weight: 600;
            color: #1d1d1f;
            margin-top: 20px;
            font-size: 1.3em;
        }

        .contact-btn {
            display: inline-block;
            background: #1d1d1f;
            color: white;
            padding: 12px 25px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .contact-btn:hover {
            background: #333;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .prestation-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .prestation-section {
                padding: 20px;
                margin-bottom: 30px;
            }

            .prestation-card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <h1>Nos Prestations</h1>

        <div class="prestations-container">
            <!-- Section Développement Web -->
            <div class="prestation-section">
                <h2>Développement Web</h2>
                <div class="prestation-grid">
                    <div class="prestation-card">
                        <h3>Site Vitrine</h3>
                        <ul>
                            <li>Design personnalisé</li>
                            <li>Responsive (adapté mobile)</li>
                            <li>Jusqu'à 5 pages</li>
                            <li>Formulaire de contact</li>
                            <li>Optimisation SEO de base</li>
                        </ul>
                        <div class="price">À partir de 500€</div>
                        <a href="contact.php" class="contact-btn">Demander un devis</a>
                    </div>
                    
                    <div class="prestation-card">
                        <h3>Site E-commerce</h3>
                        <ul>
                            <li>Boutique en ligne complète</li>
                            <li>Gestion des produits</li>
                            <li>Système de paiement sécurisé</li>
                            <li>Panel d'administration</li>
                            <li>Formation utilisation</li>
                        </ul>
                        <div class="price">À partir de 1000€</div>
                        <a href="contact.php" class="contact-btn">Demander un devis</a>
                    </div>

                    <div class="prestation-card">
                        <h3>Site Sur Mesure</h3>
                        <ul>
                            <li>Fonctionnalités personnalisées</li>
                            <li>Base de données</li>
                            <li>API sur mesure</li>
                            <li>Sécurité renforcée</li>
                            <li>Support technique</li>
                        </ul>
                        <div class="price">Sur devis</div>
                        <a href="contact.php" class="contact-btn">Demander un devis</a>
                    </div>
                </div>
            </div>

            <!-- Section Figurines -->
            <div class="prestation-section">
                <h2>Figurines</h2>
                <div class="prestation-grid">
                    <div class="prestation-card">
                        <h3>Impression 3D</h3>
                        <ul>
                            <li>Impression haute qualité</li>
                            <li>Choix du matériau</li>
                            <li>Supports optimisés</li>
                            <li>Post-traitement</li>
                            <li>Différentes tailles disponibles</li>
                        </ul>
                        <div class="price">À partir de 30€</div>
                        <a href="contact.php" class="contact-btn">Commander</a>
                    </div>

                    <div class="prestation-card">
                        <h3>Peinture Standard</h3>
                        <ul>
                            <li>Sous-couche</li>
                            <li>Couleurs de base</li>
                            <li>Ombrages simples</li>
                            <li>Finition mate ou brillante</li>
                            <li>Socle basique</li>
                        </ul>
                        <div class="price">À partir de 40€</div>
                        <a href="contact.php" class="contact-btn">Commander</a>
                    </div>

                    <div class="prestation-card">
                        <h3>Peinture Premium</h3>
                        <ul>
                            <li>Sous-couche premium</li>
                            <li>Techniques avancées</li>
                            <li>Effets spéciaux</li>
                            <li>Socle personnalisé</li>
                            <li>Photos professionnelles</li>
                        </ul>
                        <div class="price">À partir de 80€</div>
                        <a href="contact.php" class="contact-btn">Commander</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 