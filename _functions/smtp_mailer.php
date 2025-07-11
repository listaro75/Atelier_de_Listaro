<?php
/**
 * Bibliothèque d'envoi d'emails SMTP pour Atelier de Listaro
 * Compatible avec InfinityFree et autres hébergements
 */

/**
 * Classe simple pour l'envoi d'emails via SMTP
 * Alternative à PHPMailer pour éviter les dépendances
 */
class SimpleMailer {
    private $smtp_host;
    private $smtp_port;
    private $smtp_username;
    private $smtp_password;
    private $smtp_encryption;
    private $timeout = 30;
    private $debug = false;
    
    public function __construct($config) {
        $this->smtp_host = $config['host'];
        $this->smtp_port = $config['port'];
        $this->smtp_username = $config['username'];
        $this->smtp_password = $config['password'];
        $this->smtp_encryption = $config['encryption'] ?? 'tls';
    }
    
    public function setDebug($debug = true) {
        $this->debug = $debug;
        return $this;
    }
    
    /**
     * Envoie un email via SMTP
     */
    public function sendMail($to, $subject, $body, $from_email, $from_name = '', $reply_to = null) {
        try {
            // Connexion SMTP
            $socket = $this->connectSMTP();
            if (!$socket) {
                throw new Exception("Impossible de se connecter au serveur SMTP");
            }
            
            // Authentification
            $this->authenticateSMTP($socket);
            
            // Envoi de l'email
            $this->sendEmailSMTP($socket, $to, $subject, $body, $from_email, $from_name, $reply_to);
            
            // Fermeture de la connexion
            fwrite($socket, "QUIT\r\n");
            fclose($socket);
            
            return true;
            
        } catch (Exception $e) {
            if ($this->debug) {
                error_log("Erreur SMTP: " . $e->getMessage());
            }
            return false;
        }
    }
    
    private function connectSMTP() {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        if ($this->smtp_encryption === 'ssl') {
            $socket = stream_socket_client(
                "ssl://{$this->smtp_host}:{$this->smtp_port}",
                $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context
            );
        } else {
            $socket = stream_socket_client(
                "tcp://{$this->smtp_host}:{$this->smtp_port}",
                $errno, $errstr, $this->timeout, STREAM_CLIENT_CONNECT, $context
            );
        }
        
        if (!$socket) {
            throw new Exception("Connexion échouée: $errstr ($errno)");
        }
        
        $this->readSMTPResponse($socket); // Lire la réponse de bienvenue
        
        // EHLO
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSMTPResponse($socket);
        
        // STARTTLS si nécessaire
        if ($this->smtp_encryption === 'tls') {
            fwrite($socket, "STARTTLS\r\n");
            $this->readSMTPResponse($socket);
            
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new Exception("Impossible d'activer TLS");
            }
            
            // Re-EHLO après TLS
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $this->readSMTPResponse($socket);
        }
        
        return $socket;
    }
    
    private function authenticateSMTP($socket) {
        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $this->readSMTPResponse($socket);
        
        // Username
        fwrite($socket, base64_encode($this->smtp_username) . "\r\n");
        $this->readSMTPResponse($socket);
        
        // Password
        fwrite($socket, base64_encode($this->smtp_password) . "\r\n");
        $response = $this->readSMTPResponse($socket);
        
        if (strpos($response, '235') !== 0) {
            throw new Exception("Authentification échouée");
        }
    }
    
    private function sendEmailSMTP($socket, $to, $subject, $body, $from_email, $from_name, $reply_to) {
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<$from_email>\r\n");
        $this->readSMTPResponse($socket);
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<$to>\r\n");
        $this->readSMTPResponse($socket);
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $this->readSMTPResponse($socket);
        
        // Headers et corps
        $headers = $this->buildHeaders($to, $subject, $from_email, $from_name, $reply_to);
        $message = $headers . "\r\n" . $body . "\r\n.\r\n";
        
        fwrite($socket, $message);
        $this->readSMTPResponse($socket);
    }
    
    private function buildHeaders($to, $subject, $from_email, $from_name, $reply_to) {
        $from_name = $from_name ? "=?UTF-8?B?" . base64_encode($from_name) . "?=" : '';
        $subject_encoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";
        $reply_to = $reply_to ?: $from_email;
        
        $headers = "To: $to\r\n";
        $headers .= "Subject: $subject_encoded\r\n";
        $headers .= "From: $from_name <$from_email>\r\n";
        $headers .= "Reply-To: $reply_to\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "Content-Transfer-Encoding: 8bit\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . md5(uniqid(time())) . "@" . $this->smtp_host . ">\r\n";
        
        return $headers;
    }
    
    private function readSMTPResponse($socket) {
        $response = '';
        while (($line = fgets($socket, 512)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) == ' ') break;
        }
        
        if ($this->debug) {
            error_log("SMTP Response: " . trim($response));
        }
        
        return $response;
    }
}

