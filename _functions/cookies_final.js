/**
 * GESTIONNAIRE DE COOKIES RGPD - VERSION FINALE
 * Script JavaScript pour le bandeau et les préférences
 */

class CookieManager {
    constructor() {
        this.consentGiven = false;
        this.preferences = {
            essential: true,
            analytics: false,
            preferences: false,
            marketing: false
        };
        
        this.init();
    }
    
    init() {
        console.log('🔄 Initialisation du système de cookies...');
        
        // Vérifier le consentement existant
        this.checkExistingConsent();
        
        // Ajouter les modales et l'indicateur
        this.addPreferencesModal();
        this.addCookieIndicator();
        
        // Attacher les événements
        this.attachEvents();
        
        // Collecter les données si autorisé
        this.collectData();
        
        // Afficher le bandeau si nécessaire
        this.showBannerIfNeeded();
        
        console.log('✅ Système de cookies initialisé');
    }
    
    checkExistingConsent() {
        const consent = this.getCookie('cookie_consent');
        console.log('🔍 Vérification du consentement existant:', consent);
        
        if (consent) {
            this.consentGiven = consent === 'accepted';
            
            const preferencesData = this.getCookie('cookie_preferences');
            if (preferencesData) {
                try {
                    this.preferences = JSON.parse(preferencesData);
                } catch (e) {
                    console.warn('Erreur lors du parsing des préférences:', e);
                }
            }
        } else {
            console.log('Aucun consentement trouvé');
        }
    }
    
    collectData() {
        if (this.consentGiven && this.preferences.analytics) {
            console.log('📊 Collecte des données autorisée');
        } else {
            console.log('📊 Collecte des données non autorisée');
        }
    }
    
    showBannerIfNeeded() {
        if (!this.consentGiven) {
            this.showCookieBanner();
        }
    }
    
    attachEvents() {
        const prefsBtn = document.getElementById('cookie-prefs-btn');
        if (prefsBtn) {
            prefsBtn.addEventListener('click', () => this.showPreferences());
        }
    }
    
