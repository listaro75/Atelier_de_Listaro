<?php
/**
 * Configuration SMTP pour atelierdelistaro.fr sur Raspberry Pi
 */

// =============================================================================
// CONFIGURATION SMTP - CHOISISSEZ UNE OPTION
// =============================================================================

// OPTION 1: Gmail avec domaine personnalisé (SOLUTION RECOMMANDÉE)
// Créez un mot de passe d'application Gmail: https://support.google.com/accounts/answer/185833
$email_config = [
    'method' => 'smtp',
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => 'lucien.dacunha@gmail.com', // Votre email Gmail
    'smtp_password' => 'xdiz iydk tisz jfop', // Mot de passe d'application (16 caractères)
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@atelierdelistaro.fr',
    'from_name' => 'Atelier de Listaro',
    'reply_to' => 'contact@atelierdelistaro.fr'
];

// OPTION 2: OVH (si votre domaine est chez OVH)
/*
$email_config = [
    'method' => 'smtp',
    'smtp_host' => 'ssl0.ovh.net',
    'smtp_port' => 587,
    'smtp_username' => 'contact@atelierdelistaro.fr',
    'smtp_password' => 'votre-mot-de-passe',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@atelierdelistaro.fr',
    'from_name' => 'Atelier de Listaro',
    'reply_to' => 'contact@atelierdelistaro.fr'
];
*/

// OPTION 3: Mailgun (gratuit jusqu'à 5000 emails/mois)
/*
$email_config = [
    'method' => 'smtp',
    'smtp_host' => 'smtp.mailgun.org',
    'smtp_port' => 587,
    'smtp_username' => 'postmaster@mg.atelierdelistaro.fr',
    'smtp_password' => 'votre-cle-mailgun',
    'smtp_encryption' => 'tls',
    'from_email' => 'noreply@atelierdelistaro.fr',
    'from_name' => 'Atelier de Listaro',
    'reply_to' => 'contact@atelierdelistaro.fr'
];
*/

// OPTION 4: Postfix local sur Raspberry Pi (plus avancé)
/*
$email_config = [
    'method' => 'local',
    'from_email' => 'noreply@atelierdelistaro.fr',
    'from_name' => 'Atelier de Listaro',
    'reply_to' => 'contact@atelierdelistaro.fr'
];
*/

// CONFIGURATION ACTIVE - Gmail SMTP (Configuration optimale)
// Configuration désactivée - utilise Gmail SMTP ci-dessus
/*
$email_config = [
    'method' => 'local',
    'from_email' => 'noreply@atelierdelistaro.fr',
    'from_name' => 'Atelier de Listaro',
    'reply_to' => 'contact@atelierdelistaro.fr'
];
*/

/*
 * ✅ CONFIGURATION GMAIL SMTP ACTIVE
 * 
 * Gmail SMTP configuré avec:
 * - Email: lucien.dacunha@gmail.com
 * - Mot de passe d'application configuré
 * - Expéditeur: noreply@atelierdelistaro.fr
 * 
 * AVANTAGES:
 * ✅ Livraison immédiate via Gmail
 * ✅ Réputation Gmail (pas de spam)
 * ✅ Pas de configuration DNS requise
 * ✅ Logs détaillés disponibles
 * 
 * TESTS DISPONIBLES:
 * - Interface web: test_email_raspberry.php
 * - URL directe: ?test=email&email=votre@email.com
 */

// =============================================================================
// FONCTIONS D'ENVOI
// =============================================================================

/**
 * Envoie un email via la méthode configurée
 */
function sendEmail($to, $subject, $body, $from = null, $method = null) {
    global $email_config;
    
    // Utiliser la méthode spécifiée ou celle par défaut
    $useMethod = $method ?: $email_config['method'];
    $fromEmail = $from ?: $email_config['from_email'];
    
    $headers = getEmailHeaders(
        $fromEmail, 
        $email_config['from_name'], 
        $email_config['reply_to']
    );
    
    switch ($useMethod) {
        case 'smtp':
        case 'gmail':
        case 'ovh':
        case 'mailgun':
            return sendEmailSMTP($to, $subject, $body, $headers, $email_config);
            
        case 'local':
        case 'postfix':
            return sendEmailLocal($to, $subject, $body, $headers);
            
        case 'test':
            return logEmailForTesting($to, $subject, $body);
            
        default:
            error_log("Méthode email non supportée: " . $useMethod);
            return ['success' => false, 'message' => "Méthode '$useMethod' non supportée"];
    }
}

/**
 * Envoi via SMTP externe
 */
