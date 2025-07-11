<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Bouton Like</title>
    <link rel="stylesheet" href="_css/stylesheet.css">
    <link rel="stylesheet" href="_css/images.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .test-container {
            max-width: 800px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .test-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .test-info {
            flex: 1;
        }
        .test-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <?php
    include '_db/connexion_DB.php';
    include '_functions/auth.php';
    session_start();
    
    $isLoggedIn = is_logged();
    ?>
    
    <div class="test-container">
        <h1>🧪 Test Bouton Like</h1>
        
        <div class="test-section">
            <h2>État de Connexion</h2>
            <p>Vous êtes <?php echo $isLoggedIn ? '✅ <strong>connecté</strong>' : '❌ <strong>non connecté</strong>'; ?></p>
            <?php if (!$isLoggedIn): ?>
                <p><a href="connexion.php">Se connecter</a> pour tester les likes</p>
            <?php endif; ?>
        </div>
        
        <div class="test-section">
            <h2>Produits de Test</h2>
            
            <?php
            $stmt = $DB->query("SELECT id, name, price FROM products LIMIT 3");
            $products = $stmt->fetchAll();
            
            foreach ($products as $product):
                // Vérifier si l'utilisateur a liké ce produit
                $liked = false;
                if ($isLoggedIn) {
                    $user_id = $_SESSION['id'] ?? null;
                    if ($user_id) {
                        $stmt = $DB->prepare("SELECT id FROM product_likes WHERE product_id = ? AND user_id = ?");
                        $stmt->execute([$product['id'], $user_id]);
                        $liked = $stmt->fetch() !== false;
                    }
                }
                
                // Compter les likes
                $stmt = $DB->prepare("SELECT COUNT(*) FROM product_likes WHERE product_id = ?");
                $stmt->execute([$product['id']]);
                $likes_count = $stmt->fetchColumn();
            ?>
                <div class="test-card">
                    <div class="test-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p>Prix: <?php echo number_format($product['price'], 2); ?> €</p>
                        <p>ID Produit: <?php echo $product['id']; ?></p>
                    </div>
                    
                    <div class="test-actions">
                        <button class="btn-like <?php echo $liked ? 'liked' : ''; ?>" 
                                onclick="return toggleLikeTest(this, <?php echo $product['id']; ?>, event)"
                                title="Cliquer pour liker/unliker">
                            <i class="fas fa-heart"></i>
                            <span class="likes-count"><?php echo $likes_count; ?></span>
                        </button>
                        
                        <span style="color: #666; font-size: 12px;">
                            Status: <span class="like-status"><?php echo $liked ? 'Liké' : 'Non liké'; ?></span>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="test-section">
            <h2>🔗 Navigation</h2>
            <p>
                <a href="shop.php">🛒 Retour au Shop</a> | 
                <a href="debug_shop.php">🔍 Debug</a>
            </p>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
        
        function toggleLikeTest(button, productId, event) {
            event.preventDefault();
            event.stopPropagation();
            
            console.log('🔍 Test Like:', { productId, isLoggedIn });
            
            if (!isLoggedIn) {
                alert('Vous devez être connecté pour liker un produit');
                return false;
            }

            // Ajouter un indicateur de chargement
            const originalContent = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;

            fetch('ajax/toggle_product_like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}`
            })
            .then(response => response.json())
            .then(data => {
                console.log('📦 Réponse:', data);
                
                // Restaurer le bouton
                button.innerHTML = originalContent;
                button.disabled = false;
                
                if (data.success) {
                    const likesCount = button.querySelector('.likes-count');
                    const statusSpan = button.closest('.test-actions').querySelector('.like-status');
                    
                    likesCount.textContent = data.likes_count;
                    
                    if (data.is_liked) {
                        button.classList.add('liked');
                        statusSpan.textContent = 'Liké';
                    } else {
                        button.classList.remove('liked');
                        statusSpan.textContent = 'Non liké';
                    }
                    
                    // Animation
                    button.querySelector('i').classList.add('pulse');
                    setTimeout(() => {
                        button.querySelector('i').classList.remove('pulse');
                    }, 500);
                    
                    // Message de succès
                    const action = data.is_liked ? 'liké' : 'unliké';
                    console.log(`✅ Produit ${action} avec succès`);
                    
                } else {
                    console.error('❌ Erreur:', data.message);
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('🚨 Erreur réseau:', error);
                alert('Erreur de connexion');
                
                // Restaurer le bouton
                button.innerHTML = originalContent;
                button.disabled = false;
            });
            
            return false;
        }
    </script>
</body>
</html>
