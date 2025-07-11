<?php
/**
 * Système d'envoi d'emails pour Atelier de Listaro
 * Compatible avec InfinityFree via SMTP et fallback mail()
 */

require_once __DIR__ . '/smtp_mailer.php';
require_once __DIR__ . '/../_config/env.php';
function sendOrderConfirmationEmail($order_id, $user_email) {
    global $DB;
    
    // Debug
    error_log("Tentative d'envoi d'email pour la commande #" . $order_id . " à " . $user_email);
    
    if (!isset($DB)) {
        require_once(__DIR__ . '/../_db/connexion_DB.php');
    }
    
    if (!$DB) {
        error_log("Erreur: Pas de connexion à la base de données dans mail.php");
        return false;
    }

    try {
        // Récupérer les détails de la commande avec une requête simplifiée
        $stmt = $DB->prepare("
            SELECT o.*, u.pseudo, u.mail,
            GROUP_CONCAT(
                CONCAT(
                    p.name, 
                    ' - Quantité: ', oi.quantity,
                    ' - Prix: ', FORMAT(oi.price, 2),
                    '€'
                ) SEPARATOR '\n'
            ) as products
            FROM orders o
            JOIN user u ON o.user_id = u.id
            JOIN order_items oi ON o.id = oi.order_id
            JOIN products p ON oi.product_id = p.id
            WHERE o.id = ?
            GROUP BY o.id
        ");
        
        $stmt->execute([$order_id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$order) {
            error_log("Erreur: Commande #" . $order_id . " non trouvée");
            return false;
        }

        $address = json_decode($order['shipping_address'], true);
        
        $subject = "Votre commande #" . $order_id . " est confirmée";
        $body = getOrderConfirmationTemplate($order, $address, $order_id);
        
        return EmailSender::send($user_email, $subject, $body);

    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email: " . $e->getMessage());
        return false;
    }
}

/**
 * Envoie un email de bienvenue à un nouvel utilisateur
 */
function sendWelcomeEmail($pseudo, $email) {
    $subject = "🎨 Bienvenue chez Atelier de Listaro !";
    $body = getWelcomeEmailTemplate($pseudo, $email);
    
    $success = EmailSender::send($email, $subject, $body);
    
    // Log du résultat
    if ($success) {
        error_log("Email de bienvenue envoyé avec succès à: " . $email . " pour l'utilisateur: " . $pseudo);
        return true;
    } else {
        error_log("Échec de l'envoi de l'email de bienvenue à: " . $email . " pour l'utilisateur: " . $pseudo);
        return false;
    }
}

/**
 * Template pour l'email de confirmation de commande
 */
function getOrderConfirmationTemplate($order, $address, $order_id) {
    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
            .content { padding: 20px; background: #f9f9f9; }
            .order-details { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; }
            .footer { background: #34495e; color: white; padding: 15px; text-align: center; font-size: 14px; }
            .total { font-size: 18px; font-weight: bold; color: #e74c3c; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎨 Atelier de Listaro</h1>
                <h2>Confirmation de commande #" . $order_id . "</h2>
            </div>
            <div class='content'>
                <p>Merci pour votre commande ! Nous avons bien reçu votre paiement.</p>
                
                <div class='order-details'>
                    <h3>📅 Détails de la commande :</h3>
                    <p><strong>Date :</strong> " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</p>
                    <p><strong>Statut :</strong> " . $order['status'] . "</p>
                </div>
                
                <div class='order-details'>
                    <h3>📦 Produits commandés :</h3>
                    <pre style='white-space: pre-wrap; font-family: Arial;'>" . $order['products'] . "</pre>
                </div>
                
                <div class='order-details'>
                    <h3>🚚 Livraison :</h3>
                    <p><strong>Méthode :</strong> " . htmlspecialchars($order['shipping_method']) . "</p>
                    <p><strong>Coût :</strong> " . number_format($order['shipping_cost'], 2) . "€</p>
                </div>
                
                <div class='order-details'>
                    <h3>📍 Adresse de livraison :</h3>
                    <p>
                        " . htmlspecialchars($address['firstname']) . " " . htmlspecialchars($address['lastname']) . "<br>
                        " . htmlspecialchars($address['address']) . "<br>
                        " . htmlspecialchars($address['postal']) . " " . htmlspecialchars($address['city']) . "
                    </p>
                </div>
                
                <div class='order-details' style='text-align: center;'>
                    <p class='total'>💰 Total : " . number_format($order['total_amount'], 2) . "€</p>
                </div>
                
                <p>Nous traiterons votre commande rapidement et vous tiendrons informé de son évolution.</p>
            </div>
            <div class='footer'>
                <p>&copy; " . date('Y') . " Atelier de Listaro - Créations artisanales uniques</p>
                <p>Pour toute question, contactez-nous à : " . ($_ENV['EMAIL_FROM'] ?? 'contact@atelierdelistaro.great-site.net') . "</p>
            </div>
        </div>
    </body>
    </html>";
}

/**
 * Template pour l'email de bienvenue
 */
function getWelcomeEmailTemplate($pseudo, $email) {
    $site_url = $_ENV['SITE_URL'] ?? 'http://atelierdelistaro.great-site.net';
    
    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
            .content { padding: 30px 20px; }
            .welcome-box { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; border-left: 4px solid #667eea; }
            .features { display: table; width: 100%; margin: 20px 0; }
            .feature { display: table-cell; text-align: center; padding: 15px; vertical-align: top; }
            .feature-icon { font-size: 24px; display: block; margin-bottom: 10px; }
            .button { display: inline-block; padding: 12px 25px; background: #667eea; color: white; text-decoration: none; border-radius: 25px; margin: 10px 5px; font-weight: bold; }
            .button.secondary { background: #6c757d; }
            .footer { background: #343a40; color: white; padding: 20px; text-align: center; font-size: 14px; }
            ul { text-align: left; }
            li { margin: 8px 0; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎨 Atelier de Listaro</h1>
                <h2>Bienvenue dans notre communauté !</h2>
            </div>
            
            <div class='content'>
                <div class='welcome-box'>
                    <h2>Salut " . htmlspecialchars($pseudo) . " ! 👋</h2>
                    <p>Félicitations ! Votre compte a été créé avec succès sur <strong>Atelier de Listaro</strong>.</p>
                    <p>Vous faites maintenant partie de notre communauté d'artistes et passionnés de figurines !</p>
                </div>
                
                <h3>🎯 Ce que vous pouvez faire maintenant :</h3>
                <div class='features'>
                    <div class='feature'>
                        <span class='feature-icon'>🛒</span>
                        <h4>Explorer la boutique</h4>
                        <p>Découvrez nos figurines uniques et accessoires</p>
                    </div>
                    <div class='feature'>
                        <span class='feature-icon'>🎨</span>
                        <h4>Nos prestations</h4>
                        <p>Services de peinture et personnalisation</p>
                    </div>
                    <div class='feature'>
                        <span class='feature-icon'>📸</span>
                        <h4>Portfolio</h4>
                        <p>Admirez nos créations artistiques</p>
                    </div>
                </div>
                
                <div class='welcome-box'>
                    <h3>🎁 Informations de votre compte :</h3>
                    <p><strong>Pseudo :</strong> " . htmlspecialchars($pseudo) . "</p>
                    <p><strong>Email :</strong> " . htmlspecialchars($email) . "</p>
                    <p><strong>Date d'inscription :</strong> " . date('d/m/Y à H:i') . "</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='" . $site_url . "/connexion.php' class='button'>
                        🔐 Se connecter maintenant
                    </a>
                    <a href='" . $site_url . "/shop.php' class='button secondary'>
                        🛒 Découvrir la boutique
                    </a>
                </div>
                
                <div class='welcome-box'>
                    <h3>💡 Conseils pour commencer :</h3>
                    <ul>
                        <li>✅ Complétez votre profil utilisateur</li>
                        <li>🔍 Explorez notre collection de figurines</li>
                        <li>🎨 Découvrez nos services de peinture</li>
                        <li>💬 N'hésitez pas à nous contacter pour toute question</li>
                        <li>📧 Surveillez vos emails pour nos offres spéciales</li>
                    </ul>
                </div>
            </div>
            
            <div class='footer'>
                <p>&copy; " . date('Y') . " Atelier de Listaro - Créations artisanales uniques</p>
                <p>Merci de faire confiance à notre savoir-faire artisanal !</p>
                <p>Pour toute question : " . ($_ENV['EMAIL_FROM'] ?? 'contact@atelierdelistaro.great-site.net') . "</p>
            </div>
        </div>
    </body>
    </html>";
}