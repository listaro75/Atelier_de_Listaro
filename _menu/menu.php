<?php
    include_once('_functions/auth.php');
    include_once('_functions/cart.php');
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
?>
<nav class="navbar">
    <div class="navbar-content">
        <!-- Bouton hamburger -->
        <button class="hamburger-menu" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <!-- Menu principal -->
        <div class="nav-main" id="mobileMenu">
            <a href="index.php">Accueil</a>
            <a href="prestation.php">Prestations</a>
            <a href="shop.php">Shop</a>
            <a href="portfolio.php">Portfolio</a>
        </div>
        
        <div class="auth">
            <!-- Panier -->
            <div class="cart-icon">
                <?php if(is_logged()): ?>
                    <a href="cart.php" class="cart-link">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="cart-count"><?php echo getCartCount(); ?></span>
                    </a>
                <?php else: ?>
                    <a href="#" class="cart-link" onclick="showLoginPopup(event)">
                        <i class="fas fa-shopping-cart"></i>
                    </a>
                <?php endif; ?>
            </div>
            
            <!-- Menu utilisateur -->
            <?php if(is_logged()): ?>
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" onclick="toggleDropdown(event)"><?php echo htmlspecialchars($_SESSION['pseudo']); ?></a>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="my_orders.php">Mes commandes</a>
                        <a href="profil.php">Mon Profil</a>
                        <?php if(is_admin()): ?>
                            <a href="admin_panel.php">admin</a>
                        <?php endif; ?>
                        <a href="deconnexion.php">Déconnexion</a>
                    </div>
                </div>
            <?php else: ?>
                <a href="inscription.php">Inscription</a>
                <a href="connexion.php">Connexion</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- Popup de connexion -->
<div class="popup-overlay" id="popupOverlay"></div>
<div class="login-popup" id="loginPopup">
    <h3>Connectez-vous pour accéder au panier</h3>
    <div class="popup-buttons">
        <a href="connexion.php" class="popup-login">Se connecter</a>
        <a href="#" class="popup-cancel" onclick="hideLoginPopup()">Annuler</a>
    </div>
</div>

<script>
function showLoginPopup(event) {
    event.preventDefault();
    document.getElementById('popupOverlay').style.display = 'block';
    document.getElementById('loginPopup').style.display = 'block';
}

function hideLoginPopup() {
    document.getElementById('popupOverlay').style.display = 'none';
    document.getElementById('loginPopup').style.display = 'none';
}

// Fermer la popup en cliquant sur l'overlay
document.getElementById('popupOverlay').addEventListener('click', hideLoginPopup);

function toggleMenu() {
    const menu = document.getElementById('mobileMenu');
    const hamburger = document.querySelector('.hamburger-menu');
    const body = document.body;
    
    menu.classList.toggle('active');
    hamburger.classList.toggle('active');
    
    // Empêcher le scroll quand le menu est ouvert
    if (menu.classList.contains('active')) {
        body.style.overflow = 'hidden';
    } else {
        body.style.overflow = '';
    }
}

// Fermer le menu mobile lors du clic sur un lien
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('mobileMenu');
    const menuLinks = mobileMenu.querySelectorAll('a');
    
    menuLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (window.innerWidth <= 768) {
                toggleMenu();
            }
        });
    });
});

// Fermer le menu mobile lors du redimensionnement
window.addEventListener('resize', function() {
    const menu = document.getElementById('mobileMenu');
    const hamburger = document.querySelector('.hamburger-menu');
    const body = document.body;
    
    if (window.innerWidth > 768) {
        menu.classList.remove('active');
        hamburger.classList.remove('active');
        body.style.overflow = '';
    }
});

// Fonction pour le menu déroulant au clic
function toggleDropdown(event) {
    event.preventDefault();
    event.stopPropagation();
    const dropdown = document.getElementById('userDropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Fermer le menu si on clique ailleurs, avec un délai
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const toggle = document.querySelector('.dropdown-toggle');
    
    if (dropdown && !dropdown.contains(event.target) && !toggle.contains(event.target)) {
        // Ajouter un délai pour faciliter la sélection
        setTimeout(function() {
            dropdown.style.display = 'none';
        }, 200);
    }
});

// Empêcher la fermeture immédiate lors du clic sur le menu
if (document.getElementById('userDropdown')) {
    document.getElementById('userDropdown').addEventListener('click', function(event) {
        // Empêcher la propagation pour éviter la fermeture immédiate
        event.stopPropagation();
    });
}
</script>