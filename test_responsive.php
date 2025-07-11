<!DOCTYPE html>
<html lang="fr">
<head>
    <?php include '_head/meta.php'; ?>
    <title>Test Responsive - Atelier de Listaro</title>
    <?php include '_head/link.php'; ?>
</head>
<body>
    <?php include '_menu/menu.php'; ?>
    
    <div class="main-container">
        <h1>Test Responsive</h1>
        <p>Cette page teste le responsive de votre site.</p>
        
        <div class="products-grid">
            <div class="product-card">
                <h3>Produit Test 1</h3>
                <p>Test responsive</p>
            </div>
            <div class="product-card">
                <h3>Produit Test 2</h3>
                <p>Test responsive</p>
            </div>
            <div class="product-card">
                <h3>Produit Test 3</h3>
                <p>Test responsive</p>
            </div>
            <div class="product-card">
                <h3>Produit Test 4</h3>
                <p>Test responsive</p>
            </div>
        </div>
        
        <div class="form-container">
            <h2>Test Formulaire</h2>
            <form>
                <div class="form-row">
                    <div class="form-group">
                        <label>Nom</label>
                        <input type="text" placeholder="Votre nom">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" placeholder="Votre email">
                    </div>
                </div>
                <div class="form-group">
                    <label>Message</label>
                    <textarea placeholder="Votre message"></textarea>
                </div>
                <button type="submit" class="btn">Envoyer</button>
            </form>
        </div>
    </div>
    
    <?php include '_footer/footer.php'; ?>
    <?php include '_head/script.php'; ?>
</body>
</html>
