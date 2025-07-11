/**
 * SYSTÈME DE COOKIES RGPD SIMPLE
 * Version simplifiée qui s'affiche automatiquement
 */

// Fonction pour créer et afficher le bandeau de cookies
function showCookieBanner() {
    // Vérifier si le consentement existe déjà
    const consent = getCookie('cookie_consent');
    if (consent) {
        return; // Ne pas afficher le bandeau si le consentement existe
    }
    
    // Créer le bandeau
    const banner = document.createElement('div');
    banner.id = 'cookie-banner';
    banner.style.cssText = `
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
        color: white;
        padding: 20px;
        box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
        z-index: 10000;
        font-family: Arial, sans-serif;
        animation: slideUp 0.5s ease-out;
    `;
    
    banner.innerHTML = `
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 300px;">
                <h3 style="margin: 0 0 10px 0; font-size: 1.2em;">🍪 Gestion des cookies</h3>
                <p style="margin: 0; font-size: 0.9em; opacity: 0.9;">
                    Nous utilisons des cookies pour améliorer votre expérience sur notre site. 
                    Vous pouvez accepter tous les cookies ou personnaliser vos préférences.
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-shrink: 0; flex-wrap: wrap;">
                <button id="cookie-accept-all" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                    Accepter tout
                </button>
                <button id="cookie-customize" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                    Personnaliser
                </button>
                <button id="cookie-refuse" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; font-size: 14px;">
                    Refuser
                </button>
            </div>
        </div>
    `;
    
    // Ajouter les styles d'animation
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        #cookie-banner button:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        
        @media (max-width: 768px) {
            #cookie-banner > div {
                flex-direction: column;
                gap: 15px;
            }
            
            #cookie-banner button {
                flex: 1;
                min-width: 120px;
            }
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(banner);
    
    // Attacher les événements
    document.getElementById('cookie-accept-all').addEventListener('click', function() {
        setCookie('cookie_consent', 'accepted', 365);
        setCookie('cookie_preferences', JSON.stringify({
            essential: true,
            analytics: true,
            preferences: true,
            marketing: true
        }), 365);
        
        saveCookieConsent(true, {
            essential: true,
            analytics: true,
            preferences: true,
            marketing: true
        });
        
        banner.style.animation = 'slideUp 0.5s ease-out reverse';
        setTimeout(() => banner.remove(), 500);
    });
    
    document.getElementById('cookie-refuse').addEventListener('click', function() {
        setCookie('cookie_consent', 'refused', 365);
        setCookie('cookie_preferences', JSON.stringify({
            essential: true,
            analytics: false,
            preferences: false,
            marketing: false
        }), 365);
        
        saveCookieConsent(false, {
            essential: true,
            analytics: false,
            preferences: false,
            marketing: false
        });
        
        banner.style.animation = 'slideUp 0.5s ease-out reverse';
        setTimeout(() => banner.remove(), 500);
    });
    
    document.getElementById('cookie-customize').addEventListener('click', function() {
        showCookiePreferences();
    });
}

