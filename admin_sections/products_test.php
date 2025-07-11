<?php
// VERSION SIMPLIFIÉE POUR DIAGNOSTIC
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Test de base
echo "<!-- DEBUG: Début du fichier -->\n";

try {
    include_once(__DIR__ . '/../_db/connexion_DB.php');
    echo "<!-- DEBUG: DB connectée -->\n";
} catch (Exception $e) {
    echo "<!-- DEBUG: Erreur DB: " . $e->getMessage() . " -->\n";
    exit("Erreur de connexion à la base de données");
}

try {
    include_once(__DIR__ . '/../_functions/auth.php');
    echo "<!-- DEBUG: Auth chargé -->\n";
} catch (Exception $e) {
    echo "<!-- DEBUG: Erreur Auth: " . $e->getMessage() . " -->\n";
    exit("Erreur d'authentification");
}

// Vérifier l'admin
if (!function_exists('is_admin')) {
    echo "<!-- DEBUG: Fonction is_admin n'existe pas -->\n";
    exit("Fonction is_admin manquante");
}

if (!is_admin()) {
    echo "<!-- DEBUG: Pas admin -->\n";
    exit("Accès refusé - pas admin");
}

echo "<!-- DEBUG: Admin OK -->\n";

// Récupérer les produits
try {
    $stmt = $DB->query("SELECT * FROM products ORDER BY id DESC LIMIT 10");
    $products = $stmt->fetchAll();
    echo "<!-- DEBUG: " . count($products) . " produits récupérés -->\n";
} catch (Exception $e) {
    echo "<!-- DEBUG: Erreur récupération produits: " . $e->getMessage() . " -->\n";
    $products = [];
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Section Produits</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .btn { background: #3498db; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin: 5px; }
        .btn-success { background: #27ae60; }
        .btn-danger { background: #e74c3c; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f2f2f2; }
        .alert { padding: 15px; margin: 15px 0; border-radius: 5px; }
        .alert-success { background: #d4edda; color: #155724; }
        .alert-danger { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

<h1>🧪 Test Section Produits</h1>

<div style="margin: 20px 0;">
    <button class="btn btn-success" onclick="testAddProduct()">➕ Test Ajouter</button>
    <button class="btn" onclick="testAPI()">🔍 Test API</button>
    <button class="btn btn-danger" onclick="testDelete()">🗑️ Test Supprimer</button>
</div>

<h2>📦 Produits (<?php echo count($products); ?>)</h2>

<?php if (empty($products)): ?>
    <div class="alert alert-danger">
        ❌ Aucun produit trouvé. 
        <button class="btn" onclick="addSampleProduct()">Ajouter un produit de test</button>
    </div>
<?php else: ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nom</th>
                <th>Prix</th>
                <th>Catégorie</th>
                <th>Stock</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['id']; ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo $product['price']; ?> €</td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td><?php echo $product['stock']; ?></td>
                    <td>
                        <button class="btn" onclick="editProduct(<?php echo $product['id']; ?>)">✏️</button>
                        <button class="btn btn-danger" onclick="deleteProduct(<?php echo $product['id']; ?>)">🗑️</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<h2>📝 Ajouter un produit</h2>
<form method="POST" style="background: #f9f9f9; padding: 20px; border-radius: 5px;">
    <input type="hidden" name="action" value="add_simple_product">
    
    <div style="margin: 10px 0;">
        <label>Nom:</label><br>
        <input type="text" name="name" required style="width: 300px; padding: 5px;">
    </div>
    
    <div style="margin: 10px 0;">
        <label>Prix:</label><br>
        <input type="number" name="price" step="0.01" required style="width: 100px; padding: 5px;">
    </div>
    
    <div style="margin: 10px 0;">
        <label>Catégorie:</label><br>
        <select name="category" required style="width: 200px; padding: 5px;">
            <option value="">Sélectionner</option>
            <option value="Lampes">Lampes</option>
            <option value="Décoration">Décoration</option>
            <option value="Mobilier">Mobilier</option>
            <option value="Accessoires">Accessoires</option>
        </select>
    </div>
    
    <div style="margin: 10px 0;">
        <label>Stock:</label><br>
        <input type="number" name="stock" min="0" value="1" style="width: 100px; padding: 5px;">
    </div>
    
    <div style="margin: 20px 0;">
        <button type="submit" class="btn btn-success">Ajouter</button>
    </div>
</form>

<?php
// Traitement simple
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_simple_product') {
        try {
            $stmt = $DB->prepare("INSERT INTO products (name, description, price, category, stock) VALUES (?, ?, ?, ?, ?)");
            $result = $stmt->execute([
                $_POST['name'],
                'Description de test',
                $_POST['price'],
                $_POST['category'],
                $_POST['stock']
            ]);
            
            if ($result) {
                echo "<div class='alert alert-success'>✅ Produit ajouté avec succès! <a href=''>Recharger</a></div>";
            } else {
                echo "<div class='alert alert-danger'>❌ Erreur lors de l'ajout</div>";
            }
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>❌ Erreur: " . $e->getMessage() . "</div>";
        }
    }
}
?>

<script>
function testAddProduct() {
    alert('Test ajout produit');
}

function testAPI() {
    fetch('get_product.php?id=1')
        .then(response => response.text())
        .then(data => {
            alert('Réponse API: ' + data.substring(0, 100));
        })
        .catch(error => {
            alert('Erreur API: ' + error);
        });
}

function testDelete() {
    alert('Test suppression');
}

function editProduct(id) {
    alert('Édition produit ID: ' + id);
}

function deleteProduct(id) {
    if (confirm('Supprimer le produit ' + id + ' ?')) {
        // Test de suppression
        fetch('products_test.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: 'action=delete_product&product_id=' + id
        })
        .then(response => response.text())
        .then(data => {
            alert('Résultat: ' + data);
            location.reload();
        })
        .catch(error => {
            alert('Erreur: ' + error);
        });
    }
}

function addSampleProduct() {
    fetch('products_test.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'action=add_simple_product&name=Produit Test&price=19.99&category=Décoration&stock=5'
    })
    .then(response => response.text())
    .then(data => {
        alert('Produit test ajouté');
        location.reload();
    })
    .catch(error => {
        alert('Erreur: ' + error);
    });
}
</script>

</body>
</html>
