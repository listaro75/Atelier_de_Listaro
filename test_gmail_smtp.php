<?php
/**
 * Test rapide Gmail SMTP - Atelier de Listaro
 */

// Inclure la configuration email
require_once '_functions/email_config_raspberry.php';

// Style simple
echo '<style>
body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
.success { background: #d4edda; border: 1px solid #c3e6cb; padding: 15px; border-radius: 5px; color: #155724; }
.error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; color: #721c24; }
.info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; color: #0c5460; }
</style>';

echo '<h1>🧪 Test Gmail SMTP - Atelier de Listaro</h1>';

// Test automatique si email fourni en GET
if (isset($_GET['email']) && !empty($_GET['email'])) {
    $test_email = $_GET['email'];
    
    echo '<div class="info">';
    echo '<h3>🚀 Test d\'envoi en cours...</h3>';
    echo '<p><strong>Destinataire:</strong> ' . htmlspecialchars($test_email) . '</p>';
    echo '<p><strong>Méthode:</strong> Gmail SMTP</p>';
    echo '</div>';
    
    $subject = 'Test Gmail SMTP - Atelier de Listaro ✅';
    $message = getEmailTemplate(
        $subject,
        "<h2>🎉 Félicitations !</h2>
         <p>Votre configuration Gmail SMTP fonctionne parfaitement !</p>
         <p>✅ <strong>Email envoyé avec succès</strong> depuis votre Raspberry Pi via Gmail SMTP.</p>
         
         <h3>📊 Détails techniques :</h3>
         <ul>
             <li><strong>Serveur:</strong> " . gethostname() . "</li>
             <li><strong>Date:</strong> " . date('d/m/Y à H:i:s') . "</li>
             <li><strong>Méthode:</strong> Gmail SMTP (smtp.gmail.com:587)</li>
             <li><strong>Sécurité:</strong> TLS</li>
             <li><strong>Expéditeur:</strong> noreply@atelierdelistaro.fr</li>
         </ul>
         
         <p style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0;'>
             <strong>🔧 Configuration réussie !</strong><br>
             Votre système d'envoi d'emails est maintenant opérationnel pour :
         </p>
         <ul>
             <li>📧 Confirmations de commandes</li>
             <li>👤 Notifications utilisateurs</li>
             <li>📝 Messages de contact</li>
             <li>🔐 Réinitialisations de mot de passe</li>
         </ul>"
    );
    
    try {
        $result = sendEmail($test_email, $subject, $message);
        
        if (is_array($result) && isset($result['success'])) {
            if ($result['success']) {
                echo '<div class="success">';
                echo '<h3>✅ Email envoyé avec succès !</h3>';
                echo '<p>' . htmlspecialchars($result['message']) . '</p>';
                echo '<p><strong>📧 Vérifiez votre boîte email :</strong> ' . htmlspecialchars($test_email) . '</p>';
                echo '<p><em>L\'email peut arriver dans les dossiers spam/promotions, vérifiez-les aussi.</em></p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<h3>❌ Erreur lors de l\'envoi</h3>';
                echo '<p>' . htmlspecialchars($result['message']) . '</p>';
                echo '</div>';
            }
        } else {
            // Compatibilité ancienne version
            if ($result) {
                echo '<div class="success">';
                echo '<h3>✅ Email envoyé avec succès !</h3>';
                echo '<p>Vérifiez votre boîte email : ' . htmlspecialchars($test_email) . '</p>';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '<h3>❌ Erreur lors de l\'envoi</h3>';
                echo '<p>La fonction a retourné FALSE</p>';
                echo '</div>';
            }
        }
        
    } catch (Exception $e) {
        echo '<div class="error">';
        echo '<h3>❌ Exception attrapée</h3>';
        echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '</div>';
    }
    
} else {
    // Formulaire de test
    echo '<div class="info">';
    echo '<h3>📧 Test d\'envoi d\'email Gmail SMTP</h3>';
    echo '<form method="GET">';
    echo '<label for="email">Email de test :</label><br>';
    echo '<input type="email" name="email" id="email" required placeholder="votre@email.com" style="width: 300px; padding: 8px; margin: 10px 0;">';
    echo '<br><button type="submit" style="background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">🚀 Envoyer test email</button>';
    echo '</form>';
    echo '</div>';
}

// Informations de configuration
echo '<div class="info">';
echo '<h3>⚙️ Configuration actuelle</h3>';
echo '<p><strong>Méthode :</strong> ' . htmlspecialchars($email_config['method']) . '</p>';
if ($email_config['method'] === 'smtp') {
    echo '<p><strong>Serveur SMTP :</strong> ' . htmlspecialchars($email_config['smtp_host']) . ':' . $email_config['smtp_port'] . '</p>';
    echo '<p><strong>Utilisateur :</strong> ' . htmlspecialchars($email_config['smtp_username']) . '</p>';
    echo '<p><strong>Chiffrement :</strong> ' . htmlspecialchars($email_config['smtp_encryption']) . '</p>';
}
echo '<p><strong>Expéditeur :</strong> ' . htmlspecialchars($email_config['from_name']) . ' &lt;' . htmlspecialchars($email_config['from_email']) . '&gt;</p>';
echo '<p><strong>Répondre à :</strong> ' . htmlspecialchars($email_config['reply_to']) . '</p>';
echo '</div>';

echo '<div class="info">';
echo '<h3>🔗 Liens utiles</h3>';
echo '<ul>';
echo '<li><a href="test_email_raspberry.php">Interface de test complète</a></li>';
echo '<li><a href="fix_email_raspberry.php">Diagnostic et correction</a></li>';
echo '<li><a href="?email=lucien.dacunha@gmail.com">Test rapide vers votre Gmail</a></li>';
echo '</ul>';
echo '</div>';

?>
