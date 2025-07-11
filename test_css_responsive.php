n<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test CSS Shop</title>
    <link rel="stylesheet" href="_css/stylesheet.css">
    <link rel="stylesheet" href="_css/responsive.css">
    <link rel="stylesheet" href="_css/images.css">
    <style>
        .debug-info {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 10px;
            border-radius: 5px;
            font-size: 12px;
            z-index: 9999;
        }
        .test-container {
            max-width: 1200px;
            margin: 100px auto 50px;
            padding: 20px;
        }
        .test-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .test-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
        }
    </style>
</head>
<body>
    <div class="debug-info">
        Écran: <span id="screenSize"></span><br>
        Responsive: <span id="responsiveStatus"></span>
    </div>

    <?php include '_menu/menu.php'; ?>
    
    <div class="test-container">
        <h1>Test CSS et Responsive</h1>
        
        <div class="test-grid">
            <div class="test-card">
                <h3>Card Test 1</h3>
                <div class="product-image-placeholder">Placeholder Test</div>
                <p>Test du CSS responsive</p>
            </div>
            
            <div class="test-card">
                <h3>Card Test 2</h3>
                <img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iI2YwZjBmMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwsIHNhbnMtc2VyaWYiIGZvbnQtc2l6ZT0iMTYiIGZpbGw9IiM2NjYiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIj5UZXN0PC90ZXh0Pjwvc3ZnPg==" 
                     class="product-image" alt="Test">
                <p>Test avec image SVG</p>
            </div>
            
            <div class="test-card">
                <h3>Card Test 3</h3>
                <div style="background: linear-gradient(135deg, #ff6b6b, #4ecdc4); height: 100px; border-radius: 4px; margin: 10px 0;"></div>
                <p>Test avec gradient</p>
            </div>
        </div>
        
        <div class="products-grid" style="margin-top: 40px;">
            <div class="product-card">
                <div class="product-images">
                    <div class="product-image-placeholder">Test Placeholder</div>
                </div>
                <div class="product-info">
                    <h3>Produit Test Grid</h3>
                    <div class="product-price">19.99 €</div>
                </div>
            </div>
            
            <div class="product-card">
                <div class="product-images">
                    <div class="product-image-placeholder">Test Placeholder 2</div>
                </div>
                <div class="product-info">
                    <h3>Produit Test Grid 2</h3>
                    <div class="product-price">29.99 €</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateDebugInfo() {
            document.getElementById('screenSize').textContent = window.innerWidth + 'x' + window.innerHeight;
            
            let status = 'Desktop';
            if (window.innerWidth <= 480) status = 'Mobile';
            else if (window.innerWidth <= 768) status = 'Tablet';
            else if (window.innerWidth <= 1199) status = 'Laptop';
            
            document.getElementById('responsiveStatus').textContent = status;
        }
        
        updateDebugInfo();
        window.addEventListener('resize', updateDebugInfo);
        
        // Test du menu mobile
        if (typeof toggleMenu === 'function') {
            console.log('✅ toggleMenu function found');
        } else {
            console.log('❌ toggleMenu function NOT found');
        }
    </script>
</body>
</html>
