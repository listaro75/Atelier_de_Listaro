<?php
include_once('_db/connexion_DB.php');
include_once('_functions/auth.php');
session_start();

if (!isset($_GET['id'])) {
    header('Location: shop.php');
    exit;
}

$product_id = intval($_GET['id']);

// Récupérer les détails du produit
$stmt = $DB->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: shop.php');
    exit;
}

// Récupérer les images du produit (même si non connecté)
$stmt = $DB->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
$stmt->execute([$product_id]);
$images = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title><?php echo htmlspecialchars($product['name']); ?> - Boutique</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
        require_once('_head/script.php');
    ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="main-container">
        <div class="product-details">
            <div class="product-details-container">
                <div class="product-details-images">
                    <?php if(!empty($images)): ?>
                        <div class="image-slider">
                            <?php foreach($images as $index => $image): ?>
                                <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="product-image <?php echo $index === 0 ? 'active' : ''; ?>"
                                     data-index="<?php echo $index; ?>">
                            <?php endforeach; ?>
                        </div>
                        <?php if(count($images) > 1): ?>
                            <button class="slider-arrow prev" onclick="prevImage(this)">&lt;</button>
                            <button class="slider-arrow next" onclick="nextImage(this)">&gt;</button>
                            <div class="image-dots">
                                <?php foreach($images as $index => $image): ?>
                                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                          onclick="showImage(this.parentNode.parentNode, <?php echo $index; ?>)"></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="product-details-info">
                    <h1 class="product-details-name"><?php echo htmlspecialchars($product['name']); ?></h1>
                    <p class="product-details-description"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                    <div class="product-details-price"><?php echo number_format($product['price'], 2); ?> €</div>
                    
                    <div class="product-info">
                        <p class="stock-info <?php echo $product['stock'] < 5 ? 'low-stock' : ''; ?>">
                            <?php 
                            if ($product['stock'] > 0) {
                                echo $product['stock'] > 10 
                                    ? "En stock" 
                                    : "Plus que " . $product['stock'] . " en stock";
                            } else {
                                echo "Rupture de stock";
                            }
                            ?>
                        </p>
                    </div>
                    
                    <div class="product-actions">
                        <?php
                            $liked = false;
                            if(is_logged()) {
                                $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
                                if($user_id) {
                                    $stmt = $DB->prepare("SELECT id FROM product_likes WHERE product_id = ? AND user_id = ?");
                                    $stmt->execute([$product['id'], $user_id]);
                                    $liked = $stmt->fetch() !== false;
                                }
                            }
                            
                            $stmt = $DB->prepare("SELECT COUNT(*) FROM product_likes WHERE product_id = ?");
                            $stmt->execute([$product['id']]);
                            $likes_count = $stmt->fetchColumn();
                        ?>
                        <button class="btn-like <?php echo $liked ? 'liked' : ''; ?>" 
                                onclick="toggleLike(this, <?php echo $product['id']; ?>)"
                                <?php if(!is_logged()) echo 'title="Connectez-vous pour aimer ce produit"'; ?>>
                            <i class="fas fa-heart"></i>
                            <span class="likes-count"><?php echo $likes_count; ?></span>
                        </button>
                        <button class="add-to-cart <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>"
                                onclick="addToCart(<?php echo $product['id']; ?>)"
                                <?php if($product['stock'] <= 0) echo 'disabled'; ?>>
                            <?php echo $product['stock'] > 0 ? 'Ajouter au panier' : 'Rupture de stock'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Variables globales
    const isLoggedIn = <?php echo is_logged() ? 'true' : 'false'; ?>;

    // Fonctions pour le slider d'images
    function showImage(container, index) {
        const images = container.getElementsByClassName('product-image');
        const dots = container.getElementsByClassName('dot');
        
        Array.from(images).forEach(img => img.classList.remove('active'));
        Array.from(dots).forEach(dot => dot.classList.remove('active'));
        
        images[index].classList.add('active');
        dots[index].classList.add('active');
    }

    function nextImage(button) {
        const container = button.parentNode;
        const images = container.getElementsByClassName('product-image');
        const currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
        const nextIndex = (currentIndex + 1) % images.length;
        showImage(container, nextIndex);
    }

    function prevImage(button) {
        const container = button.parentNode;
        const images = container.getElementsByClassName('product-image');
        const currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
        const prevIndex = (currentIndex - 1 + images.length) % images.length;
        showImage(container, prevIndex);
    }

    // Fonction pour le panier
    function addToCart(productId) {
        if (!isLoggedIn) {
            window.location.href = 'connexion.php';
            return;
        }

        // Vérifier le stock disponible
        const stockInfo = document.querySelector('.stock-info');
        const currentStock = <?php echo $product['stock']; ?>;
        
        if (currentStock <= 0) {
            const button = document.querySelector('.add-to-cart');
            button.textContent = 'Rupture de stock';
            button.disabled = true;
            return;
        }

        fetch('ajax/update_cart.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}&action=increase`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour le compteur du panier
                document.querySelector('.cart-count').textContent = data.cart_count;
                
                // Mettre à jour l'affichage du stock
                const newStock = currentStock - 1;
                stockInfo.textContent = newStock > 10 
                    ? "En stock" 
                    : "Plus que " + newStock + " en stock";
                
                if (newStock < 5) {
                    stockInfo.classList.add('low-stock');
                }
                
                if (newStock <= 0) {
                    const button = document.querySelector('.add-to-cart');
                    button.textContent = 'Rupture de stock';
                    button.disabled = true;
                }

                // Afficher une notification
                const button = document.querySelector('.add-to-cart');
                const originalText = button.textContent;
                button.textContent = '✓ Ajouté';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 1000);
            } else {
                // Afficher le message d'erreur dans le bouton
                const button = document.querySelector('.add-to-cart');
                const originalText = button.textContent;
                button.textContent = data.message || 'Erreur';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            const button = document.querySelector('.add-to-cart');
            const originalText = button.textContent;
            button.textContent = 'Erreur';
            setTimeout(() => {
                button.textContent = originalText;
            }, 1000);
        });
    }

    // Fonction pour les likes
    function toggleLike(button, productId) {
        if (!isLoggedIn) {
            window.location.href = 'connexion.php';
            return;
        }

        fetch('ajax/toggle_product_like.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `product_id=${productId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const likesCount = button.querySelector('.likes-count');
                likesCount.textContent = data.likes_count;
                
                if (data.is_liked) {
                    button.classList.add('liked');
                } else {
                    button.classList.remove('liked');
                }
                
                button.querySelector('i').classList.add('pulse');
                setTimeout(() => {
                    button.querySelector('i').classList.remove('pulse');
                }, 500);
            }
        })
        .catch(error => console.error('Erreur:', error));
    }

    // Initialisation du défilement automatique
    document.addEventListener('DOMContentLoaded', function() {
        const sliders = document.getElementsByClassName('product-details-images');
        Array.from(sliders).forEach(slider => {
            if (slider.getElementsByClassName('product-image').length > 1) {
                setInterval(() => {
                    const nextButton = slider.querySelector('.slider-arrow.next');
                    if (nextButton && !slider.matches(':hover')) {
                        nextImage(nextButton);
                    }
                }, 5000);
            }
        });
    });
    </script>

    <style>
    /* Styles pour l'affichage du stock */
    .stock-info {
        font-size: 0.9em;
        padding: 5px 10px;
        border-radius: 4px;
        display: inline-block;
        margin: 10px 0;
        background-color: #e8f5e9;
        color: #2e7d32;
    }

    .stock-info.low-stock {
        background-color: #fff3e0;
        color: #ef6c00;
    }

    .stock-info:empty {
        display: none;
    }
    </style>

    <?php require_once('_footer/footer.php'); ?>
</body>
</html> 