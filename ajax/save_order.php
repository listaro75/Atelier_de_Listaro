<?php
// Activer l'affichage des erreurs pour le débogage
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php-error.log');

// Définir l'en-tête de réponse comme JSON
header('Content-Type: application/json');

// Fonction pour envoyer une réponse JSON
function sendJsonResponse($success, $message, $data = []) {
    try {
        $response = json_encode(array_merge(
            ['success' => $success, 'message' => $message],
            $data
        ));
        
        if ($response === false) {
            error_log("Erreur JSON encode: " . json_last_error_msg());
            echo json_encode(['success' => false, 'message' => 'Erreur serveur interne']);
        } else {
            echo $response;
        }
    } catch (Exception $e) {
        error_log("Erreur sendJsonResponse: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur interne']);
    }
    exit;
}

try {
    // Vérifier que les fichiers requis existent
    $requiredFiles = [
        '../stripe-php/init.php',
        '../_db/connexion_DB.php',
        '../_functions/auth.php'
    ];
    
    foreach ($requiredFiles as $file) {
        if (!file_exists($file)) {
            error_log("Fichier manquant: " . $file);
            sendJsonResponse(false, "Fichier manquant: " . basename($file));
        }
    }

    require_once('../stripe-php/init.php');
    include_once('../_db/connexion_DB.php');
    include_once('../_functions/auth.php');
    
    session_start();

    // Debug complet de la session et de l'authentification
    error_log("=== DEBUG COMPLET ===");
    error_log("Session ID: " . session_id());
    error_log("Session status: " . session_status());
    error_log("Session data: " . print_r($_SESSION, true));
    error_log("Cookie session: " . print_r($_COOKIE, true));
    error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Request path: " . $_SERVER['REQUEST_URI']);
    error_log("Current script: " . __FILE__);

    // Vérifier le chemin du fichier auth.php
    $authFile = realpath('../_functions/auth.php');
    error_log("Auth file path: " . $authFile);
    error_log("Auth file exists: " . (file_exists($authFile) ? 'yes' : 'no'));

    // Vérifier la fonction is_logged
    error_log("is_logged function exists: " . (function_exists('is_logged') ? 'yes' : 'no'));

    if (function_exists('is_logged')) {
        error_log("is_logged() result: " . (is_logged() ? 'true' : 'false'));
    }

    // Vérifier la connexion à la base de données
    error_log("DB connection exists: " . (isset($DB) ? 'yes' : 'no'));

    if (isset($DB)) {
        try {
            $stmt = $DB->query("SELECT COUNT(*) as count FROM users");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            error_log("Total users in database: " . $result['count']);
        } catch (Exception $e) {
            error_log("DB error: " . $e->getMessage());
        }
    }

    // Vérifier si la session est active
    if (session_status() !== PHP_SESSION_ACTIVE) {
        error_log("Session non active");
        sendJsonResponse(false, 'Session non active');
    }

    // Vérifier l'authentification
    if (!function_exists('is_logged')) {
        error_log("Fonction is_logged() non trouvée");
        sendJsonResponse(false, 'Erreur de configuration');
    }

    if (!is_logged()) {
        error_log("Fonction is_logged() retourne false");
        sendJsonResponse(false, 'Utilisateur non connecté');
    }

    // Vérifier que l'ID utilisateur existe dans la session
    if (!isset($_SESSION['id'])) {
        error_log("Pas d'ID utilisateur dans la session");
        sendJsonResponse(false, 'ID utilisateur non trouvé dans la session');
    }

    // Vérifier que l'ID est un nombre valide
    if (!is_numeric($_SESSION['id']) || $_SESSION['id'] <= 0) {
        error_log("ID utilisateur invalide: " . $_SESSION['id']);
        sendJsonResponse(false, 'ID utilisateur invalide');
    }

    // Vérifier que l'utilisateur existe dans la base de données
    try {
        error_log("Tentative de recherche de l'utilisateur ID: " . $_SESSION['id']);
        
        // Vérifier d'abord la connexion à la base de données
        if (!$DB) {
            error_log("Connexion DB non établie");
            sendJsonResponse(false, 'Erreur de connexion à la base de données');
        }

        // Vérifier la table user (au lieu de users)
        $stmt = $DB->query("SHOW TABLES LIKE 'user'");
        if ($stmt->rowCount() === 0) {
            error_log("Table 'user' non trouvée");
            sendJsonResponse(false, 'Table utilisateur non trouvée');
        }

        // Afficher la structure de la table user
        $stmt = $DB->query("DESCRIBE user");
        error_log("Structure de la table user : " . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));

        // Rechercher l'utilisateur
        $stmt = $DB->prepare("SELECT * FROM user WHERE id = ?");
        $stmt->execute([$_SESSION['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            error_log("Aucun utilisateur trouvé avec l'ID: " . $_SESSION['id']);
            
            // Vérifier tous les utilisateurs (pour le débogage)
            $stmt = $DB->query("SELECT id, pseudo, email FROM user");
            error_log("Liste des utilisateurs disponibles : " . print_r($stmt->fetchAll(PDO::FETCH_ASSOC), true));
            
            sendJsonResponse(false, 'Utilisateur non trouvé dans la base de données');
        } else {
            error_log("Utilisateur trouvé : " . print_r($user, true));
        }

    } catch (Exception $e) {
        error_log("Erreur lors de la vérification de l'utilisateur: " . $e->getMessage());
        error_log("Trace: " . $e->getTraceAsString());
        sendJsonResponse(false, 'Erreur lors de la vérification de l\'utilisateur');
    }

    // Récupérer et vérifier les données JSON
    $input = file_get_contents('php://input');
    if (empty($input)) {
        sendJsonResponse(false, 'Aucune donnée reçue');
    }

    error_log("Données reçues: " . $input);
    
    $data = json_decode($input, true);
    if ($data === null) {
        sendJsonResponse(false, 'Données JSON invalides: ' . json_last_error_msg());
    }

    // Vérifier les données requises
    $required = ['payment_method_id', 'shipping_method', 'shipping_cost', 'total_amount', 'shipping_address'];
    foreach ($required as $field) {
        if (!isset($data[$field])) {
            sendJsonResponse(false, "Champ manquant: $field");
        }
    }

    // Vérifier la connexion à la base de données
    if (!isset($DB) || !($DB instanceof PDO)) {
        error_log("Erreur connexion DB: DB non initialisée ou invalide");
        sendJsonResponse(false, "Erreur de connexion à la base de données");
    }

    // Configuration de Stripe
    try {
        \Stripe\Stripe::setApiKey('sk_test_51R9bAN4ZLqIaT6NtcoH4C44ziY5yE84oX2AJQIsj5JKLWWE4sjJZr7sGyKCNLIN4ZohDfDineIvT1YrYs54J3ujy00DUkUohmm');
        
        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => intval($data['total_amount'] * 100),
            'currency' => 'eur',
            'payment_method' => $data['payment_method_id'],
            'confirm' => true,
            'return_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/confirmation.php',
            'payment_method_types' => ['card']
        ]);
        
        error_log("PaymentIntent créé: " . $paymentIntent->id);
        
    } catch (\Stripe\Exception\ApiErrorException $e) {
        error_log("Erreur Stripe: " . $e->getMessage());
        sendJsonResponse(false, 'Erreur de paiement: ' . $e->getMessage());
    }

    // Traitement de la commande
    if ($paymentIntent->status === 'succeeded') {
        try {
            $DB->beginTransaction();

            // Vérifier d'abord si l'utilisateur existe dans la table user
            $stmt = $DB->prepare("SELECT id FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Utilisateur invalide');
            }

            // Insérer la commande principale
            $stmt = $DB->prepare("
                INSERT INTO orders (
                    user_id, shipping_method, shipping_cost, total_amount, 
                    shipping_address, payment_method, status, stripe_payment_id
                ) VALUES (?, ?, ?, ?, ?, ?, 'payé', ?)
            ");

            if (!$stmt->execute([
                $_SESSION['id'],
                $data['shipping_method'],
                floatval($data['shipping_cost']),
                floatval($data['total_amount']),
                json_encode($data['shipping_address']),
                'card',
                $paymentIntent->id
            ])) {
                throw new Exception('Erreur lors de l\'insertion de la commande');
            }

            $orderId = $DB->lastInsertId();
            error_log("Commande créée avec ID: " . $orderId);

            // Insérer les produits de la commande
            if (!empty($_SESSION['cart'])) {
                // Préparer la requête pour récupérer les prix des produits
                $stmt = $DB->prepare("SELECT id, price FROM products WHERE id = ?");
                
                // Préparer la requête d'insertion des items
                $insertStmt = $DB->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, quantity, price
                    ) VALUES (?, ?, ?, ?)
                ");

                foreach ($_SESSION['cart'] as $product_id => $item) {
                    // Récupérer le prix du produit depuis la base de données
                    $stmt->execute([$product_id]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$product) {
                        throw new Exception('Produit non trouvé: ' . $product_id);
                    }

                    error_log("Ajout du produit à la commande: " . print_r([
                        'product_id' => $product_id,
                        'quantity' => $item['quantity'],
                        'price' => $product['price']
                    ], true));
                    
                    if (!$insertStmt->execute([
                        $orderId,
                        $product_id,
                        $item['quantity'],
                        $product['price']
                    ])) {
                        throw new Exception('Erreur lors de l\'ajout des produits');
                    }

                    // Mettre à jour le stock
                    $updateStockStmt = $DB->prepare("
                        UPDATE products 
                        SET stock = stock - ? 
                        WHERE id = ? AND stock >= ?
                    ");
                    
                    if (!$updateStockStmt->execute([
                        $item['quantity'],
                        $product_id,
                        $item['quantity']
                    ])) {
                        throw new Exception('Stock insuffisant pour le produit: ' . $product_id);
                    }
                }
            } else {
                error_log("Panier vide lors de la création de la commande!");
            }

            // Récupérer l'email de l'utilisateur
            $stmt = $DB->prepare("SELECT mail FROM user WHERE id = ?");
            $stmt->execute([$_SESSION['id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$user || empty($user['mail'])) {
                error_log("Email utilisateur non trouvé pour l'ID: " . $_SESSION['id']);
                throw new Exception("Email utilisateur non trouvé");
            }

            $user_email = $user['mail'];
            error_log("Email utilisateur trouvé: " . $user_email);

            require_once('../_functions/mail.php');
            $email_sent = sendOrderConfirmationEmail($orderId, $user_email);
            
            if (!$email_sent) {
                error_log("Échec de l'envoi de l'email de confirmation");
            } else {
                error_log("Email de confirmation envoyé avec succès");
            }

            $DB->commit();

            // Vider le panier
            $_SESSION['cart'] = [];

            sendJsonResponse(true, 'Commande enregistrée', [
                'order_id' => $orderId,
                'payment_id' => $paymentIntent->id,
                'redirect_url' => 'my_orders.php'
            ]);

        } catch (Exception $e) {
            $DB->rollBack();
            error_log("Erreur transaction: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            sendJsonResponse(false, $e->getMessage());
        }
    } else {
        sendJsonResponse(false, 'Paiement échoué: ' . $paymentIntent->status);
    }

} catch (Exception $e) {
    error_log("Erreur critique: " . $e->getMessage());
    error_log("Trace: " . $e->getTraceAsString());
    sendJsonResponse(false, 'Erreur serveur: ' . $e->getMessage());
} 