/**
 * Configuration et envoi d'emails SMTP
 */
class EmailSender {
    private static $config = null;
    
    public static function init() {
        if (self::$config === null) {
            // Charger la configuration depuis l'environnement
            require_once __DIR__ . '/../_config/env.php';
            
            self::$config = [
                'use_smtp' => $_ENV['SMTP_ENABLED'] ?? false,
                'host' => $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com',
                'port' => $_ENV['SMTP_PORT'] ?? 587,
                'username' => $_ENV['SMTP_USERNAME'] ?? '',
                'password' => $_ENV['SMTP_PASSWORD'] ?? '',
                'encryption' => $_ENV['SMTP_ENCRYPTION'] ?? 'tls',
                'from_email' => $_ENV['EMAIL_FROM'] ?? 'noreply@atelierdelistaro.great-site.net',
                'from_name' => $_ENV['EMAIL_FROM_NAME'] ?? 'Atelier de Listaro',
                'reply_to' => $_ENV['EMAIL_REPLY_TO'] ?? 'contact@atelierdelistaro.great-site.net'
            ];
        }
    }
    
    /**
     * Envoie un email (SMTP ou fonction mail() native)
     */
    public static function send($to, $subject, $body, $options = []) {
        self::init();
        
        $from_email = $options['from_email'] ?? self::$config['from_email'];
        $from_name = $options['from_name'] ?? self::$config['from_name'];
        $reply_to = $options['reply_to'] ?? self::$config['reply_to'];
        
        // Si SMTP est activé et configuré
        if (self::$config['use_smtp'] && !empty(self::$config['username']) && !empty(self::$config['password'])) {
            try {
                $mailer = new SimpleMailer([
                    'host' => self::$config['host'],
                    'port' => self::$config['port'],
                    'username' => self::$config['username'],
                    'password' => self::$config['password'],
                    'encryption' => self::$config['encryption']
                ]);
                
                return $mailer->sendMail($to, $subject, $body, $from_email, $from_name, $reply_to);
                
            } catch (Exception $e) {
                error_log("Erreur SMTP: " . $e->getMessage());
                // Fallback vers mail() en cas d'erreur SMTP
            }
        }
        
        // Fallback : utiliser la fonction mail() native de PHP
        return self::sendWithNativeMail($to, $subject, $body, $from_email, $from_name, $reply_to);
    }
    
    /**
     * Envoi avec la fonction mail() native de PHP
     */
    private static function sendWithNativeMail($to, $subject, $body, $from_email, $from_name, $reply_to) {
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: $from_name <$from_email>" . "\r\n";
        $headers .= "Reply-To: $reply_to" . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
        
        return mail($to, $subject, $body, $headers);
    }
    
    /**
     * Test de la configuration email
     */
    public static function test($to, $debug = false) {
        self::init();
        
        $subject = "Test Email - Atelier de Listaro";
        $body = self::getTestEmailTemplate();
        
        if ($debug) {
            echo "<h3>Configuration Email:</h3>";
            echo "<pre>";
            echo "SMTP Activé: " . (self::$config['use_smtp'] ? 'Oui' : 'Non') . "\n";
            echo "Host: " . self::$config['host'] . "\n";
            echo "Port: " . self::$config['port'] . "\n";
            echo "Username: " . self::$config['username'] . "\n";
            echo "Encryption: " . self::$config['encryption'] . "\n";
            echo "From: " . self::$config['from_email'] . "\n";
            echo "</pre>";
        }
        
        $result = self::send($to, $subject, $body);
        
        if ($debug) {
            echo "<h3>Résultat:</h3>";
            echo $result ? "<p style='color: green;'>✅ Email envoyé avec succès!</p>" : "<p style='color: red;'>❌ Échec de l'envoi</p>";
        }
        
        return $result;
    }
    
    private static function getTestEmailTemplate() {
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2c3e50; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f9f9f9; }
                .footer { background: #34495e; color: white; padding: 15px; text-align: center; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Test Email - Atelier de Listaro</h1>
                </div>
                <div class='content'>
                    <h2>Configuration email testée avec succès!</h2>
                    <p>Si vous recevez cet email, cela signifie que la configuration d'envoi d'emails fonctionne correctement.</p>
                    <p><strong>Détails du test:</strong></p>
                    <ul>
                        <li>Date: " . date('d/m/Y H:i:s') . "</li>
                        <li>Serveur: " . ($_SERVER['SERVER_NAME'] ?? 'localhost') . "</li>
                        <li>PHP Version: " . phpversion() . "</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p>&copy; " . date('Y') . " Atelier de Listaro - Créations artisanales uniques</p>
                </div>
            </div>
        </body>
        </html>";
    }
}
