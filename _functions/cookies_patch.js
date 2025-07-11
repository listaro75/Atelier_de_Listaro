/**
 * PATCH URGENT - deleteCookie
 * Ajoute la méthode deleteCookie au prototype CookieManager
 */

// Attendre que CookieManager soit défini
function applyDeleteCookiePatch() {
    if (typeof CookieManager === 'undefined') {
        console.log('⏳ CookieManager non défini, retry dans 100ms...');
        setTimeout(applyDeleteCookiePatch, 100);
        return;
    }
    
    console.log('🔧 Application du patch deleteCookie...');
    
    // Vérifier si la méthode existe déjà
    if (typeof CookieManager.prototype.deleteCookie === 'function') {
        console.log('✅ deleteCookie déjà présente');
        return;
    }
    
    // Ajouter la méthode au prototype
    CookieManager.prototype.deleteCookie = function(name) {
        console.log('🔧 deleteCookie (patched) appelée pour:', name);
        document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
        console.log('✅ Cookie supprimé via patch:', name);
    };
    
    console.log('✅ Patch deleteCookie appliqué avec succès');
    
    // Vérifier que le patch fonctionne
    const testInstance = new CookieManager();
    if (typeof testInstance.deleteCookie === 'function') {
        console.log('✅ deleteCookie disponible sur instance');
    } else {
        console.error('❌ deleteCookie toujours indisponible après patch');
    }
    
    // Mettre à jour l'instance globale si elle existe
    if (window.cookieManager && typeof window.cookieManager.deleteCookie !== 'function') {
        console.log('🔄 Mise à jour de l\'instance globale...');
        
        // Ajouter la méthode directement à l'instance
        window.cookieManager.deleteCookie = function(name) {
            console.log('🔧 deleteCookie (instance patch) appelée pour:', name);
            document.cookie = name + '=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/';
            console.log('✅ Cookie supprimé via instance patch:', name);
        };
        
        console.log('✅ Instance globale mise à jour');
    }
}

// Appliquer le patch
applyDeleteCookiePatch();
