<?php
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
        
        // Message simplifié
        $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                <h2>Confirmation de commande #" . $order_id . "</h2>
                <p>Merci pour votre commande !</p>
                
                <h3>Détails de la commande :</h3>
                <p>Date : " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</p>
                <p>Statut : " . $order['status'] . "</p>
                
                <h3>Produits :</h3>
                <pre>" . $order['products'] . "</pre>
                
                <h3>Livraison :</h3>
                <p>" . htmlspecialchars($order['shipping_method']) . " : " . number_format($order['shipping_cost'], 2) . "€</p>
                
                <h3>Adresse de livraison :</h3>
                <p>" . htmlspecialchars($address['firstname']) . " " . htmlspecialchars($address['lastname']) . "<br>
                " . htmlspecialchars($address['address']) . "<br>
                " . htmlspecialchars($address['postal']) . " " . htmlspecialchars($address['city']) . "</p>
                
                <h3>Total : " . number_format($order['total_amount'], 2) . "€</h3>
            </div>
        </body>
        </html>";

        // En-têtes basiques
        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";
        $headers .= 'From: lucien.dacunha@gmail.com' . "\r\n";

        // Envoi de l'email avec plus de debug
        $subject = "Votre commande #" . $order_id . " est confirmée";
        
        error_log("Tentative d'envoi d'email à " . $user_email);
        error_log("Sujet: " . $subject);
        error_log("Headers: " . $headers);
        
        $sent = mail($user_email, $subject, $message, $headers);
        
        if (!$sent) {
            error_log("Échec de l'envoi de l'email pour la commande #" . $order_id);
            return false;
        }

        error_log("Email envoyé avec succès pour la commande #" . $order_id);
        return true;

    } catch (Exception $e) {
        error_log("Erreur lors de l'envoi de l'email: " . $e->getMessage());
        return false;
    }
} 