function sendEmailSMTP($to, $subject, $body, $headers, $config) {
    require_once __DIR__ . '/smtp_mailer.php';
    
    try {
        $mailer = new SimpleMailer([
            'host' => $config['smtp_host'],
            'port' => $config['smtp_port'],
            'username' => $config['smtp_username'],
            'password' => $config['smtp_password'],
            'encryption' => $config['smtp_encryption']
        ]);
        
        // Utiliser sendMail avec les bons paramètres
        $result = $mailer->sendMail(
            $to,
            $subject,
            $body,
            $config['from_email'],
            $config['from_name'],
            $config['reply_to']
        );
        
        if ($result) {
            return ['success' => true, 'message' => 'Email envoyé avec succès via Gmail SMTP'];
        } else {
            return ['success' => false, 'message' => 'Échec envoi Gmail SMTP'];
        }
        
    } catch (Exception $e) {
        error_log("Erreur SMTP: " . $e->getMessage());
        return ['success' => false, 'message' => 'Erreur SMTP: ' . $e->getMessage()];
    }
}

/**
 * Envoi via postfix local (Raspberry Pi)
 */
function sendEmailLocal($to, $subject, $body, $headers) {
    // Log de debug
    error_log("Tentative d'envoi email local via Postfix");
    error_log("To: $to, Subject: $subject");
    
    // Tentative d'envoi
    $result = mail($to, $subject, $body, $headers);
    
    if ($result) {
        $message = "Email envoyé avec succès via Postfix local";
        error_log($message);
        return ['success' => true, 'message' => $message];
    } else {
        $message = "Échec de l'envoi via Postfix local. Vérifiez la configuration Postfix et les logs.";
        error_log($message);
        
        // Vérifications supplémentaires
        $diagnostics = [];
        
        // Vérification sendmail path
        $sendmail_path = ini_get('sendmail_path');
        if (empty($sendmail_path)) {
            $diagnostics[] = "sendmail_path vide dans PHP";
        } else {
            $diagnostics[] = "sendmail_path: $sendmail_path";
        }
        
        // Vérification si sendmail existe
        if (!file_exists('/usr/sbin/sendmail')) {
            $diagnostics[] = "/usr/sbin/sendmail n'existe pas";
        }
        
        // Vérification Postfix
        $postfix_status = shell_exec('systemctl is-active postfix 2>/dev/null');
        $diagnostics[] = "Postfix status: " . trim($postfix_status);
        
        return [
            'success' => false, 
            'message' => $message,
            'diagnostics' => $diagnostics
        ];
    }
}

/**
 * Mode test - log les emails au lieu de les envoyer
 */
function logEmailForTesting($to, $subject, $body) {
    $logEntry = date('Y-m-d H:i:s') . " - EMAIL TEST\n";
    $logEntry .= "To: $to\n";
    $logEntry .= "Subject: $subject\n";
    $logEntry .= "Body: " . substr(strip_tags($body), 0, 100) . "...\n";
    $logEntry .= "---\n\n";
    
    file_put_contents(__DIR__ . '/../logs/email_test.log', $logEntry, FILE_APPEND);
    error_log("Email de test envoyé à: $to");
    
    return true;
}

/**
 * Obtient les en-têtes email optimisés pour atelierdelistaro.fr
 */
function getEmailHeaders($from, $fromName, $replyTo) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: $fromName <$from>\r\n";
    $headers .= "Reply-To: $replyTo\r\n";
    $headers .= "Return-Path: $from\r\n";
    $headers .= "X-Mailer: Atelier de Listaro\r\n";
    $headers .= "X-Priority: 3\r\n";
    $headers .= "Message-ID: <" . time() . "." . uniqid() . "@atelierdelistaro.fr>\r\n";
    
    return $headers;
}

/**
 * Template email avec design Atelier de Listaro
 */
function getEmailTemplate($title, $content, $logoUrl = null) {
    $logoUrl = $logoUrl ?: 'https://atelierdelistaro.fr/assets/logo.png';
    
    return "
    <!DOCTYPE html>
    <html lang='fr'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; }
            .content { padding: 30px; }
            .footer { background: #ecf0f1; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            .btn { display: inline-block; background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 4px; margin: 10px 0; }
            .logo { max-height: 60px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>🎨 Atelier de Listaro</h1>
            </div>
            <div class='content'>
                $content
            </div>
            <div class='footer'>
                <p>© " . date('Y') . " Atelier de Listaro - Créations artisanales uniques</p>
                <p>📧 contact@atelierdelistaro.fr | 🌐 atelierdelistaro.fr</p>
            </div>
        </div>
    </body>
    </html>";
}

// Test de la configuration
if (isset($_GET['test']) && $_GET['test'] === 'email') {
    $testEmail = $_GET['email'] ?? 'test@example.com';
    $testSubject = "Test Email - Atelier de Listaro";
    $testBody = getEmailTemplate(
        $testSubject,
        "<h2>🧪 Test Email</h2>
         <p>Félicitations ! Votre configuration email fonctionne correctement.</p>
         <p>Ce message de test a été envoyé depuis votre Raspberry Pi.</p>
         <p><strong>Date:</strong> " . date('d/m/Y à H:i:s') . "</p>"
    );
    
    if (sendEmail($testEmail, $testSubject, $testBody)) {
        echo "✅ Email de test envoyé avec succès à: $testEmail";
    } else {
        echo "❌ Erreur lors de l'envoi de l'email de test";
    }
    exit;
}
?>
