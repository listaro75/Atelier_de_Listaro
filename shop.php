<?php
    include_once('_db/connexion_DB.php');
    include_once('_functions/auth.php');
    include_once('_functions/image_utils.php');
    session_start();

    // Récupération des catégories pour le filtre
    $categories = $DB->query("SELECT DISTINCT category FROM products ORDER BY category")->fetchAll();

    // Gestion des filtres
    $where = "1=1";
    $params = array();

    if(isset($_GET['category']) && !empty($_GET['category'])) {
        $where .= " AND category = ?";
        $params[] = $_GET['category'];
    }

    if(isset($_GET['search']) && !empty($_GET['search'])) {
        $where .= " AND (name LIKE ? OR description LIKE ?)";
        $search = "%".$_GET['search']."%";
        $params[] = $search;
        $params[] = $search;
    }

    // Gestion du tri
    $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
    $orderBy = "created_at DESC"; // Par défaut, tri par date (plus récents)

    switch($sort) {
        case 'price_asc':
            $orderBy = "price ASC";
            break;
        case 'price_desc':
            $orderBy = "price DESC";
            break;
        case 'name_asc':
            $orderBy = "name ASC";
            break;
        case 'name_desc':
            $orderBy = "name DESC";
            break;
        default:
            $orderBy = "created_at DESC";
    }

    // Récupération des produits avec tri
    $stmt = $DB->prepare("SELECT * FROM products WHERE $where ORDER BY $orderBy");
    $stmt->execute($params);
    $products = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Boutique</title>
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
        <h1>Notre Boutique</h1>
        
        <div class="shop-container">
            <!-- Filtres -->
            <div class="filters">
                <form id="filter-form" method="GET" action="">
                    <div class="search-bar">
                        <input type="text" name="search" placeholder="Rechercher un produit..." 
                               value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    </div>
                    
                    <div class="category-filter">
                        <select name="category" onchange="this.form.submit()">
                            <option value="">Toutes les catégories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?php echo htmlspecialchars($cat['category']); ?>"
                                    <?php if(isset($_GET['category']) && $_GET['category'] === $cat['category']) echo 'selected'; ?>>
                                    <?php echo htmlspecialchars($cat['category']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="sort-filter">
                        <select name="sort" id="sort" onchange="this.form.submit()">
                            <option value="newest" <?php echo isset($sort) && $sort === 'newest' ? 'selected' : ''; ?>>Plus récents</option>
                            <option value="price_asc" <?php echo isset($sort) && $sort === 'price_asc' ? 'selected' : ''; ?>>Prix croissant</option>
                            <option value="price_desc" <?php echo isset($sort) && $sort === 'price_desc' ? 'selected' : ''; ?>>Prix décroissant</option>
                            <option value="name_asc" <?php echo isset($sort) && $sort === 'name_asc' ? 'selected' : ''; ?>>Nom (A-Z)</option>
                            <option value="name_desc" <?php echo isset($sort) && $sort === 'name_desc' ? 'selected' : ''; ?>>Nom (Z-A)</option>
                        </select>
                    </div>
                </form>
            </div>

            <!-- Grille des produits -->
            <div class="products-grid">
                <?php if(empty($products)): ?>
                    <div style="grid-column: 1/-1; text-align: center; padding: 20px;">
                        <p>Aucun produit n'est disponible pour le moment.</p>
                    </div>
                <?php else: ?>
                    <?php foreach($products as $product): 
                        // Récupérer l'image principale du produit
                        $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1");
                        $stmt->execute([$product['id']]);
                        $mainImage = $stmt->fetch();

                        // Récupérer toutes les images du produit
                        $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
                        $stmt->execute([$product['id']]);
                        $images = $stmt->fetchAll();
                    ?>
                        <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-link">
                            <div class="product-card">
                                <div class="product-images">
                                    <?php 
                                    // Récupérer les images même si non connecté
                                    $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? ORDER BY is_main DESC");
                                    $stmt->execute([$product['id']]);
                                    $images = $stmt->fetchAll();
                                    
                                    if(!empty($images)): ?>
                                        <div class="image-slider">
                                            <?php foreach($images as $index => $image): 
                                                $imageUrl = getImageUrl($image['image_path']);
                                                ?>
                                                <?php if ($imageUrl === createPlaceholderImageUrl()): ?>
                                                    <div class="product-image-placeholder <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                         data-index="<?php echo $index; ?>">
                                                        Aucune image
                                                    </div>
                                                <?php else: ?>
                                                    <img src="<?php echo htmlspecialchars($imageUrl); ?>" 
                                                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                         class="product-image <?php echo $index === 0 ? 'active' : ''; ?>"
                                                         data-index="<?php echo $index; ?>"
                                                         loading="lazy"
                                                         onerror="this.style.display='none'; this.parentNode.querySelector('.product-image-placeholder')?.style.setProperty('display', 'flex');">
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if(count($images) > 1): ?>
                                            <button class="slider-arrow prev" onclick="prevImage(this, event)">&lt;</button>
                                            <button class="slider-arrow next" onclick="nextImage(this, event)">&gt;</button>
                                            <div class="image-dots">
                                                <?php foreach($images as $index => $image): ?>
                                                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                          onclick="showImage(this.closest('.product-images'), <?php echo $index; ?>, event)"></span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="product-image-placeholder">
                                            Aucune image disponible
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-price"><?php echo number_format($product['price'], 2); ?> €</div>
                                    <div class="product-actions">
                                        <?php
                                            $liked = false;
                                            if(is_logged()) {
                                                // Récupérer l'ID de l'utilisateur depuis la session
                                                $user_id = isset($_SESSION['id']) ? $_SESSION['id'] : null;
                                                
                                                if($user_id) {
                                                    $stmt = $DB->prepare("SELECT id FROM product_likes WHERE product_id = ? AND user_id = ?");
                                                    $stmt->execute([$product['id'], $user_id]);
                                                    $liked = $stmt->fetch() !== false;
                                                }
                                            }
                                            
                                            // Compter le nombre total de likes
                                            $stmt = $DB->prepare("SELECT COUNT(*) FROM product_likes WHERE product_id = ?");
                                            $stmt->execute([$product['id']]);
                                            $likes_count = $stmt->fetchColumn();
                                        ?>
                                        <button class="btn-like <?php echo $liked ? 'liked' : ''; ?>" 
                                                onclick="return toggleLike(this, <?php echo $product['id']; ?>, event)"
                                                <?php if(!is_logged()) echo 'title="Connectez-vous pour aimer ce produit"'; ?>>
                                            <i class="fas fa-heart"></i>
                                            <span class="likes-count"><?php echo $likes_count; ?></span>
                                        </button>
                                        <button class="add-to-cart <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>"
                                                onclick="event.preventDefault(); event.stopPropagation(); addToCart(<?php echo $product['id']; ?>)"
                                                <?php if($product['stock'] <= 0) echo 'disabled'; ?>>
                                            <?php echo $product['stock'] > 0 ? 'Ajouter au panier' : 'Rupture de stock'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Variables globales
    const isLoggedIn = <?php echo is_logged() ? 'true' : 'false'; ?>;

    // Fonctions pour le slider d'images
    function showImage(container, index, event) {
        if(event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const images = container.getElementsByClassName('product-image');
        const dots = container.getElementsByClassName('dot');
        
        Array.from(images).forEach(img => img.classList.remove('active'));
        Array.from(dots).forEach(dot => dot.classList.remove('active'));
        
        images[index].classList.add('active');
        if(dots.length > 0) {
            dots[index].classList.add('active');
        }
    }

    function nextImage(button, event) {
        if(event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const container = button.closest('.product-images');
        const images = container.getElementsByClassName('product-image');
        const currentIndex = Array.from(images).findIndex(img => img.classList.contains('active'));
        const nextIndex = (currentIndex + 1) % images.length;
        showImage(container, nextIndex);
    }

    function prevImage(button, event) {
        if(event) {
            event.preventDefault();
            event.stopPropagation();
        }
        const container = button.closest('.product-images');
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
                
                // Afficher une notification discrète
                const button = document.querySelector(`button[onclick*="addToCart(${productId})"]`);
                button.textContent = '✓ Ajouté';
                setTimeout(() => {
                    button.textContent = 'Ajouter au panier';
                }, 1000);
            } else {
                // Afficher le message d'erreur dans le bouton
                const button = document.querySelector(`button[onclick*="addToCart(${productId})"]`);
                const originalText = button.textContent;
                button.textContent = data.message || 'Erreur';
                setTimeout(() => {
                    button.textContent = originalText;
                }, 1000);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            const button = document.querySelector(`button[onclick*="addToCart(${productId})"]`);
            button.textContent = 'Erreur';
            setTimeout(() => {
                button.textContent = 'Ajouter au panier';
            }, 1000);
        });
    }

    // Fonction pour les likes
    function toggleLike(button, productId, event = null) {
        // Empêcher la propagation vers le lien parent
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        
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
            } else {
                console.error('Erreur:', data.message);
            }
        })
        .catch(error => console.error('Erreur:', error));
        
        return false; // Empêcher la propagation
    }

    // Initialisation du défilement automatique
    document.addEventListener('DOMContentLoaded', function() {
        const sliders = document.getElementsByClassName('product-images');
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

    <?php require_once('_footer/footer.php'); ?>
</body>
</html>
