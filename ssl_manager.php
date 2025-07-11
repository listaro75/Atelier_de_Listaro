<?php
/**
 * Interface de vérification SSL en temps réel
 * Atelier de Listaro
 */

// Fonction pour vérifier SSL
function checkSSL($domain) {
    $context = stream_context_create([
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false,
        ],
    ]);
    
    $result = [
        'domain' => $domain,
        'accessible' => false,
        'certificate' => null,
        'error' => null
    ];
    
    try {
        $socket = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );
        
        if ($socket) {
            $result['accessible'] = true;
            
            // Obtenir les informations du certificat
            $cert = stream_context_get_params($socket);
            if (isset($cert['options']['ssl']['peer_certificate'])) {
                $certData = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);
                $result['certificate'] = $certData;
            }
            
            fclose($socket);
        } else {
            $result['error'] = "$errno: $errstr";
        }
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

// Vérifier si on teste le SSL
$testSSL = isset($_GET['test_ssl']);
$sslResults = [];

if ($testSSL) {
    $domains = ['atelierdelistaro.fr', 'www.atelierdelistaro.fr'];
    foreach ($domains as $domain) {
        $sslResults[$domain] = checkSSL($domain);
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Vérification SSL - Atelier de Listaro</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; }
        .status-card { background: #f8f9fa; border-radius: 8px; padding: 20px; margin: 15px 0; border-left: 4px solid #007cba; }
        .success { border-left-color: #28a745; background: #d4edda; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .error { border-left-color: #dc3545; background: #f8d7da; }
        .btn { display: inline-block; background: #007cba; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #005fa3; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: #212529; }
        .steps { background: #e7f3ff; padding: 20px; border-radius: 8px; margin: 20px 0; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .cert-info { background: white; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #dee2e6; }
        th { background: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Configuration SSL - Atelier de Listaro</h1>
            <p>Interface de vérification et configuration du certificat SSL</p>
        </div>

        <?php if ($testSSL && !empty($sslResults)): ?>
            <h2>🧪 Résultats du test SSL</h2>
            
            <?php foreach ($sslResults as $domain => $result): ?>
                <div class="status-card <?= $result['accessible'] ? 'success' : 'error' ?>">
                    <h3><?= $result['accessible'] ? '✅' : '❌' ?> <?= htmlspecialchars($domain) ?></h3>
                    
                    <?php if ($result['accessible']): ?>
                        <p><strong>Statut :</strong> SSL accessible et fonctionnel</p>
                        
                        <?php if ($result['certificate']): ?>
                            <div class="cert-info">
                                <h4>📜 Informations du certificat</h4>
                                <table>
                                    <tr><th>Émetteur</th><td><?= htmlspecialchars($result['certificate']['issuer']['CN'] ?? 'Non défini') ?></td></tr>
                                    <tr><th>Sujet</th><td><?= htmlspecialchars($result['certificate']['subject']['CN'] ?? 'Non défini') ?></td></tr>
                                    <tr><th>Valide depuis</th><td><?= date('d/m/Y H:i:s', $result['certificate']['validFrom_time_t']) ?></td></tr>
                                    <tr><th>Expire le</th><td><?= date('d/m/Y H:i:s', $result['certificate']['validTo_time_t']) ?></td></tr>
                                    <tr><th>Algorithme</th><td><?= htmlspecialchars($result['certificate']['signatureTypeSN'] ?? 'Non défini') ?></td></tr>
                                </table>
                                
                                <?php 
                                $daysLeft = ceil(($result['certificate']['validTo_time_t'] - time()) / 86400);
                                $colorClass = $daysLeft > 30 ? 'success' : ($daysLeft > 7 ? 'warning' : 'error');
                                ?>
                                <p class="<?= $colorClass ?>"><strong>Expiration :</strong> Dans <?= $daysLeft ?> jours</p>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <p><strong>Erreur :</strong> <?= htmlspecialchars($result['error'] ?? 'SSL non accessible') ?></p>
                        <p>Le certificat SSL n'est pas encore configuré ou accessible pour ce domaine.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
        <?php endif; ?>

        <div class="steps">
            <h2>📋 Guide d'installation SSL complet</h2>
            
            <h3>🗂️ Fichiers nécessaires :</h3>
            <ul>
                <li>✅ <strong>Clé privée :</strong> _.atelierdelistaro.fr_private_key.key (vous l'avez)</li>
                <li>❓ <strong>Certificat SSL :</strong> _.atelierdelistaro.fr.crt</li>
                <li>❓ <strong>Certificat intermédiaire :</strong> _.atelierdelistaro.fr.ca-bundle (optionnel)</li>
            </ul>
            
            <h3>🚀 Installation automatique :</h3>
            <p>Connectez-vous à votre Raspberry Pi et exécutez :</p>
            <pre>
# Télécharger le script d'installation
wget http://88.124.91.246/install_ssl.sh

# Rendre exécutable
chmod +x install_ssl.sh

# Exécuter l'installation
sudo ./install_ssl.sh
            </pre>
            
            <h3>🔧 Installation manuelle :</h3>
            <pre>
# 1. Créer les dossiers SSL
sudo mkdir -p /etc/ssl/atelierdelistaro

# 2. Copier la clé privée
sudo nano /etc/ssl/atelierdelistaro/private.key
# Collez le contenu de votre fichier _.atelierdelistaro.fr_private_key.key

# 3. Copier le certificat SSL
sudo nano /etc/ssl/atelierdelistaro/certificate.crt
# Collez le contenu de votre fichier _.atelierdelistaro.fr.crt

# 4. Configurer les permissions
sudo chmod 600 /etc/ssl/atelierdelistaro/private.key
sudo chmod 644 /etc/ssl/atelierdelistaro/*.crt
sudo chown -R root:root /etc/ssl/atelierdelistaro/

# 5. Activer SSL dans Apache
sudo a2enmod ssl headers rewrite
sudo a2ensite atelierdelistaro-ssl
sudo apache2ctl configtest
sudo systemctl restart apache2
            </pre>
        </div>

        <div class="status-card">
            <h3>🌐 État actuel de votre site</h3>
            <?php 
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443;
            $currentUrl = ($isHttps ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
            ?>
            
            <p><strong>URL actuelle :</strong> <?= $currentUrl ?></p>
            <p><strong>Protocole :</strong> <?= $isHttps ? 'HTTPS ✅' : 'HTTP ⚠️' ?></p>
            <p><strong>Port :</strong> <?= $_SERVER['SERVER_PORT'] ?></p>
            
            <?php if (!$isHttps): ?>
                <p style="color: #dc3545;">⚠️ Votre site fonctionne actuellement en HTTP. Après l'installation SSL, il basculera automatiquement en HTTPS.</p>
            <?php endif; ?>
        </div>

        <div class="status-card">
            <h3>📧 Impact sur votre système d'email</h3>
            <p>Avec SSL configuré, votre système d'email bénéficiera de :</p>
            <ul>
                <li>✅ Meilleure réputation pour vos emails</li>
                <li>✅ Liens sécurisés dans les newsletters</li>
                <li>✅ Webhooks sécurisés pour Stripe</li>
                <li>✅ Confiance accrue des destinataires</li>
            </ul>
        </div>

        <h3>🔍 Tests et vérifications</h3>
        <div style="text-align: center; margin: 30px 0;">
            <a href="?test_ssl=1" class="btn btn-warning">🧪 Tester SSL maintenant</a>
            <a href="https://www.ssllabs.com/ssltest/analyze.html?d=atelierdelistaro.fr" target="_blank" class="btn">🔍 Test SSL Labs</a>
            <a href="admin_panel.php" class="btn btn-success">🏠 Panel Admin</a>
        </div>

        <div class="status-card">
            <h3>📞 Support</h3>
            <p>Si vous rencontrez des difficultés :</p>
            <ul>
                <li>Vérifiez que vous avez bien tous les fichiers de certificat</li>
                <li>Assurez-vous que votre DNS pointe vers 88.124.91.246</li>
                <li>Consultez les logs Apache : <code>sudo tail -f /var/log/apache2/error.log</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