    showCookieBanner() {
        if (document.getElementById('cookie-banner')) {
            console.log('ℹ️ Bandeau de cookies déjà présent');
            return;
        }
        
        const banner = document.createElement('div');
        banner.id = 'cookie-banner';
        banner.className = 'cookie-banner';
        banner.innerHTML = `
            <div class="cookie-banner-content">
                <div class="cookie-banner-text">
                    <h3>🍪 Nous utilisons des cookies</h3>
                    <p>Ce site utilise des cookies pour améliorer votre expérience de navigation.</p>
                </div>
                <div class="cookie-banner-buttons">
                    <button id="cookie-accept-all" class="cookie-btn cookie-btn-primary">Tout accepter</button>
                    <button id="cookie-customize" class="cookie-btn cookie-btn-secondary">Personnaliser</button>
                    <button id="cookie-refuse" class="cookie-btn cookie-btn-outline">Tout refuser</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(banner);
        console.log('✅ Bandeau de cookies ajouté au DOM');
        
        this.attachBannerEvents();
    }
    
    attachBannerEvents() {
        const acceptAllBtn = document.getElementById('cookie-accept-all');
        const customizeBtn = document.getElementById('cookie-customize');
        const refuseBtn = document.getElementById('cookie-refuse');
        
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => {
                console.log('✅ Acceptation de tous les cookies');
                this.acceptAll();
            });
        }
        
        if (customizeBtn) {
            customizeBtn.addEventListener('click', () => {
                console.log('⚙️ Ouverture des préférences');
                this.showPreferences();
            });
        }
        
        if (refuseBtn) {
            refuseBtn.addEventListener('click', () => {
                console.log('❌ Refus des cookies');
                this.refuseAll();
            });
        }
    }
    
    addPreferencesModal() {
        if (document.getElementById('cookie-preferences-modal')) {
            console.log('ℹ️ Modal de préférences déjà présent');
            return;
        }
        
        const modal = document.createElement('div');
        modal.id = 'cookie-preferences-modal';
        modal.className = 'cookie-modal';
        modal.style.display = 'none';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h2>🍪 Préférences des cookies</h2>
                    <button id="close-preferences" class="close-btn">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies essentiels</h3>
                            <label class="cookie-switch">
                                <input type="checkbox" id="essential-cookies" checked disabled>
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p>Ces cookies sont nécessaires au fonctionnement du site.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies d'analyse</h3>
                            <label class="cookie-switch">
                                <input type="checkbox" id="analytics-cookies">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p>Ces cookies nous aident à comprendre comment les visiteurs utilisent notre site.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies de préférences</h3>
                            <label class="cookie-switch">
                                <input type="checkbox" id="preferences-cookies">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p>Ces cookies permettent au site de se souvenir de vos préférences.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies marketing</h3>
                            <label class="cookie-switch">
                                <input type="checkbox" id="marketing-cookies">
                                <span class="slider"></span>
                            </label>
                        </div>
                        <p>Ces cookies sont utilisés pour vous proposer des publicités personnalisées.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="save-preferences" class="cookie-btn cookie-btn-primary">Enregistrer</button>
                    <button id="accept-all-prefs" class="cookie-btn cookie-btn-secondary">Tout accepter</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        console.log('✅ Modal de préférences ajouté au DOM');
        
        this.attachModalEvents();
    }
    
    attachModalEvents() {
        const modal = document.getElementById('cookie-preferences-modal');
        const closeBtn = document.getElementById('close-preferences');
        const saveBtn = document.getElementById('save-preferences');
        const acceptAllBtn = document.getElementById('accept-all-prefs');
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });
        }
        
        if (saveBtn) {
            saveBtn.addEventListener('click', () => {
                this.savePreferences();
                modal.style.display = 'none';
            });
        }
        
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => {
                this.acceptAll();
                modal.style.display = 'none';
            });
        }
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });
    }
    
    addCookieIndicator() {
        if (document.getElementById('cookie-indicator')) {
            console.log('ℹ️ Indicateur de cookies déjà présent');
            return;
        }
        
        const indicator = document.createElement('div');
        indicator.id = 'cookie-indicator';
        indicator.className = 'cookie-indicator';
        indicator.innerHTML = `
            <button id="cookie-prefs-btn" class="cookie-prefs-btn" title="Gérer les cookies">
                🍪
            </button>
        `;
        
        document.body.appendChild(indicator);
        console.log('✅ Indicateur de cookies ajouté au DOM');
        
        const prefsBtn = document.getElementById('cookie-prefs-btn');
        if (prefsBtn) {
            prefsBtn.addEventListener('click', () => this.showPreferences());
        }
    }
    
    showPreferences() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            this.updatePreferencesUI();
            modal.style.display = 'block';
            console.log('📋 Modal de préférences affiché');
        }
    }
    
    updatePreferencesUI() {
        const analyticsCheckbox = document.getElementById('analytics-cookies');
        const preferencesCheckbox = document.getElementById('preferences-cookies');
        const marketingCheckbox = document.getElementById('marketing-cookies');
        
        if (analyticsCheckbox) analyticsCheckbox.checked = this.preferences.analytics;
        if (preferencesCheckbox) preferencesCheckbox.checked = this.preferences.preferences;
        if (marketingCheckbox) marketingCheckbox.checked = this.preferences.marketing;
    }
    
    savePreferences() {
        const analyticsCheckbox = document.getElementById('analytics-cookies');
        const preferencesCheckbox = document.getElementById('preferences-cookies');
        const marketingCheckbox = document.getElementById('marketing-cookies');
        
        this.preferences = {
            essential: true,
            analytics: analyticsCheckbox ? analyticsCheckbox.checked : false,
            preferences: preferencesCheckbox ? preferencesCheckbox.checked : false,
            marketing: marketingCheckbox ? marketingCheckbox.checked : false
        };
        
        this.consentGiven = true;
        
        this.setCookie('cookie_consent', 'accepted', 365);
        this.setCookie('cookie_preferences', JSON.stringify(this.preferences), 365);
        
        this.sendConsentToServer(true, this.preferences);
        this.hideBanner();
        
        console.log('✅ Préférences sauvegardées:', this.preferences);
    }
    
    acceptAll() {
        this.preferences = {
            essential: true,
            analytics: true,
            preferences: true,
            marketing: true
        };
        
        this.consentGiven = true;
        
        this.setCookie('cookie_consent', 'accepted', 365);
        this.setCookie('cookie_preferences', JSON.stringify(this.preferences), 365);
        
        this.sendConsentToServer(true, this.preferences);
        this.hideBanner();
        
        console.log('✅ Tous les cookies acceptés');
    }
    
    refuseAll() {
        this.preferences = {
            essential: true,
            analytics: false,
            preferences: false,
            marketing: false
        };
        
        this.consentGiven = true;
        
        this.setCookie('cookie_consent', 'refused', 365);
        this.setCookie('cookie_preferences', JSON.stringify(this.preferences), 365);
        
        this.sendConsentToServer(false, this.preferences);
        this.hideBanner();
        
        console.log('❌ Tous les cookies refusés (sauf essentiels)');
    }
    
    hideBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.remove();
            console.log('🗑️ Bandeau de cookies supprimé');
        }
    }
    
    sendConsentToServer(consent, preferences) {
        fetch('collect_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                consent: consent,
                preferences: preferences,
                timestamp: new Date().toISOString(),
                userAgent: navigator.userAgent
            })
        })
        .then(response => response.json())
        .then(data => {
            console.log('📤 Consentement envoyé au serveur:', data);
        })
        .catch(error => {
            console.error('❌ Erreur lors de l\'envoi du consentement:', error);
        });
    }
    
    // METHODES DE GESTION DES COOKIES
    getCookie(name) {
        const nameEQ = name + "=";
        const ca = document.cookie.split(';');
        for (let i = 0; i < ca.length; i++) {
            let c = ca[i];
            while (c.charAt(0) === ' ') c = c.substring(1, c.length);
            if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
        }
        return null;
    }
    
    setCookie(name, value, days) {
        const expires = new Date();
        expires.setTime(expires.getTime() + (days * 24 * 60 * 60 * 1000));
        const cookieString = `${name}=${value};expires=${expires.toUTCString()};path=/;SameSite=Lax`;
        document.cookie = cookieString;
        console.log(`🍪 Cookie défini: ${name}=${value}`);
    }
    
    deleteCookie(name) {
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
        console.log('🗑️ Cookie supprimé: ' + name);
    }
    
    // METHODES PUBLIQUES POUR LES DEVELOPPEURS
    isAllowed(type) {
        return this.consentGiven && this.preferences[type];
    }
    
    getPreferences() {
        return this.preferences;
    }
    
    hasConsent() {
        return this.consentGiven;
    }
    
    revokeConsent() {
        console.log('🔄 Révocation du consentement...');
        
        this.preferences = {
            essential: true,
            analytics: false,
            preferences: false,
            marketing: false
        };
        
        this.deleteCookie('cookie_consent');
        this.deleteCookie('cookie_preferences');
        
        this.consentGiven = false;
        
        this.sendConsentToServer(false, this.preferences);
        
        setTimeout(() => {
            location.reload();
        }, 100);
    }
}

// INITIALISATION AUTOMATIQUE
function initializeCookieManager() {
    console.log('🔄 initializeCookieManager() appelée');
    
    if (window.cookieManager) {
        console.log('ℹ️ CookieManager déjà initialisé');
        return true;
    }
    
    if (typeof CookieManager === 'undefined') {
        console.error('❌ Classe CookieManager non définie');
        return false;
    }
    
    console.log('🚀 Création d\'une nouvelle instance CookieManager...');
    
    try {
        window.cookieManager = new CookieManager();
        console.log('✅ CookieManager initialisé avec succès');
        console.log('🔍 Instance créée:', window.cookieManager);
        console.log('🔍 Type de deleteCookie:', typeof window.cookieManager.deleteCookie);
        
        return true;
    } catch (error) {
        console.error('❌ Erreur lors de l\'initialisation:', error);
        return false;
    }
}

// TENTATIVE D'INITIALISATION
function attemptInitialization() {
    if (document.readyState === 'loading') {
        console.log('📄 DOM en cours de chargement, attente...');
        document.addEventListener('DOMContentLoaded', initializeCookieManager);
    } else {
        console.log('📄 DOM prêt, initialisation immédiate...');
        initializeCookieManager();
    }
}

// LANCEMENT DE L'INITIALISATION
attemptInitialization();

// API PUBLIQUE POUR LES DEVELOPPEURS
window.CookieAPI = {
    getInstance: () => window.cookieManager,
    isAllowed: (type) => window.cookieManager?.isAllowed(type) || false,
    getPreferences: () => window.cookieManager?.getPreferences() || {},
    hasConsent: () => window.cookieManager?.hasConsent() || false,
    showPreferences: () => window.cookieManager?.showPreferences(),
    revokeConsent: () => window.cookieManager?.revokeConsent()
};

// INITIALISATION DE SECOURS
setTimeout(() => {
    if (!window.cookieManager) {
        console.log('🔄 Tentative d\'initialisation de secours...');
        attemptInitialization();
    } else {
        console.log('✅ Instance CookieManager déjà présente');
        console.log('🔍 Vérification deleteCookie:', typeof window.cookieManager.deleteCookie);
    }
}, 500);
