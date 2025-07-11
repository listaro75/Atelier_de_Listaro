<?php
require_once('../stripe-php/init.php');
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');
session_start();

// Configuration de la clé secrète Stripe
\Stripe\Stripe::setApiKey('sk_test_51R9bAN4ZLqIaT6NtcoH4C44ziY5yE84oX2AJQIsj5JKLWWE4sjJZr7sGyKCNLIN4ZohDfDineIvT1YrYs54J3ujy00DUkUohmm');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Vérification des données requises
    if (!isset($data['shipping_method']) || !isset($data['shipping_cost']) || 
        !isset($data['total_amount']) || !isset($data['shipping_address'])) {
        throw new Exception("Données manquantes");
    }

    // Debug - vérifier les données reçues
    error_log('Données reçues : ' . print_r($data, true));
    
    // Créer le paiement avec Stripe
    $paymentIntent = \Stripe\PaymentIntent::create([
        'amount' => intval($data['total_amount'] * 100), // Convertir en centimes
        'currency' => 'eur',
        'payment_method' => $data['payment_method_id'],
        'confirmation_method' => 'manual',
        'confirm' => true
    ]);

    error_log('PaymentIntent créé : ' . $paymentIntent->id);

    if ($paymentIntent->status === 'succeeded') {
        // Préparer les données pour l'insertion
        $userId = $_SESSION['id'];
        $shippingMethod = $data['shipping_method'];
        $shippingCost = floatval($data['shipping_cost']);
        $totalAmount = floatval($data['total_amount']);
        $shippingAddress = json_encode($data['shipping_address']);
        
        // Vérifier que toutes les données sont valides
        if (!$userId || !$shippingMethod || !$shippingAddress) {
            throw new Exception("Données invalides pour la création de la commande");
        }

        // Enregistrer la commande dans la base de données
        $stmt = $DB->prepare("
            INSERT INTO orders (
                user_id, 
                shipping_method, 
                shipping_cost, 
                total_amount, 
                shipping_address, 
                payment_method,
                status
            ) VALUES (?, ?, ?, ?, ?, ?, 'payé')
        ");

        $success = $stmt->execute([
            $userId,
            $shippingMethod,
            $shippingCost,
            $totalAmount,
            $shippingAddress,
            'card'
        ]);

        if (!$success) {
            error_log('Erreur SQL : ' . print_r($stmt->errorInfo(), true));
            throw new Exception("Erreur lors de l'enregistrement de la commande");
        }

        // Vider le panier
        $_SESSION['cart'] = [];

        echo json_encode(['success' => true]);
    } else {
        error_log('Paiement échoué. Status : ' . $paymentIntent->status);
        throw new Exception("Le paiement a échoué");
    }
} catch (\Stripe\Exception\CardException $e) {
    error_log('Erreur carte : ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('Erreur générale : ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Une erreur est survenue lors du traitement du paiement'
    ]);
} 