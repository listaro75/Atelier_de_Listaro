<?php
/**
 * Configuration des emails pour Atelier de Listaro
 */

// Configuration SMTP (pour un envoi plus fiable)
// Décommentez et configurez si vous voulez utiliser SMTP au lieu de la fonction mail() native
/*
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'votre-email@gmail.com');
define('SMTP_PASSWORD', 'votre-mot-de-passe-app');
define('SMTP_ENCRYPTION', 'tls');
*/

// Configuration générale des emails
define('EMAIL_FROM', 'noreply@atelierdelistaro.great-site.net');
define('EMAIL_FROM_NAME', 'Atelier de Listaro');
define('EMAIL_REPLY_TO', 'contact@atelierdelistaro.great-site.net');
define('SITE_URL', 'http://atelierdelistaro.great-site.net');
define('SITE_NAME', 'Atelier de Listaro');

/**
 * Fonction utilitaire pour obtenir les en-têtes email standard
 */
function getEmailHeaders($from = null, $fromName = null, $replyTo = null) {
    $from = $from ?: EMAIL_FROM;
    $fromName = $fromName ?: EMAIL_FROM_NAME;
    $replyTo = $replyTo ?: EMAIL_REPLY_TO;
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: $fromName <$from>" . "\r\n";
    $headers .= "Reply-To: $replyTo" . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 3" . "\r\n";
    
    return $headers;
}

/**
 * Template de base pour les emails
 */
function getEmailTemplate($title, $content, $footerText = null) {
    $footerText = $footerText ?: "Merci de faire confiance à notre savoir-faire artisanal !";
    
    return "
    <html>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <style>
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                line-height: 1.6; 
                color: #333; 
                margin: 0; 
                padding: 0; 
                background-color: #f4f4f4; 
            }
            .email-container { 
                max-width: 600px; 
                margin: 20px auto; 
                background: white; 
                border-radius: 12px; 
                overflow: hidden; 
                box-shadow: 0 4px 6px rgba(0,0,0,0.1); 
            }
            .header { 
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                color: white; 
                padding: 40px 30px; 
                text-align: center; 
            }
            .header h1 { 
                margin: 0; 
                font-size: 28px; 
                font-weight: 300; 
            }
            .content { 
                padding: 40px 30px; 
            }
            .welcome-box { 
                background: #f8f9ff; 
                padding: 25px; 
                border-radius: 8px; 
                margin: 25px 0; 
                border-left: 4px solid #667eea; 
            }
            .button { 
                display: inline-block; 
                padding: 14px 28px; 
                background: #667eea; 
                color: white; 
                text-decoration: none; 
                border-radius: 6px; 
                margin: 10px 5px; 
                font-weight: 500;
                transition: background 0.3s ease;
            }
            .button:hover { 
                background: #5a6fd8; 
            }
            .button.secondary { 
                background: #28a745; 
            }
            .button.secondary:hover { 
                background: #218838; 
            }
            .footer { 
                background: #f8f9fa; 
                text-align: center; 
                color: #666; 
                font-size: 14px; 
                padding: 30px; 
                border-top: 1px solid #dee2e6;
            }
            .features { 
                display: flex; 
                flex-wrap: wrap; 
                justify-content: space-around; 
                margin: 30px 0; 
            }
            .feature { 
                text-align: center; 
                flex: 1; 
                min-width: 150px; 
                margin: 15px 10px; 
                padding: 20px; 
                background: #f8f9ff; 
                border-radius: 8px;
            }
            .feature-icon { 
                font-size: 32px; 
                margin-bottom: 15px; 
                display: block; 
            }
            .feature h4 { 
                margin: 10px 0; 
                color: #667eea; 
            }
            @media (max-width: 600px) {
                .email-container { margin: 10px; }
                .header, .content { padding: 20px; }
                .features { flex-direction: column; }
                .feature { min-width: auto; margin: 10px 0; }
            }
        </style>
    </head>
    <body>
        <div class='email-container'>
            <div class='header'>
                <h1>🎨 " . SITE_NAME . "</h1>
                <p>" . htmlspecialchars($title) . "</p>
            </div>
            
            <div class='content'>
                " . $content . "
            </div>
            
            <div class='footer'>
                <p><strong>🎨 " . SITE_NAME . "</strong> - Votre passion, notre art</p>
                <p>" . htmlspecialchars($footerText) . "</p>
                <p><small>Cet email a été envoyé automatiquement. Ne pas répondre à cette adresse.</small></p>
                <p><small>Site web: <a href='" . SITE_URL . "' style='color: #667eea;'>" . SITE_URL . "</a></small></p>
            </div>
        </div>
    </body>
    </html>";
}
?>
