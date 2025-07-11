<?php
/**
 * SYSTÈME DE GESTION DES COOKIES RGPD
 * Conforme à la réglementation européenne et française
 */

class CookieManager {
    private $db;
    private $cookieConsent = false;
    private $cookiePreferences = [];
    
    public function __construct($database) {
        $this->db = $database;
        $this->initializeConsent();
    }
    
    /**
     * Initialise le consentement des cookies
     */
    private function initializeConsent() {
        if (isset($_COOKIE['cookie_consent'])) {
            $this->cookieConsent = $_COOKIE['cookie_consent'] === 'accepted';
            if (isset($_COOKIE['cookie_preferences'])) {
                $this->cookiePreferences = json_decode($_COOKIE['cookie_preferences'], true) ?: [];
            }
        }
    }
    
    /**
     * Enregistre le consentement
     */
    public function setConsent($consent, $preferences = []) {
        $this->cookieConsent = $consent;
        $this->cookiePreferences = $preferences;
        
        // Sauvegarder dans des cookies sécurisés
        setcookie('cookie_consent', $consent ? 'accepted' : 'refused', [
            'expires' => time() + (365 * 24 * 60 * 60), // 1 an
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        setcookie('cookie_preferences', json_encode($preferences), [
            'expires' => time() + (365 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '',
            'secure' => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        
        // Enregistrer en base de données
        $this->logConsent($consent, $preferences);
    }
    
    /**
     * Enregistre le consentement en base de données
     */
    private function logConsent($consent, $preferences) {
        try {
            $stmt = $this->db->prepare("INSERT INTO cookie_consents (ip_address, user_agent, consent_given, preferences, consent_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                $consent ? 1 : 0,
                json_encode($preferences)
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors de l'enregistrement du consentement : " . $e->getMessage());
        }
    }
    
    /**
     * Vérifie si un type de cookie est autorisé
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
     * Collecte les informations utilisateur autorisées
     */
    public function collectUserData() {
        $data = [];
        
        // Données essentielles (toujours collectées)
        $data['essential'] = [
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'page_url' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        // Données analytiques (si autorisées)
        if ($this->isAllowed('analytics')) {
            $data['analytics'] = [
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
                'referer' => $_SERVER['HTTP_REFERER'] ?? 'direct',
                'screen_resolution' => $_POST['screen_resolution'] ?? 'unknown',
                'browser_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'unknown'
            ];
        }
        
        // Données de préférences (si autorisées)
        if ($this->isAllowed('preferences')) {
            $data['preferences'] = [
                'theme' => $_COOKIE['theme'] ?? 'default',
                'language' => $_COOKIE['language'] ?? 'fr',
                'timezone' => $_POST['timezone'] ?? 'Europe/Paris'
            ];
        }
        
        // Données marketing (si autorisées)
        if ($this->isAllowed('marketing')) {
            $data['marketing'] = [
                'utm_source' => $_GET['utm_source'] ?? null,
                'utm_medium' => $_GET['utm_medium'] ?? null,
                'utm_campaign' => $_GET['utm_campaign'] ?? null,
                'ad_click_id' => $_GET['gclid'] ?? null
            ];
        }
        
        return $data;
    }
    
    /**
     * Enregistre les données collectées
     */
    public function saveCollectedData($data) {
        try {
            $stmt = $this->db->prepare("INSERT INTO user_data_collection (ip_address, collected_data, collection_date) VALUES (?, ?, NOW())");
            $stmt->execute([
                $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                json_encode($data)
            ]);
        } catch (Exception $e) {
            error_log("Erreur lors de l'enregistrement des données : " . $e->getMessage());
        }
    }
    
    /**
     * Génère le HTML du bandeau de cookies
     */
    public function getCookieBanner() {
        if ($this->cookieConsent !== false) {
            return ''; // Consentement déjà donné
        }
        
        return '
        <div id="cookie-banner" class="cookie-banner">
            <div class="cookie-content">
                <div class="cookie-text">
                    <h3>🍪 Gestion des cookies</h3>
                    <p>Nous utilisons des cookies pour améliorer votre expérience sur notre site. Vous pouvez accepter tous les cookies ou personnaliser vos préférences.</p>
                </div>
                <div class="cookie-actions">
                    <button id="cookie-accept-all" class="btn btn-primary">Accepter tout</button>
                    <button id="cookie-customize" class="btn btn-secondary">Personnaliser</button>
                    <button id="cookie-refuse" class="btn btn-danger">Refuser</button>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Génère le HTML du centre de préférences
     */
    public function getPreferencesModal() {
        return '
        <div id="cookie-preferences-modal" class="cookie-modal" style="display: none;">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>🍪 Préférences des cookies</h2>
                    <button id="close-preferences" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies essentiels</h3>
                            <input type="checkbox" checked disabled>
                        </div>
                        <p>Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies analytiques</h3>
                            <input type="checkbox" id="analytics-cookies" name="analytics">
                        </div>
                        <p>Ces cookies nous aident à comprendre comment vous utilisez notre site pour l\'améliorer.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies de préférences</h3>
                            <input type="checkbox" id="preferences-cookies" name="preferences">
                        </div>
                        <p>Ces cookies mémorisent vos préférences pour personnaliser votre expérience.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies marketing</h3>
                            <input type="checkbox" id="marketing-cookies" name="marketing">
                        </div>
                        <p>Ces cookies sont utilisés pour vous proposer des publicités pertinentes.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="save-preferences" class="btn btn-primary">Sauvegarder les préférences</button>
                    <button id="cancel-preferences" class="btn btn-secondary">Annuler</button>
                </div>
            </div>
        </div>';
    }
    
    /**
     * Supprime toutes les données d\'un utilisateur (droit à l\'oubli)
     */
    public function deleteUserData($ipAddress) {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_data_collection WHERE ip_address = ?");
            $stmt->execute([$ipAddress]);
            
            $stmt = $this->db->prepare("DELETE FROM cookie_consents WHERE ip_address = ?");
            $stmt->execute([$ipAddress]);
            
            return true;
        } catch (Exception $e) {
            error_log("Erreur lors de la suppression des données : " . $e->getMessage());
            return false;
        }
    }
}
?>
