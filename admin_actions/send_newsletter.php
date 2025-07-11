<?php
/**
 * Envoi Newsletter - Admin Panel
 * Atelier de Listaro
 */

session_start();
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');
include_once('../_functions/email_config_raspberry.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit();
}

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

// Récupérer les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['subject']) || !isset($data['message'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$subject = trim($data['subject']);
$message = trim($data['message']);
$test_mode = isset($data['test_mode']) ? (bool)$data['test_mode'] : false;

if (empty($subject) || empty($message)) {
    echo json_encode(['success' => false, 'message' => 'Le sujet et le message sont requis']);
    exit();
}

try {
    if ($test_mode) {
        // Mode test - envoyer seulement à l'admin
        $test_email = 'lucien.dacunha@gmail.com';
        
        $email_body = getEmailTemplate(
            $subject,
            "<h2>📧 Test Newsletter</h2>
            <div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; color: #856404;'>
                <strong>⚠️ Mode Test</strong><br>
                Ceci est un email de test. En mode production, cet email serait envoyé à tous les abonnés newsletter.
            </div>
            <div style='background: #f8f9fa; padding: 20px; border-radius: 5px;'>
                <h3>Contenu de la newsletter :</h3>
                " . nl2br(htmlspecialchars($message)) . "
            </div>"
        );
        
        $result = sendEmail($test_email, "[TEST] " . $subject, $email_body);
        
        if (is_array($result) && $result['success']) {
            echo json_encode([
                'success' => true, 
                'message' => 'Email de test envoyé avec succès à ' . $test_email
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'message' => 'Erreur lors de l\'envoi du test : ' . (is_array($result) ? $result['message'] : 'Erreur inconnue')
            ]);
        }
        
    } else {
        // Mode production - envoyer à tous les abonnés
        $stmt = $DB->prepare("
            SELECT mail as email, pseudo as username 
            FROM user 
            WHERE newsletter = 1 
            AND mail IS NOT NULL 
            AND mail != ''
        ");
        $stmt->execute();
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($subscribers) === 0) {
            echo json_encode([
                'success' => false, 
                'message' => 'Aucun abonné à la newsletter trouvé'
            ]);
            exit();
        }
        
        $sent_count = 0;
        $failed_count = 0;
        $errors = [];
        
        foreach ($subscribers as $subscriber) {
            // Personnaliser le message pour chaque abonné
            $personalized_message = str_replace(
                ['{username}', '{email}'],
                [$subscriber['username'], $subscriber['email']],
                $message
            );
            
            $email_body = getEmailTemplate(
                $subject,
                "<h2>📧 Newsletter Atelier de Listaro</h2>
                <p>Bonjour " . htmlspecialchars($subscriber['username']) . ",</p>
                <div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    " . nl2br(htmlspecialchars($personalized_message)) . "
                </div>
                <div style='background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 20px 0; font-size: 0.9em;'>
                    <p><strong>💌 Vous recevez ce mail car vous êtes abonné(e) à notre newsletter.</strong></p>
                    <p>Vous pouvez vous désabonner à tout moment en nous contactant à contact@atelierdelistaro.fr</p>
                </div>"
            );
            
            $result = sendEmail($subscriber['email'], $subject, $email_body);
            
            if (is_array($result) && $result['success']) {
                $sent_count++;
            } else {
                $failed_count++;
                $errors[] = $subscriber['email'] . ': ' . (is_array($result) ? $result['message'] : 'Erreur inconnue');
            }
            
            // Petite pause pour éviter de surcharger le serveur SMTP
            usleep(100000); // 0.1 seconde
        }
        
        // Enregistrer les statistiques d'envoi (optionnel)
        // Vous pouvez créer une table newsletter_logs pour tracker les envois
        
        $message_result = "Newsletter envoyée avec succès à $sent_count abonné(s)";
        if ($failed_count > 0) {
            $message_result .= ". $failed_count échec(s)";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message_result,
            'details' => [
                'sent' => $sent_count,
                'failed' => $failed_count,
                'errors' => $errors
            ]
        ]);
    }
    
} catch (Exception $e) {
    error_log("Erreur newsletter: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur lors de l\'envoi : ' . $e->getMessage()
    ]);
}
?>
