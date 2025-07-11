<!-- Scripts JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="_js/modern.js"></script>
<script src="_functions/cookies.js"></script>
<script>
    // Initialiser le système de cookies RGPD
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🍪 Initialisation du système de cookies RGPD...');
        
        // Ajouter les styles CSS pour les cookies
        const cookieStyles = document.createElement('link');
        cookieStyles.rel = 'stylesheet';
        cookieStyles.href = '_css/cookies.css';
        document.head.appendChild(cookieStyles);
        
        // Le CookieManager s'initialise automatiquement
        console.log('✅ Système de cookies RGPD initialisé');
    });
</script>