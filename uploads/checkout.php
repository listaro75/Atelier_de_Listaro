<?php
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');
session_start();

// Configurer les cookies de session de manière sécurisée
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Lax');

// Vérifier si l'utilisateur est connecté
if (!is_logged()) {
    header('Location: connexion.php');
    exit();
}

// Récupérer le panier de l'utilisateur
$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// Calculer le total
$total = 0;
foreach ($cart_items as $item) {
    $stmt = $DB->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->execute([$item['product_id']]);
    $product = $stmt->fetch();
    $total += $product['price'] * $item['quantity'];
}

// Stocker le total initial dans une variable JavaScript
echo "<script>const baseTotal = " . $total . ";</script>";

// Forcer HTTPS en production
if (!isset($_SERVER['HTTPS']) && $_SERVER['HTTP_HOST'] !== 'localhost') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit();
}

// Ajouter l'en-tête X-Frame-Options
header('X-Frame-Options: SAMEORIGIN');
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Procéder au paiement</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <!-- Méta-tags de sécurité -->
    <meta http-equiv="Content-Security-Policy" content="frame-src 'self' https://*.stripe.com">
    <meta http-equiv="X-Frame-Options" content="SAMEORIGIN">
    
    <!-- Charger Stripe.js de manière optimisée -->
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <!-- Étapes du processus -->
        <div class="checkout-steps">
            <div class="step active" id="step1">
                <div class="step-number">1</div>
                <span>Livraison</span>
            </div>
            <div class="step" id="step2">
                <div class="step-number">2</div>
                <span>Adresse</span>
            </div>
            <div class="step" id="step3">
                <div class="step-number">3</div>
                <span>Paiement</span>
            </div>
        </div>

        <!-- Section 1: Options de livraison -->
        <div class="checkout-section" id="shipping-section">
            <h3>Mode de livraison</h3>
            <div class="shipping-options">
                <div class="shipping-option" onclick="selectShipping(this, 5.99)">
                    <div class="shipping-option-left">
                        <input type="radio" name="shipping" value="standard" id="shipping-standard" required>
                        <label for="shipping-standard">
                            <h4>Livraison standard</h4>
                            <p>3-5 jours ouvrés</p>
                        </label>
                    </div>
                    <div class="shipping-price">5.99 €</div>
                </div>
                <div class="shipping-option" onclick="selectShipping(this, 9.99)">
                    <div class="shipping-option-left">
                        <input type="radio" name="shipping" value="express" id="shipping-express" required>
                        <label for="shipping-express">
                            <h4>Livraison express</h4>
                            <p>1-2 jours ouvrés</p>
                        </label>
                    </div>
                    <div class="shipping-price">9.99 €</div>
                </div>
            </div>
        </div>

        <!-- Section 2: Formulaire d'adresse -->
        <div class="checkout-section" id="address-section" style="display: none;">
            <h3>Adresse de livraison</h3>
            <form class="address-form" id="address-form">
                <div class="form-group">
                    <label for="firstname">Prénom</label>
                    <input type="text" id="firstname" name="firstname" required>
                </div>
                <div class="form-group">
                    <label for="lastname">Nom</label>
                    <input type="text" id="lastname" name="lastname" required>
                </div>
                <div class="form-group full-width">
                    <label for="address">Adresse</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="postal">Code postal</label>
                    <input type="text" id="postal" name="postal" required>
                </div>
                <div class="form-group">
                    <label for="city">Ville</label>
                    <input type="text" id="city" name="city" required>
                </div>
                <div class="form-group full-width">
                    <label for="phone">Téléphone</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>
            </form>
        </div>

        <!-- Section 3: Paiement -->
        <div class="checkout-section" id="payment-section" style="display: none;">
            <h3>Paiement</h3>
            <form id="payment-form">
                <div class="form-group">
                    <div id="card-element">
                        <!-- Stripe injectera ici l'iframe du formulaire de carte -->
                    </div>
                    <div id="card-errors" class="error-message"></div>
                </div>
            </form>
        </div>

        <!-- Récapitulatif de la commande -->
        <div class="order-summary">
            <div class="summary-row">
                <span>Sous-total</span>
                <span><?php echo number_format($total, 2); ?> €</span>
            </div>
            <div class="summary-row">
                <span>Frais de livraison</span>
                <span id="shipping-cost">0.00 €</span>
            </div>
            <div class="summary-row total">
                <span>Total</span>
                <span id="total-amount"><?php echo number_format($total, 2); ?> €</span>
            </div>
        </div>

        <!-- Boutons de navigation -->
        <div class="checkout-buttons">
            <button class="btn-back" onclick="previousStep()" style="display: none;">Retour</button>
            <button class="btn-next" onclick="nextStep()">Continuer</button>
        </div>
    </div>

    <script>
    let currentStep = 1;
    let shippingCost = 0;
    let stripe;
    let card;

    function selectShipping(option, cost) {
        // Arrêter la propagation de l'événement
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        // Sélectionner le radio button
        const radioInput = option.querySelector('input[type="radio"]');
        if (radioInput) {
            // Déselectionner tous les autres
            document.querySelectorAll('input[name="shipping"]').forEach(input => {
                input.checked = false;
                input.closest('.shipping-option').classList.remove('selected');
            });
            
            // Sélectionner celui-ci
            radioInput.checked = true;
            option.classList.add('selected');
            
            // Mettre à jour le coût
            shippingCost = cost;
            updateTotal();
            
            // Debug
            console.log('Sélection mode de livraison:', {
                value: radioInput.value,
                cost: cost,
                checked: radioInput.checked
            });
        }
    }

    function updateTotal() {
        document.getElementById('shipping-cost').textContent = shippingCost.toFixed(2) + ' €';
        const newTotal = baseTotal + shippingCost;
        document.getElementById('total-amount').textContent = newTotal.toFixed(2) + ' €';
    }

    function nextStep() {
        if (currentStep < 3) {
            if (currentStep === 1 && !document.querySelector('.shipping-option.selected')) {
                alert('Veuillez sélectionner un mode de livraison');
                return;
            }

            if (currentStep === 2) {
                const inputs = document.querySelectorAll('#address-section input');
                let isValid = true;
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.borderColor = 'red';
                    }
                });
                if (!isValid) {
                    alert('Veuillez remplir tous les champs de l\'adresse');
                    return;
                }
            }

            document.getElementById(`step${currentStep}`).classList.remove('active');
            currentStep++;
            document.getElementById(`step${currentStep}`).classList.add('active');

            document.getElementById('shipping-section').style.display = currentStep === 1 ? 'block' : 'none';
            document.getElementById('address-section').style.display = currentStep === 2 ? 'block' : 'none';
            document.getElementById('payment-section').style.display = currentStep === 3 ? 'block' : 'none';

            document.querySelector('.btn-back').style.display = 'block';

            document.querySelector('.btn-next').textContent = currentStep === 3 ? 'Confirmer la commande' : 'Continuer';
        } else {
            processPayment();
        }
    }

    function previousStep() {
        if (currentStep > 1) {
            document.getElementById(`step${currentStep}`).classList.remove('active');
            currentStep--;
            document.getElementById(`step${currentStep}`).classList.add('active');

            document.getElementById('shipping-section').style.display = currentStep === 1 ? 'block' : 'none';
            document.getElementById('address-section').style.display = currentStep === 2 ? 'block' : 'none';
            document.getElementById('payment-section').style.display = currentStep === 3 ? 'block' : 'none';

            document.querySelector('.btn-back').style.display = currentStep === 1 ? 'none' : 'block';

            document.querySelector('.btn-next').textContent = 'Continuer';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Initialiser Stripe
        stripe = Stripe('pk_test_51R9bAN4ZLqIaT6NtFJHi6ezvYP2dSRnrwp6BhjuPqI4KEJVk4fWFisXcs8kUJheuA3WdQtmkTrCPXMuYohSqNUHn00SAfnYJ7K', {
            locale: 'fr',
            apiVersion: '2023-10-16'
        });
        
        const elements = stripe.elements({
            fonts: [{ cssSrc: 'https://fonts.googleapis.com/css?family=Roboto' }]
        });

        // Créer l'élément de carte
        card = elements.create('card', {
            style: {
                base: {
                    fontFamily: 'Roboto, sans-serif',
                    fontSize: '16px',
                    color: '#32325d',
                    '::placeholder': { color: '#aab7c4' }
                },
                invalid: {
                    color: '#dc3545',
                    iconColor: '#dc3545'
                }
            }
        });

        // Monter l'élément de carte
        card.mount('#card-element');

        // Gérer les changements de l'élément de carte
        card.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });
    });

    async function processPayment() {
        if (!stripe || !card) {
            alert('Le système de paiement n\'est pas encore chargé. Veuillez patienter.');
            return;
        }

        // Vérifier si un mode de livraison est sélectionné
        const selectedShipping = document.querySelector('input[name="shipping"]:checked');
        if (!selectedShipping) {
            alert('Veuillez sélectionner un mode de livraison');
            return;
        }

        try {
            document.querySelector('.btn-next').disabled = true;
            document.querySelector('.btn-next').textContent = 'Traitement en cours...';

            // Créer la méthode de paiement
            const { paymentMethod, error } = await stripe.createPaymentMethod({
                type: 'card',
                card: card,
                billing_details: {
                    name: document.getElementById('firstname').value + ' ' + document.getElementById('lastname').value,
                    address: {
                        line1: document.getElementById('address').value,
                        postal_code: document.getElementById('postal').value,
                        city: document.getElementById('city').value,
                    }
                }
            });

            if (error) {
                throw error;
            }

            // Préparer les données de la commande
            const orderData = {
                payment_method_id: paymentMethod.id,
                shipping_method: selectedShipping.value,
                shipping_cost: shippingCost,
                total_amount: baseTotal + shippingCost,
                shipping_address: {
                    firstname: document.getElementById('firstname').value,
                    lastname: document.getElementById('lastname').value,
                    address: document.getElementById('address').value,
                    postal: document.getElementById('postal').value,
                    city: document.getElementById('city').value,
                    phone: document.getElementById('phone').value
                }
            };

            console.log('Données de la commande:', orderData); // Debug

            const response = await fetch('ajax/save_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(orderData)
            });

            // Vérifier si la réponse est OK
            if (!response.ok) {
                throw new Error(`Erreur HTTP: ${response.status}`);
            }

            // Vérifier si la réponse contient du JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Réponse non-JSON reçue du serveur');
            }

            const text = await response.text();
            console.log('Réponse brute:', text); // Debug

            let result;
            try {
                result = JSON.parse(text);
            } catch (e) {
                throw new Error('Réponse JSON invalide: ' + text);
            }

            if (result.success) {
                showPaymentSuccess();
            } else {
                throw new Error(result.message || 'Erreur de paiement');
            }
        } catch (error) {
            console.error('Erreur complète:', error);
            const errorElement = document.getElementById('card-errors');
            errorElement.textContent = error.message;
        } finally {
            document.querySelector('.btn-next').disabled = false;
            document.querySelector('.btn-next').textContent = 'Confirmer la commande';
        }
    }

    function showPaymentSuccess() {
        const successDiv = document.createElement('div');
        successDiv.className = 'payment-success';
        successDiv.innerHTML = `
            <div class="success-content">
                <i class="fas fa-check-circle"></i>
                <h2>Paiement réussi !</h2>
                <p>Votre commande a été confirmée</p>
                <p>Total payé : ${document.getElementById('total-amount').textContent}</p>
                <p>Vous allez être redirigé...</p>
            </div>
        `;

        document.querySelector('.main-container').appendChild(successDiv);

        setTimeout(() => {
            window.location.href = 'confirmation.php';
        }, 3000);
    }
    </script>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 