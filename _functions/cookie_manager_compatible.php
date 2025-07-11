<?php
/**
 * GESTIONNAIRE DE COOKIES RGPD - VERSION COMPATIBLE
 * Gestion complète des cookies selon les normes RGPD
 */

class CookieManager {
    private $db;
    private $sessionId;
    private $ipAddress;
    private $userAgent;
    private $cookieConsent;
    private $cookiePreferences;
    
    public function __construct($database) {
        $this->db = $database;
        $this->sessionId = session_id() ?: $this->generateSessionId();
        $this->ipAddress = $this->getClientIP();
        $this->userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        // Charger les préférences existantes
        $this->loadConsent();
    }
    
    /**
     * Générer un ID de session unique
     */
    private function generateSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Obtenir l'adresse IP réelle du client
     */
    private function getClientIP() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Charger le consentement existant
     */
    private function loadConsent() {
        try {
            $stmt = $this->db->prepare("
                SELECT consent_data FROM cookie_consents 
                WHERE session_id = ? AND is_active = 1 
                ORDER BY date_created DESC LIMIT 1
            ");
            $stmt->execute([$this->sessionId]);
            $consent = $stmt->fetch();
            
            if ($consent) {
                $data = json_decode($consent['consent_data'], true);
                $this->cookieConsent = $data['consent'] ?? false;
                $this->cookiePreferences = $data['preferences'] ?? [];
            } else {
                $this->cookieConsent = false;
                $this->cookiePreferences = [];
            }
        } catch (Exception $e) {
            error_log("Erreur chargement consentement: " . $e->getMessage());
            $this->cookieConsent = false;
            $this->cookiePreferences = [];
        }
    }
    
    /**
     * Sauvegarder le consentement dans la base de données
     */
    public function saveConsent($consentData) {
        try {
            // Vérifier si un consentement existe déjà pour cette session
            $checkStmt = $this->db->prepare("
                SELECT id FROM cookie_consents 
                WHERE session_id = ? AND is_active = 1
            ");
            $checkStmt->execute([$this->sessionId]);
            $existing = $checkStmt->fetch();
            
            if ($existing) {
                // Mettre à jour le consentement existant
                $stmt = $this->db->prepare("
                    UPDATE cookie_consents 
                    SET consent_data = ?, date_updated = NOW(), is_active = 1
                    WHERE session_id = ?
                ");
                
                return $stmt->execute([
                    json_encode($consentData),
                    $this->sessionId
                ]);
            } else {
                // Créer un nouveau consentement
                $stmt = $this->db->prepare("
                    INSERT INTO cookie_consents 
                    (user_id, session_id, consent_data, ip_address, user_agent, date_created, is_active)
                    VALUES (?, ?, ?, ?, ?, NOW(), 1)
                ");
                
                $userId = $this->getCurrentUserId();
                
                return $stmt->execute([
                    $userId,
                    $this->sessionId,
                    json_encode($consentData),
                    $this->ipAddress,
                    $this->userAgent
                ]);
            }
        } catch (PDOException $e) {
            error_log("Erreur sauvegarde consentement: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Collecter des données utilisateur
     */
    public function collectData($dataType, $dataContent, $consentGiven = false) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO user_data_collection 
                (user_id, session_id, data_type, data_content, ip_address, user_agent, page_url, referer, date_created, consent_given)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)
            ");
            
            $userId = $this->getCurrentUserId();
            $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            
            return $stmt->execute([
                $userId,
                $this->sessionId,
                $dataType,
                json_encode($dataContent),
                $this->ipAddress,
                $this->userAgent,
                $pageUrl,
                $referer,
                $consentGiven ? 1 : 0
            ]);
        } catch (Exception $e) {
            error_log("Erreur collecte données: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Enregistrer un log d'accès
     */
    public function logAccess($responseCode = 200) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO access_logs 
                (user_id, session_id, ip_address, user_agent, page_url, referer, request_method, response_code, date_created)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $userId = $this->getCurrentUserId();
            $pageUrl = $_SERVER['REQUEST_URI'] ?? '';
            $referer = $_SERVER['HTTP_REFERER'] ?? '';
            $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
            
            return $stmt->execute([
                $userId,
                $this->sessionId,
                $this->ipAddress,
                $this->userAgent,
                $pageUrl,
                $referer,
                $method,
                $responseCode
            ]);
        } catch (Exception $e) {
            error_log("Erreur log accès: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer une demande de suppression de données
     */
    public function createDeletionRequest($requestType, $email, $requestData = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO data_deletion_requests 
                (user_id, email, session_id, request_type, request_data, status, date_created)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            
            $userId = $this->getCurrentUserId();
            
            return $stmt->execute([
                $userId,
                $email,
                $this->sessionId,
                $requestType,
                $requestData ? json_encode($requestData) : null
            ]);
        } catch (Exception $e) {
            error_log("Erreur demande suppression: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir l'ID utilisateur actuel
     */
    private function getCurrentUserId() {
        if (isset($_SESSION['user_id'])) {
            return $_SESSION['user_id'];
        }
        return null;
    }
    
    /**
     * Vérifier si un type de cookie est autorisé
     */
    public function isAllowed($cookieType) {
        if (!$this->cookieConsent) {
            return false;
        }
        
        // Les cookies essentiels sont toujours autorisés
        if ($cookieType === 'essential') {
            return true;
        }
        
        return isset($this->cookiePreferences[$cookieType]) && $this->cookiePreferences[$cookieType];
    }
    
    /**
     * Obtenir les préférences de cookies
     */
    public function getPreferences() {
        return $this->cookiePreferences;
    }
    
    /**
     * Vérifier si le consentement a été donné
     */
    public function hasConsent() {
        return $this->cookieConsent;
    }
    
    /**
     * Définir un cookie avec vérification du consentement
     */
    public function setCookie($name, $value, $expiration = 0, $cookieType = 'essential') {
        if (!$this->isAllowed($cookieType)) {
            return false;
        }
        
        return setcookie($name, $value, [
            'expires' => $expiration,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    /**
     * Supprimer un cookie
     */
    public function deleteCookie($name) {
        return setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    
    /**
     * Obtenir les statistiques de consentement
     */
    public function getConsentStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_consents,
                    SUM(JSON_EXTRACT(consent_data, '$.consent')) as consents_given,
                    DATE(date_created) as date
                FROM cookie_consents 
                WHERE date_created >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY DATE(date_created)
                ORDER BY date DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Erreur statistiques consentement: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Exporter les données d'un utilisateur
     */
    public function exportUserData($userId = null, $email = null) {
        try {
            $data = [];
            
            // Données de consentement
            if ($userId) {
                $stmt = $this->db->prepare("SELECT * FROM cookie_consents WHERE user_id = ?");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM cookie_consents WHERE session_id = ?");
                $stmt->execute([$this->sessionId]);
            }
            $data['consents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Données collectées
            if ($userId) {
                $stmt = $this->db->prepare("SELECT * FROM user_data_collection WHERE user_id = ?");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM user_data_collection WHERE session_id = ?");
                $stmt->execute([$this->sessionId]);
            }
            $data['collected_data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Logs d'accès
            if ($userId) {
                $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE user_id = ?");
                $stmt->execute([$userId]);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM access_logs WHERE session_id = ?");
                $stmt->execute([$this->sessionId]);
            }
            $data['access_logs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $data;
        } catch (Exception $e) {
            error_log("Erreur export données: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Supprimer toutes les données d'un utilisateur
     */
    public function deleteUserData($userId = null) {
        try {
            $tables = ['cookie_consents', 'user_data_collection', 'access_logs'];
            
            foreach ($tables as $table) {
                if ($userId) {
                    $stmt = $this->db->prepare("DELETE FROM $table WHERE user_id = ?");
                    $stmt->execute([$userId]);
                } else {
                    $stmt = $this->db->prepare("DELETE FROM $table WHERE session_id = ?");
                    $stmt->execute([$this->sessionId]);
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur suppression données: " . $e->getMessage());
            return false;
        }
    }
}
?>
