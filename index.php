<?php
	include_once('_db/connexion_DB.php');
	include_once('_functions/auth.php');
	session_start();

	// Récupérer les produits les plus aimés (top 4)
	$stmt = $DB->prepare("
		SELECT p.*, COUNT(pl.id) as likes_count 
		FROM products p 
		LEFT JOIN product_likes pl ON p.id = pl.product_id 
		GROUP BY p.id 
		ORDER BY likes_count DESC 
		LIMIT 4
	");
	$stmt->execute();
	$top_products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<title>Accueil</title>
	<?php	
		require_once('_head/meta.php');
		require_once('_head/link.php');
		require_once('_head/script.php');
	?>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
	<?php
		require_once('_menu/menu.php');
	?>
    <div class="main-container">
        <h1>Bienvenue</h1>
        
        <!-- Section des produits les plus aimés -->
        <?php if(!empty($top_products)): ?>
            <section class="top-products">
                <h2>Nos produits les plus appréciés</h2>
                <div class="products-grid">
                    <?php foreach($top_products as $product): 
                        // Récupérer l'image principale
                        $stmt = $DB->prepare("SELECT image_path FROM product_images WHERE product_id = ? AND is_main = 1");
                        $stmt->execute([$product['id']]);
                        $main_image = $stmt->fetch();
                    ?>
                        <div class="product-card">
                            <a href="product_details.php?id=<?php echo $product['id']; ?>" class="product-link">
                                <div class="product-images">
                                    <?php if($main_image): ?>
                                        <img src="<?php echo htmlspecialchars($main_image['image_path']); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="product-image active">
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                                    <div class="product-price"><?php echo number_format($product['price'], 2); ?> €</div>
                                </div>
                            </a>
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
                                ?>
                                <button class="btn-like <?php echo $liked ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(this, <?php echo $product['id']; ?>)"
                                        <?php if(!is_logged()) echo 'title="Connectez-vous pour aimer ce produit"'; ?>>
                                    <i class="fas fa-heart"></i>
                                    <span class="likes-count"><?php echo $product['likes_count']; ?></span>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

        <!-- Reste du contenu de la page d'accueil -->
    </div>

    <script>
    const isLoggedIn = <?php echo is_logged() ? 'true' : 'false'; ?>;

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
    </script>

	<?php
		require_once('_footer/footer.php');
	?>
</body>
</html>