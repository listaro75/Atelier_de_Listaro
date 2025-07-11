<?php
// Script de test simple pour vérifier la connexion à la base de données
// À exécuter dans le navigateur : https://votre-site.com/test_simple.php

echo "<h1>🔍 Test de Connexion - Atelier de Listaro</h1>";
echo "<p><strong>Date :</strong> " . date('Y-m-d H:i:s') . "</p>";

// 1. Vérifier si le fichier .env existe
echo "<h2>1. Vérification du fichier .env</h2>";
if (file_exists('.env')) {
    echo "✅ Fichier .env trouvé<br>";
} else {
    echo "❌ Fichier .env introuvable<br>";
    echo "<p><strong>Action requise :</strong> Créez un fichier .env avec vos identifiants de base de données</p>";
}

// 2. Charger la configuration
echo "<h2>2. Chargement de la configuration</h2>";
try {
    require_once '_config/env.php';
    echo "✅ Configuration chargée<br>";
    
    $host = getenv('DB_HOST') ?: 'Non défini';
    $dbname = getenv('DB_NAME') ?: 'Non défini';
    $username = getenv('DB_USERNAME') ?: 'Non défini';
    
    echo "<p><strong>Host :</strong> $host<br>";
    echo "<strong>Base :</strong> $dbname<br>";
    echo "<strong>Utilisateur :</strong> $username<br>";
    echo "<strong>Mot de passe :</strong> " . (getenv('DB_PASSWORD') ? '[Défini]' : '[Non défini]') . "</p>";
    
} catch (Exception $e) {
    echo "❌ Erreur de configuration : " . $e->getMessage() . "<br>";
    die();
}

// 3. Test de connexion à la base de données
echo "<h2>3. Test de connexion MySQL</h2>";
try {
    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";dbname=" . getenv('DB_NAME') . ";charset=utf8mb4",
        getenv('DB_USERNAME'),
        getenv('DB_PASSWORD'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong>Connexion à la base de données réussie !</strong><br>";
    
    // Tester une requête simple
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '" . getenv('DB_NAME') . "'");
    $result = $stmt->fetch();
    
    echo "<p><strong>Nombre de tables dans la base :</strong> " . $result['count'] . "</p>";
    
    if ($result['count'] == 0) {
        echo "<p>⚠️ <strong>La base est vide.</strong> Vous devez importer le fichier SQL via phpMyAdmin.</p>";
    } else {
        echo "<p>✅ <strong>Des tables sont présentes dans la base.</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Erreur de connexion :</strong> " . $e->getMessage() . "<br>";
    echo "<p><strong>Solutions possibles :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifiez vos identifiants dans le fichier .env</li>";
    echo "<li>Assurez-vous que ce script s'exécute depuis votre hébergeur (pas en local)</li>";
    echo "<li>Contactez le support de votre hébergeur</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Note :</strong> Supprimez ce fichier après les tests pour la sécurité.</p>";
echo "<p><strong>Fichier à supprimer :</strong> test_simple.php</p>";
?>
