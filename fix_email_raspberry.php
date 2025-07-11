<?php
/**
 * Correction automatique des problèmes email - Raspberry Pi
 * Atelier de Listaro
 */

// Sécurité - seulement accessible en local
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

if (!in_array($client_ip, $allowed_ips) && !preg_match('/^192\.168\./', $client_ip)) {
    http_response_code(403);
    exit('Accès interdit - Script de maintenance locale uniquement');
}

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correction Email - Raspberry Pi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007cba;
            padding-bottom: 10px;
        }
        .status-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .success { background: #d4edda; border-color: #28a745; color: #155724; }
        .warning { background: #fff3cd; border-color: #ffc107; color: #856404; }
        .error { background: #f8d7da; border-color: #dc3545; color: #721c24; }
        .info { background: #d1ecf1; border-color: #17a2b8; color: #0c5460; }
        .action-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        button {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover { background: #005a8b; }
        .danger { background: #dc3545; }
        .danger:hover { background: #c82333; }
        .logs {
            background: #2d3748;
            color: #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Correction Email - Raspberry Pi</h1>
        
        <?php
        // Gestion des actions
        if (isset($_POST['action'])) {
            $action = $_POST['action'];
            
            switch ($action) {
                case 'fix_php_config':
                    echo '<div class="status-box info"><h3>🔧 Correction configuration PHP...</h3>';
                    
                    // Vérifier et corriger sendmail_path
                    $current_path = ini_get('sendmail_path');
                    echo '<p>sendmail_path actuel: ' . htmlspecialchars($current_path) . '</p>';
                    
                    if (empty($current_path) || $current_path === '/usr/sbin/sendmail -t -i') {
                        echo '<p>✅ Configuration PHP correcte</p>';
                    } else {
                        echo '<p>⚠️ Configuration à vérifier</p>';
                    }
                    echo '</div>';
                    break;
                    
                case 'test_sendmail_direct':
                    $test_email = $_POST['test_email'] ?? '';
                    if (!empty($test_email)) {
                        echo '<div class="status-box info"><h3>🧪 Test sendmail direct...</h3>';
                        
                        $subject = 'Test Direct Sendmail - Raspberry Pi';
                        $message = "Test d'envoi direct via sendmail\n\n";
                        $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
                        $message .= "Hostname: " . gethostname() . "\n";
                        $message .= "IP: " . $_SERVER['SERVER_ADDR'] ?? 'unknown' . "\n";
                        
                        $command = 'echo "Subject: ' . $subject . '\nFrom: noreply@atelierdelistaro.fr\nTo: ' . $test_email . '\n\n' . $message . '" | /usr/sbin/sendmail -t 2>&1';
                        
                        $output = shell_exec($command);
                        
                        echo '<p><strong>Commande:</strong> ' . htmlspecialchars($command) . '</p>';
                        echo '<p><strong>Sortie:</strong></p>';
                        echo '<div class="logs">' . htmlspecialchars($output ?: 'Aucune sortie') . '</div>';
                        
                        // Vérifier la queue
                        $queue = shell_exec('mailq 2>&1');
                        echo '<p><strong>Queue après envoi:</strong></p>';
                        echo '<div class="logs">' . htmlspecialchars($queue) . '</div>';
                        
                        echo '</div>';
                    }
                    break;
                    
                case 'restart_postfix':
                    echo '<div class="status-box info"><h3>🔄 Redémarrage Postfix...</h3>';
                    
                    $output = shell_exec('sudo systemctl restart postfix 2>&1');
                    echo '<div class="logs">' . htmlspecialchars($output ?: 'Redémarrage effectué') . '</div>';
                    
                    sleep(2);
                    
                    $status = shell_exec('systemctl is-active postfix 2>&1');
                    if (trim($status) === 'active') {
                        echo '<div class="status-box success">✅ Postfix redémarré avec succès</div>';
                    } else {
                        echo '<div class="status-box error">❌ Problème lors du redémarrage: ' . htmlspecialchars($status) . '</div>';
                    }
                    break;
                    
                case 'check_logs':
                    echo '<div class="status-box info"><h3>📋 Vérification des logs...</h3>';
                    
                    $logs = shell_exec('tail -50 /var/log/mail.log 2>/dev/null || tail -50 /var/log/maillog 2>/dev/null || echo "Logs non accessibles"');
                    echo '<div class="logs">' . htmlspecialchars($logs) . '</div>';
                    echo '</div>';
                    break;
                    
                case 'test_php_mail':
                    $test_email = $_POST['test_email'] ?? '';
                    if (!empty($test_email)) {
                        echo '<div class="status-box info"><h3>🐘 Test PHP mail()...</h3>';
                        
                        $to = $test_email;
                        $subject = 'Test PHP mail() - Raspberry Pi';
                        $message = "Test d'envoi via PHP mail()\n\n";
                        $message .= "Date: " . date('Y-m-d H:i:s') . "\n";
                        $message .= "Serveur: " . gethostname() . "\n";
                        $message .= "PHP Version: " . PHP_VERSION . "\n";
                        
                        $headers = "From: noreply@atelierdelistaro.fr\r\n";
                        $headers .= "Reply-To: contact@atelierdelistaro.fr\r\n";
                        $headers .= "X-Mailer: PHP/" . PHP_VERSION . "\r\n";
                        
                        error_log("Test PHP mail() vers: $to");
                        
                        $result = mail($to, $subject, $message, $headers);
                        
                        if ($result) {
                            echo '<div class="status-box success">✅ mail() retourne TRUE</div>';
                        } else {
                            echo '<div class="status-box error">❌ mail() retourne FALSE</div>';
                        }
                        
                        // Vérifier les logs PHP
                        $php_errors = error_get_last();
                        if ($php_errors) {
                            echo '<p><strong>Dernière erreur PHP:</strong></p>';
                            echo '<div class="logs">' . htmlspecialchars(print_r($php_errors, true)) . '</div>';
                        }
                        
                        echo '</div>';
                    }
                    break;
            }
        }
        
        // Diagnostic actuel
        echo '<div class="action-section">';
        echo '<h2>📊 État actuel du système</h2>';
        
        // PHP mail
        if (function_exists('mail')) {
            echo '<div class="status-box success">✅ Fonction PHP mail() disponible</div>';
        } else {
            echo '<div class="status-box error">❌ Fonction PHP mail() non disponible</div>';
        }
        
        // sendmail path
        $sendmail_path = ini_get('sendmail_path');
        echo '<p><strong>sendmail_path:</strong> ' . htmlspecialchars($sendmail_path) . '</p>';
        
        // Postfix
        $postfix_status = shell_exec('systemctl is-active postfix 2>/dev/null');
        if (trim($postfix_status) == 'active') {
            echo '<div class="status-box success">✅ Postfix actif</div>';
        } else {
            echo '<div class="status-box error">❌ Postfix non actif: ' . htmlspecialchars($postfix_status) . '</div>';
        }
        
        // Sendmail binaire
        if (file_exists('/usr/sbin/sendmail')) {
            echo '<div class="status-box success">✅ /usr/sbin/sendmail existe</div>';
        } else {
            echo '<div class="status-box error">❌ /usr/sbin/sendmail introuvable</div>';
        }
        
        echo '</div>';
        ?>
        
        <!-- Actions correctives -->
        <div class="action-section">
            <h2>🛠️ Actions correctives</h2>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="restart_postfix">
                <button type="submit" class="danger">🔄 Redémarrer Postfix</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="check_logs">
                <button type="submit">📋 Vérifier logs</button>
            </form>
            
            <form method="POST" style="display: inline;">
                <input type="hidden" name="action" value="fix_php_config">
                <button type="submit">🔧 Vérifier config PHP</button>
            </form>
        </div>
        
        <!-- Tests d'envoi -->
        <div class="action-section">
            <h2>🧪 Tests d'envoi</h2>
            
            <form method="POST">
                <div style="margin: 10px 0;">
                    <label for="test_email">Email de test:</label>
                    <input type="email" name="test_email" id="test_email" required 
                           value="<?= isset($_POST['test_email']) ? htmlspecialchars($_POST['test_email']) : '' ?>" 
                           style="width: 300px; padding: 5px;">
                </div>
                
                <button type="submit" name="action" value="test_sendmail_direct">📧 Test sendmail direct</button>
                <button type="submit" name="action" value="test_php_mail">🐘 Test PHP mail()</button>
            </form>
        </div>
        
        <!-- Instructions -->
        <div class="action-section">
            <h2>📖 Instructions de dépannage</h2>
            
            <div class="status-box info">
                <h3>💡 Problèmes courants et solutions</h3>
                <ul>
                    <li><strong>Postfix inactif:</strong> Utilisez le bouton "Redémarrer Postfix"</li>
                    <li><strong>Logs non accessibles:</strong> Vérifiez les permissions avec <code>sudo chmod 644 /var/log/mail.log</code></li>
                    <li><strong>Hostname incorrect:</strong> Configurez avec <code>sudo hostnamectl set-hostname mail.atelierdelistaro.fr</code></li>
                    <li><strong>DNS non configuré:</strong> Ajoutez les enregistrements MX, A et SPF chez votre registrar</li>
                </ul>
            </div>
            
            <div class="status-box warning">
                <h3>⚠️ Configuration DNS requise</h3>
                <p>Pour que les emails soient délivrés, configurez ces enregistrements DNS :</p>
                <ul>
                    <li><strong>MX:</strong> atelierdelistaro.fr → [IP_PUBLIQUE_RASPBERRY]</li>
                    <li><strong>A:</strong> mail.atelierdelistaro.fr → [IP_PUBLIQUE_RASPBERRY]</li>
                    <li><strong>SPF:</strong> v=spf1 ip4:[IP_PUBLIQUE_RASPBERRY] ~all</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
