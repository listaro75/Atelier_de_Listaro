<?php
/**
 * Test et diagnostic de la configuration email
 * Atelier de Listaro - Raspberry Pi
 */

// Inclure la configuration email
require_once '_functions/email_config_raspberry.php';

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email - Atelier de Listaro</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
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
        .success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .warning {
            background: #fff3cd;
            border-color: #ffc107;
            color: #856404;
        }
        .error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
        .info {
            background: #d1ecf1;
            border-color: #17a2b8;
            color: #0c5460;
        }
        .test-section {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, select, textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background: #007cba;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background: #005a8b;
        }
        .config-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
        }
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
        <h1>🍓 Test Email - Raspberry Pi</h1>
        
        <?php
        // Gestion des tests
        if (isset($_POST['test_email'])) {
            $to = $_POST['email'] ?? '';
            $method = $_POST['method'] ?? 'postfix';
            $subject = $_POST['subject'] ?? 'Test Email - Atelier de Listaro';
            $message = $_POST['message'] ?? 'Ceci est un email de test envoyé depuis votre Raspberry Pi.';
            
            if (!empty($to)) {
                echo '<div class="status-box info">';
                echo '<h3>🧪 Test d\'envoi en cours...</h3>';
                echo '<p><strong>Destinataire:</strong> ' . htmlspecialchars($to) . '</p>';
                echo '<p><strong>Méthode:</strong> ' . htmlspecialchars($method) . '</p>';
                echo '</div>';
                
                try {
                    $result = sendEmail($to, $subject, $message, 'noreply@atelierdelistaro.fr', $method);
                    
                    if (is_array($result) && isset($result['success'])) {
                        if ($result['success']) {
                            echo '<div class="status-box success">';
                            echo '<h3>✅ Email envoyé avec succès !</h3>';
                            echo '<p>' . htmlspecialchars($result['message']) . '</p>';
                            echo '</div>';
                        } else {
                            echo '<div class="status-box error">';
                            echo '<h3>❌ Erreur lors de l\'envoi</h3>';
                            echo '<p>' . htmlspecialchars($result['message']) . '</p>';
                            
                            if (isset($result['diagnostics'])) {
                                echo '<h4>🔍 Diagnostic:</h4><ul>';
                                foreach ($result['diagnostics'] as $diag) {
                                    echo '<li>' . htmlspecialchars($diag) . '</li>';
                                }
                                echo '</ul>';
                            }
                            echo '</div>';
                        }
                    } else {
                        // Ancienne logique pour compatibilité
                        if ($result) {
                            echo '<div class="status-box success">';
                            echo '<h3>✅ Email envoyé avec succès !</h3>';
                            echo '</div>';
                        } else {
                            echo '<div class="status-box error">';
                            echo '<h3>❌ Erreur lors de l\'envoi</h3>';
                            echo '<p>La fonction a retourné FALSE</p>';
                            echo '</div>';
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="status-box error">';
                    echo '<h3>❌ Exception attrapée</h3>';
                    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '</div>';
                }
            }
        }
        
        // Diagnostic du système
        echo '<div class="test-section">';
        echo '<h2>📊 Diagnostic du système</h2>';
        
        // Vérification PHP mail
        if (function_exists('mail')) {
            echo '<div class="status-box success">✅ Fonction PHP mail() disponible</div>';
        } else {
            echo '<div class="status-box error">❌ Fonction PHP mail() non disponible</div>';
        }
        
        // Vérification Postfix
        $postfix_status = shell_exec('systemctl is-active postfix 2>/dev/null');
        if (trim($postfix_status) == 'active') {
            echo '<div class="status-box success">✅ Postfix actif</div>';
        } else {
            echo '<div class="status-box warning">⚠️ Postfix non actif ou non installé</div>';
        }
        
        // Configuration système
        echo '<div class="config-info">';
        echo '<h3>⚙️ Configuration système</h3>';
        echo '<p><strong>Hostname:</strong> ' . gethostname() . '</p>';
        echo '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
        echo '<p><strong>Date/Heure:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        echo '<p><strong>Sendmail path:</strong> ' . ini_get('sendmail_path') . '</p>';
        echo '</div>';
        
        echo '</div>';
        ?>
        
        <!-- Formulaire de test -->
        <div class="test-section">
            <h2>🧪 Test d'envoi d'email</h2>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email destinataire :</label>
                    <input type="email" name="email" id="email" required 
                           value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">
                </div>
                
                <div class="form-group">
                    <label for="method">Méthode d'envoi :</label>
                    <select name="method" id="method">
                        <option value="postfix" <?= (isset($_POST['method']) && $_POST['method'] == 'postfix') ? 'selected' : '' ?>>
                            Postfix Local (Recommandé)
                        </option>
                        <option value="gmail" <?= (isset($_POST['method']) && $_POST['method'] == 'gmail') ? 'selected' : '' ?>>
                            Gmail SMTP
                        </option>
                        <option value="ovh" <?= (isset($_POST['method']) && $_POST['method'] == 'ovh') ? 'selected' : '' ?>>
                            OVH SMTP
                        </option>
                        <option value="mailgun" <?= (isset($_POST['method']) && $_POST['method'] == 'mailgun') ? 'selected' : '' ?>>
                            Mailgun API
                        </option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="subject">Sujet :</label>
                    <input type="text" name="subject" id="subject" 
                           value="<?= isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : 'Test Email - Atelier de Listaro' ?>">
                </div>
                
                <div class="form-group">
                    <label for="message">Message :</label>
                    <textarea name="message" id="message" rows="5"><?= isset($_POST['message']) ? htmlspecialchars($_POST['message']) : 'Bonjour,

Ceci est un email de test envoyé depuis votre Raspberry Pi.

Configuration:
- Serveur: ' . gethostname() . '
- Date: ' . date('Y-m-d H:i:s') . '
- Méthode: Postfix local

Cordialement,
Atelier de Listaro' ?></textarea>
                </div>
                
                <button type="submit" name="test_email">📧 Envoyer test email</button>
            </form>
        </div>
        
        <!-- Logs système -->
        <div class="test-section">
            <h2>📋 Logs système (dernières lignes)</h2>
            
            <h3>Mail logs (/var/log/mail.log)</h3>
            <div class="logs">
<?php
$mail_logs = shell_exec('tail -20 /var/log/mail.log 2>/dev/null || echo "Logs mail non accessibles"');
echo htmlspecialchars($mail_logs);
?>
            </div>
            
            <h3>Queue Postfix</h3>
            <div class="logs">
<?php
$postfix_queue = shell_exec('mailq 2>/dev/null || echo "Postfix non installé ou non accessible"');
echo htmlspecialchars($postfix_queue);
?>
            </div>
        </div>
        
        <!-- Instructions DNS -->
        <div class="test-section">
            <h2>🌐 Configuration DNS requise</h2>
            <div class="status-box warning">
                <h3>⚠️ Configuration DNS nécessaire pour atelierdelistaro.fr</h3>
                <p>Pour que les emails soient délivrés correctement, configurez ces enregistrements DNS :</p>
                <ul>
                    <li><strong>MX record:</strong> atelierdelistaro.fr → [IP_DE_VOTRE_RASPBERRY]</li>
                    <li><strong>A record:</strong> mail.atelierdelistaro.fr → [IP_DE_VOTRE_RASPBERRY]</li>
                    <li><strong>SPF record:</strong> v=spf1 ip4:[IP_DE_VOTRE_RASPBERRY] ~all</li>
                </ul>
                <p><em>Remplacez [IP_DE_VOTRE_RASPBERRY] par l'IP publique de votre Raspberry Pi.</em></p>
            </div>
        </div>
        
        <!-- Outils de dépannage -->
        <div class="test-section">
            <h2>🔧 Outils de dépannage</h2>
            <div class="status-box info">
                <h3>🛠️ Scripts disponibles</h3>
                <ul>
                    <li><strong><a href="fix_email_raspberry.php" target="_blank">Interface de correction</a></strong> - Diagnostic et correction automatique</li>
                    <li><strong>Diagnostic complet:</strong> <code>./diagnostic_mail.sh votre@email.com</code></li>
                    <li><strong>Test manuel:</strong> <code>/home/pi/test_email.sh votre@email.com</code></li>
                </ul>
                
                <p><strong>Si l'envoi échoue :</strong></p>
                <ol>
                    <li>Utilisez l'<a href="fix_email_raspberry.php">interface de correction</a> pour diagnostiquer</li>
                    <li>Vérifiez que Postfix est bien installé et configuré</li>
                    <li>Assurez-vous que les enregistrements DNS sont configurés</li>
                    <li>Vérifiez les logs avec <code>tail -f /var/log/mail.log</code></li>
                </ol>
            </div>
        </div>
        
        <!-- Guide d'installation -->
        <div class="test-section">
            <h2>🛠️ Guide d'installation</h2>
            <div class="config-info">
                <h3>1. Installation sur Raspberry Pi</h3>
                <div class="logs">
# Transférer et exécuter le script d'installation
sudo chmod +x install_mail_raspberry.sh
sudo ./install_mail_raspberry.sh

# Test manuel
/home/pi/test_email.sh votre@email.com
                </div>
                
                <h3>2. Scripts utiles</h3>
                <ul>
                    <li><code>/home/pi/test_email.sh</code> - Test d'envoi d'email</li>
                    <li><code>/home/pi/mail_status.sh</code> - Statut du serveur mail</li>
                    <li><code>tail -f /var/log/mail.log</code> - Suivi des logs en temps réel</li>
                    <li><code>mailq</code> - Voir la queue des emails</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
