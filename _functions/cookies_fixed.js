/**
 * GESTIONNAIRE DE COOKIES RGPD - VERSION CORRIGÉE
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
        
        console.log('🍪 CookieManager initialisé');
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
            const prefs = this.getCookie('cookie_preferences');
            if (prefs) {
                try {
                    this.preferences = JSON.parse(prefs);
                    console.log('✅ Préférences récupérées:', this.preferences);
                } catch (e) {
                    console.error('❌ Erreur lors du parsing des préférences:', e);
                }
            }
        } else {
            console.log('ℹ️ Aucun consentement trouvé');
        }
    }
    
    showBannerIfNeeded() {
        const consent = this.getCookie('cookie_consent');
        if (!consent) {
            console.log('📢 Affichage du bandeau de cookies...');
            this.addCookieBanner();
        } else {
            console.log('ℹ️ Bandeau non affiché - consentement déjà donné');
        }
    }
    
    addCookieBanner() {
        // Vérifier si le bandeau existe déjà
        if (document.getElementById('cookie-banner')) {
            console.log('ℹ️ Bandeau déjà présent');
            return;
        }
        
        const banner = document.createElement('div');
        banner.id = 'cookie-banner';
        banner.className = 'cookie-banner show';
        banner.innerHTML = `
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
        `;
        
        document.body.appendChild(banner);
        console.log('✅ Bandeau de cookies ajouté au DOM');
        
        // Attacher les événements spécifiques au bandeau
        this.attachBannerEvents();
    }
    
    attachBannerEvents() {
        const acceptAllBtn = document.getElementById('cookie-accept-all');
        const customizeBtn = document.getElementById('cookie-customize');
        const refuseBtn = document.getElementById('cookie-refuse');
        
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => {
                console.log('✅ Acceptation de tous les cookies');
                this.acceptAllCookies();
            });
        }
        
        if (customizeBtn) {
            customizeBtn.addEventListener('click', () => {
                console.log('⚙️ Ouverture des préférences');
                this.openPreferences();
            });
        }
        
        if (refuseBtn) {
            refuseBtn.addEventListener('click', () => {
                console.log('❌ Refus des cookies');
                this.refuseAllCookies();
            });
        }
    }
    
    addPreferencesModal() {
        // Vérifier si la modale existe déjà
        if (document.getElementById('cookie-preferences-modal')) {
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
                            <input type="checkbox" checked disabled>
                        </div>
                        <p>Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés. Ils incluent les cookies de session, d'authentification et de sécurité.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies analytiques</h3>
                            <input type="checkbox" id="analytics-cookies" name="analytics">
                        </div>
                        <p>Ces cookies nous aident à comprendre comment vous utilisez notre site pour l'améliorer. Ils collectent des informations anonymes sur votre navigation.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies de préférences</h3>
                            <input type="checkbox" id="preferences-cookies" name="preferences">
                        </div>
                        <p>Ces cookies sauvegardent vos préférences et personnalisent votre expérience sur le site.</p>
                    </div>
                    
                    <div class="cookie-category">
                        <div class="category-header">
                            <h3>Cookies marketing</h3>
                            <input type="checkbox" id="marketing-cookies" name="marketing">
                        </div>
                        <p>Ces cookies sont utilisés pour vous proposer des publicités personnalisées et mesurer l'efficacité de nos campagnes.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="save-preferences" class="btn btn-primary">Sauvegarder les préférences</button>
                    <button id="accept-all-modal" class="btn btn-secondary">Accepter tout</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        console.log('✅ Modale de préférences ajoutée');
    }
    
    addCookieIndicator() {
        // Vérifier si l'indicateur existe déjà
        if (document.getElementById('cookie-indicator')) {
            return;
        }
        
        const indicator = document.createElement('div');
        indicator.id = 'cookie-indicator';
        indicator.className = 'cookie-indicator';
        indicator.innerHTML = `
            <button id="cookie-settings" class="cookie-settings-btn" title="Paramètres des cookies">
                🍪
            </button>
        `;
        
        document.body.appendChild(indicator);
        console.log('✅ Indicateur de cookies ajouté');
    }
    
    attachEvents() {
        // Événements pour fermer la modale
        document.addEventListener('click', (e) => {
            if (e.target.id === 'close-preferences' || e.target.id === 'cookie-preferences-modal') {
                this.closePreferences();
            }
        });
        
        // Événements pour l'indicateur
        document.addEventListener('click', (e) => {
            if (e.target.id === 'cookie-settings') {
                this.openPreferences();
            }
        });
        
        // Événements pour la modale de préférences
        document.addEventListener('click', (e) => {
            if (e.target.id === 'save-preferences') {
                this.savePreferences();
            }
            if (e.target.id === 'accept-all-modal') {
                this.acceptAllCookies();
            }
        });
        
        console.log('✅ Événements attachés');
    }
    
    acceptAllCookies() {
        this.preferences = {
            essential: true,
            analytics: true,
            preferences: true,
            marketing: true
        };
        
        this.setCookie('cookie_consent', 'accepted', 365);
        this.setCookie('cookie_preferences', JSON.stringify(this.preferences), 365);
        this.consentGiven = true;
        
        this.sendConsent('accepted', this.preferences);
        this.hideBanner();
        this.closePreferences();
        
        console.log('✅ Tous les cookies acceptés');
    }
    
    refuseAllCookies() {
        this.preferences = {
            essential: true,
            analytics: false,
            preferences: false,
            marketing: false
        };
        
        this.setCookie('cookie_consent', 'refused', 365);
        this.setCookie('cookie_preferences', JSON.stringify(this.preferences), 365);
        this.consentGiven = false;
        
        this.sendConsent('refused', this.preferences);
        this.hideBanner();
        
        console.log('❌ Cookies refusés');
    }
    
    openPreferences() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            modal.style.display = 'block';
            this.loadPreferences();
            console.log('⚙️ Préférences ouvertes');
        }
    }
    
    closePreferences() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (modal) {
            modal.style.display = 'none';
            console.log('❌ Préférences fermées');
        }
    }
    
    loadPreferences() {
        const checkboxes = {
            'analytics-cookies': this.preferences.analytics,
            'preferences-cookies': this.preferences.preferences,
            'marketing-cookies': this.preferences.marketing
        };
        
        Object.entries(checkboxes).forEach(([id, checked]) => {
            const checkbox = document.getElementById(id);
            if (checkbox) {
                checkbox.checked = checked;
            }
        });
    }
    
    savePreferences() {
        const checkboxes = {
            analytics: document.getElementById('analytics-cookies'),
            preferences: document.getElementById('preferences-cookies'),
            marketing: document.getElementById('marketing-cookies')
        };
        
        Object.entries(checkboxes).forEach(([key, checkbox]) => {
            if (checkbox) {
                this.preferences[key] = checkbox.checked;
            }
        });
        
        this.setCookie('cookie_consent', 'customized', 365);
        this.setCookie('cookie_preferences', JSON.stringify(this.preferences), 365);
        this.consentGiven = true;
        
        this.sendConsent('customized', this.preferences);
        this.hideBanner();
        this.closePreferences();
        
        console.log('✅ Préférences sauvegardées:', this.preferences);
    }
    
    hideBanner() {
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.classList.add('hide');
            setTimeout(() => {
                banner.remove();
            }, 300);
            console.log('✅ Bandeau masqué');
        }
    }
    
    sendConsent(action, preferences) {
        const data = {
            action: action,
            preferences: preferences,
            timestamp: new Date().toISOString(),
            user_agent: navigator.userAgent,
            ip: null // Sera rempli côté serveur
        };
        
        fetch('cookie_consent.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log('✅ Consentement envoyé:', data);
        })
        .catch(error => {
            console.error('❌ Erreur envoi consentement:', error);
        });
    }
    
    collectData() {
        if (this.consentGiven && this.preferences.analytics) {
            const data = {
                page: window.location.pathname,
                referrer: document.referrer,
                user_agent: navigator.userAgent,
                timestamp: new Date().toISOString(),
                session_id: this.getSessionId()
            };
            
            fetch('collect_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('📊 Données collectées:', data);
            })
            .catch(error => {
                console.error('❌ Erreur collecte données:', error);
            });
        }
    }
    
    getSessionId() {
        let sessionId = this.getCookie('session_id');
        if (!sessionId) {
            sessionId = 'sess_' + Math.random().toString(36).substr(2, 9) + '_' + Date.now();
            this.setCookie('session_id', sessionId, 1);
        }
        return sessionId;
    }
    
    setCookie(name, value, days) {
        const expires = new Date(Date.now() + days * 24 * 60 * 60 * 1000).toUTCString();
        document.cookie = `${name}=${value}; expires=${expires}; path=/; SameSite=Strict`;
        console.log(`🍪 Cookie défini: ${name}=${value}`);
    }
    
    getCookie(name) {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; ${name}=`);
        if (parts.length === 2) {
            return parts.pop().split(';').shift();
        }
        return null;
    }
    
    deleteCookie(name) {
        document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
        console.log(`🗑️ Cookie supprimé: ${name}`);
    }
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM chargé, initialisation du CookieManager...');
    window.cookieManager = new CookieManager();
});

// Export pour usage externe
window.CookieManager = CookieManager;