// Fonction pour afficher les préférences
function showCookiePreferences() {
    const modal = document.createElement('div');
    modal.id = 'cookie-preferences-modal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0,0,0,0.5);
        z-index: 10001;
        display: flex;
        align-items: center;
        justify-content: center;
        font-family: Arial, sans-serif;
    `;
    
    modal.innerHTML = `
        <div style="background: white; border-radius: 10px; max-width: 600px; width: 90%; max-height: 80vh; overflow-y: auto;">
            <div style="padding: 20px; border-bottom: 1px solid #eee;">
                <h2 style="margin: 0; color: #333;">🍪 Préférences des cookies</h2>
                <button id="close-preferences" style="position: absolute; top: 15px; right: 15px; background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">&times;</button>
            </div>
            
            <div style="padding: 20px;">
                <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin: 0; color: #333;">Cookies essentiels</h3>
                        <input type="checkbox" checked disabled>
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        Ces cookies sont nécessaires au fonctionnement du site et ne peuvent pas être désactivés.
                    </p>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin: 0; color: #333;">Cookies analytiques</h3>
                        <input type="checkbox" id="analytics-checkbox">
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        Ces cookies nous aident à comprendre comment vous utilisez notre site pour l'améliorer.
                    </p>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin: 0; color: #333;">Cookies de préférences</h3>
                        <input type="checkbox" id="preferences-checkbox">
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        Ces cookies mémorisent vos préférences pour personnaliser votre expérience.
                    </p>
                </div>
                
                <div style="margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h3 style="margin: 0; color: #333;">Cookies marketing</h3>
                        <input type="checkbox" id="marketing-checkbox">
                    </div>
                    <p style="margin: 0; color: #666; font-size: 14px;">
                        Ces cookies sont utilisés pour vous proposer des publicités pertinentes.
                    </p>
                </div>
            </div>
            
            <div style="padding: 20px; border-top: 1px solid #eee; display: flex; gap: 10px; justify-content: flex-end;">
                <button id="save-preferences" style="background: #28a745; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                    Sauvegarder
                </button>
                <button id="cancel-preferences" style="background: #6c757d; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">
                    Annuler
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Fermer la modal
    document.getElementById('close-preferences').addEventListener('click', () => modal.remove());
    document.getElementById('cancel-preferences').addEventListener('click', () => modal.remove());
    
    // Sauvegarder les préférences
    document.getElementById('save-preferences').addEventListener('click', function() {
        const preferences = {
            essential: true,
            analytics: document.getElementById('analytics-checkbox').checked,
            preferences: document.getElementById('preferences-checkbox').checked,
            marketing: document.getElementById('marketing-checkbox').checked
        };
        
        const hasConsent = preferences.analytics || preferences.preferences || preferences.marketing;
        
        setCookie('cookie_consent', hasConsent ? 'accepted' : 'refused', 365);
        setCookie('cookie_preferences', JSON.stringify(preferences), 365);
        
        saveCookieConsent(hasConsent, preferences);
        
        modal.remove();
        
        // Supprimer le bandeau s'il existe
        const banner = document.getElementById('cookie-banner');
        if (banner) {
            banner.style.animation = 'slideUp 0.5s ease-out reverse';
            setTimeout(() => banner.remove(), 500);
        }
    });
}

// Fonction pour sauvegarder le consentement en base de données
function saveCookieConsent(consent, preferences) {
    fetch('cookie_consent.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            consent: consent,
            preferences: preferences
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            console.log('✅ Consentement sauvegardé en base de données');
        } else {
            console.error('❌ Erreur sauvegarde:', data.error);
        }
    })
    .catch(error => {
        console.error('❌ Erreur réseau:', error);
    });
}

// Fonctions utilitaires
function getCookie(name) {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return parts.pop().split(';').shift();
    return null;
}

function setCookie(name, value, days) {
    const expires = new Date(Date.now() + days * 864e5).toUTCString();
    document.cookie = `${name}=${value}; expires=${expires}; path=/`;
}

// Initialisation automatique
document.addEventListener('DOMContentLoaded', function() {
    console.log('🍪 Initialisation du système de cookies simple...');
    
    // Attendre un peu pour que la page se charge
    setTimeout(function() {
        showCookieBanner();
    }, 1000);
});

// Créer un indicateur de cookies
function createCookieIndicator() {
    const indicator = document.createElement('div');
    indicator.id = 'cookie-indicator';
    indicator.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #007bff;
        color: white;
        padding: 10px 15px;
        border-radius: 20px;
        cursor: pointer;
        z-index: 9999;
        font-size: 14px;
        font-family: Arial, sans-serif;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    `;
    indicator.innerHTML = '🍪 Cookies';
    indicator.title = 'Gérer les préférences de cookies';
    
    indicator.addEventListener('click', function() {
        showCookiePreferences();
    });
    
    document.body.appendChild(indicator);
}

// Créer l'indicateur après le chargement
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        createCookieIndicator();
    }, 2000);
});
