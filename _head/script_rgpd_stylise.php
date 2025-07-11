<!-- Scripts RGPD stylisé et centré pour Atelier de Listaro -->
<script src="_functions/cookies.js"></script>
<style>
/* ===== SYSTÈME RGPD ATELIER DE LISTARO - VERSION CENTRÉE ===== */

/* Reset pour éviter les conflits */
.cookie-banner, .cookie-banner * {
    box-sizing: border-box !important;
}

/* Espacement du body quand le bandeau est affiché */
body.cookie-banner-shown {
    padding-bottom: 120px !important;
}

/* Bandeau RGPD principal - FIXÉ EN BAS */
.cookie-banner {
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    width: 100% !important;
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%) !important;
    color: #ecf0f1 !important;
    padding: 25px !important;
    z-index: 99999 !important; /* Plus haute priorité */
    box-shadow: 0 -5px 25px rgba(0,0,0,0.3) !important;
    border-top: 3px solid #e74c3c !important;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif !important;
    text-align: center !important;
    margin: 0 !important;
    transform: none !important; /* Éviter les conflits de transform */
}

.cookie-banner-content {
    max-width: 1200px !important;
    margin: 0 auto !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 20px !important;
    position: relative !important;
}

.cookie-banner h3 {
    color: #e74c3c;
    margin: 0 0 15px 0;
    font-size: 1.4em;
    font-weight: bold;
    text-align: center; /* TITRE CENTRÉ */
}

.cookie-banner p {
    margin: 0 0 20px 0;
    line-height: 1.6;
    opacity: 0.95;
    max-width: 800px;
    text-align: center; /* TEXTE CENTRÉ */
    font-size: 16px;
}

.cookie-buttons {
    display: flex;
    gap: 15px;
    justify-content: center; /* BOUTONS CENTRÉS */
    flex-wrap: wrap;
    width: 100%;
}

.cookie-banner button {
    padding: 14px 28px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    font-size: 15px;
    transition: all 0.3s ease;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    min-width: 140px; /* LARGEUR UNIFORME */
    text-align: center;
}

/* Boutons stylisés */
.accept-all {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
    box-shadow: 0 4px 15px rgba(46, 204, 113, 0.3);
}

.accept-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(46, 204, 113, 0.4);
}

.refuse-all {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.3);
}

.refuse-all:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
}

.preferences {
    background: linear-gradient(135deg, #34495e, #2c3e50);
    color: white;
    border: 2px solid #ecf0f1;
    box-shadow: 0 4px 15px rgba(52, 73, 94, 0.3);
}

.preferences:hover {
    background: linear-gradient(135deg, #2c3e50, #34495e);
    transform: translateY(-2px);
}

/* Modal de préférences - CENTRÉ */
#cookie-preferences-modal {
    z-index: 9999 !important;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(44, 62, 80, 0.95);
    display: none;
    backdrop-filter: blur(5px);
    text-align: center;
}

.cookie-modal-content {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 15px;
    padding: 40px;
    max-width: 650px;
    width: 90%;
    max-height: 85vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    text-align: center; /* CONTENU MODAL CENTRÉ */
}

.cookie-modal-content h2 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
    font-size: 2.2em;
    border-bottom: 3px solid #e74c3c;
    padding-bottom: 15px;
}

.cookie-modal-content p {
    text-align: center;
    margin-bottom: 30px;
    line-height: 1.6;
    color: #555;
    font-size: 16px;
}

.cookie-category {
    margin: 25px auto;
    padding: 25px;
    border: 2px solid #ecf0f1;
    border-radius: 12px;
    background: #f8f9fa;
    max-width: 500px;
    text-align: center;
}

.cookie-category h3 {
    color: #2c3e50;
    margin-bottom: 15px;
    font-size: 1.3em;
    text-align: center;
}

.cookie-category p {
    text-align: center;
    margin-bottom: 15px;
    color: #666;
    line-height: 1.5;
}

.cookie-category label {
    display: flex;
    align-items: center;
    justify-content: center; /* LABELS CENTRÉS */
    gap: 12px;
    cursor: pointer;
    font-weight: 500;
    margin-top: 15px;
}

.modal-buttons {
    display: flex;
    gap: 15px;
    justify-content: center; /* BOUTONS MODAL CENTRÉS */
    margin-top: 30px;
    flex-wrap: wrap;
}

.modal-buttons button {
    padding: 12px 25px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    min-width: 130px;
    transition: all 0.3s ease;
}

/* Indicateur RGPD - CENTRÉ */
#cookie-indicator {
    position: fixed;
    bottom: 20px;
    right: 50%;
    transform: translateX(50%); /* CENTRÉ HORIZONTALEMENT */
    z-index: 9998;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 15px 25px;
    border-radius: 50px;
    cursor: pointer;
    box-shadow: 0 5px 20px rgba(231, 76, 60, 0.4);
    font-weight: 600;
    transition: all 0.3s ease;
    font-size: 14px;
    text-align: center;
}

#cookie-indicator:hover {
    transform: translateX(50%) translateY(-3px) scale(1.05);
    box-shadow: 0 8px 25px rgba(231, 76, 60, 0.5);
}

#cookie-indicator::before {
    content: '🍪';
    margin-right: 8px;
    font-size: 16px;
}

/* Responsive - Maintien du centrage */
@media (max-width: 768px) {
    .cookie-banner-content {
        text-align: center;
        padding: 0 15px;
    }
    
    .cookie-buttons {
        justify-content: center;
        width: 100%;
        gap: 10px;
    }
    
    .cookie-banner button {
        flex: 1;
        min-width: 100px;
        max-width: 160px;
    }
    
    .cookie-modal-content {
        padding: 25px 20px;
        margin: 20px;
        width: calc(100% - 40px);
    }
    
    .modal-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .modal-buttons button {
        width: 80%;
        max-width: 250px;
    }
    
    #cookie-indicator {
        right: 20px;
        transform: none;
        left: 50%;
        transform: translateX(-50%);
    }
}

/* Animation d'apparition */
@keyframes slideUp {
    from {
        transform: translateY(100%);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.cookie-banner {
    animation: slideUp 0.5s ease-out !important;
}

/* Forcer le positionnement en cas de conflit */
.cookie-banner.show {
    display: block !important;
    position: fixed !important;
    bottom: 0 !important;
    left: 0 !important;
    right: 0 !important;
    z-index: 99999 !important;
}

/* Animation du modal */
@keyframes fadeIn {
    from { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
    to { opacity: 1; transform: translate(-50%, -50%) scale(1); }
}

#cookie-preferences-modal.show .cookie-modal-content {
    animation: fadeIn 0.3s ease-out;
}
</style>
