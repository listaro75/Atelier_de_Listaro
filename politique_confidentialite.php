<?php
session_start();
include_once('_db/connexion_DB.php');
include_once('_functions/cookie_manager.php');

// Traitement des demandes de suppression
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];
    
    if ($_POST['action'] === 'request_deletion') {
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        
        if ($email) {
            try {
                $stmt = $DB->prepare("INSERT INTO data_deletion_requests (ip_address, user_id, email, reason) VALUES (?, ?, ?, ?)");
                $stmt->execute([
                    $_SERVER['REMOTE_ADDR'],
                    $_SESSION['id'] ?? null,
                    $email,
                    'Demande de suppression via formulaire'
                ]);
                
                $response['success'] = true;
                $response['message'] = 'Votre demande de suppression a été enregistrée.';
            } catch (Exception $e) {
                $response['message'] = 'Erreur lors de l\'enregistrement de la demande.';
            }
        } else {
            $response['message'] = 'Adresse email invalide.';
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Politique de confidentialité - Atelier de Listaro</title>
    <?php    
        require_once('_head/meta.php');
        require_once('_head/link.php');
    ?>
    <link rel="stylesheet" href="_css/cookies.css">
</head>
<body>
    <?php require_once('_menu/menu.php'); ?>
    
    <div class="privacy-policy">
        <h1>🔒 Politique de confidentialité</h1>
        
        <p><strong>Dernière mise à jour :</strong> <?php echo date('d/m/Y'); ?></p>
        
        <div class="important">
            <p><strong>Important :</strong> Cette politique de confidentialité respecte le Règlement Général sur la Protection des Données (RGPD) et la loi française Informatique et Libertés.</p>
        </div>
        
        <h2>1. Qui sommes-nous ?</h2>
        <p><strong>Atelier de Listaro</strong> est un site e-commerce artisanal qui respecte votre vie privée et vos données personnelles.</p>
        
        <h2>2. 🍪 Cookies et technologies similaires</h2>
        <p>Nous utilisons différents types de cookies pour améliorer votre expérience :</p>
        
        <h3>Cookies essentiels</h3>
        <ul>
            <li><strong>Cookies de session</strong> : Nécessaires pour la navigation</li>
            <li><strong>Cookies d'authentification</strong> : Pour votre connexion</li>
            <li><strong>Cookies de panier</strong> : Pour mémoriser vos achats</li>
        </ul>
        
        <h3>Cookies analytiques</h3>
        <ul>
            <li><strong>Statistiques de visite</strong> : Pages consultées, temps passé</li>
            <li><strong>Informations techniques</strong> : Navigateur, résolution d'écran</li>
            <li><strong>Source de trafic</strong> : Comment vous êtes arrivé sur notre site</li>
        </ul>
        
        <h3>Cookies de préférences</h3>
        <ul>
            <li><strong>Langue</strong> : Votre langue préférée</li>
            <li><strong>Thème</strong> : Apparence du site</li>
            <li><strong>Paramètres</strong> : Vos préférences d'affichage</li>
        </ul>
        
        <h3>Cookies marketing</h3>
        <ul>
            <li><strong>Publicité ciblée</strong> : Annonces personnalisées</li>
            <li><strong>Réseaux sociaux</strong> : Intégration des boutons de partage</li>
            <li><strong>Analyse marketing</strong> : Efficacité des campagnes</li>
        </ul>
        
        <h2>3. 📊 Données collectées</h2>
        <p>Selon vos préférences de cookies, nous pouvons collecter :</p>
        
        <h3>Données essentielles (toujours collectées)</h3>
        <ul>
            <li>Adresse IP (anonymisée)</li>
            <li>Horodatage de visite</li>
            <li>Pages visitées</li>
        </ul>
        
        <h3>Données analytiques (si autorisées)</h3>
        <ul>
            <li>Informations sur votre navigateur</li>
            <li>Résolution d'écran</li>
            <li>Langue du navigateur</li>
            <li>Site de provenance</li>
        </ul>
        
        <h3>Données de préférences (si autorisées)</h3>
        <ul>
            <li>Paramètres d'affichage</li>
            <li>Langue préférée</li>
            <li>Fuseau horaire</li>
        </ul>
        
        <h3>Données marketing (si autorisées)</h3>
        <ul>
            <li>Paramètres UTM (campagnes publicitaires)</li>
            <li>Identifiants de clic publicitaire</li>
            <li>Interactions avec les contenus</li>
        </ul>
        
        <h2>4. 🎯 Finalités du traitement</h2>
        <p>Nous utilisons vos données pour :</p>
        <ul>
            <li><strong>Fonctionnement du site</strong> : Navigation, authentification</li>
            <li><strong>Amélioration de l'expérience</strong> : Personnalisation, préférences</li>
            <li><strong>Analyses statistiques</strong> : Comprendre l'utilisation du site</li>
            <li><strong>Conformité légale</strong> : Respect des obligations</li>
        </ul>
        
        <h2>5. ⚖️ Base légale</h2>
        <p>Le traitement de vos données est basé sur :</p>
        <ul>
            <li><strong>Consentement</strong> : Pour les cookies non essentiels</li>
            <li><strong>Intérêt légitime</strong> : Pour les analyses statistiques</li>
            <li><strong>Nécessité contractuelle</strong> : Pour les commandes</li>
            <li><strong>Obligation légale</strong> : Pour la conformité</li>
        </ul>
        
        <h2>6. 📅 Conservation des données</h2>
        <ul>
            <li><strong>Cookies essentiels</strong> : Durée de la session</li>
            <li><strong>Autres cookies</strong> : 13 mois maximum</li>
            <li><strong>Données de navigation</strong> : 25 mois</li>
            <li><strong>Consentements</strong> : 3 ans</li>
        </ul>
        
        <h2>7. 🌍 Transferts de données</h2>
        <p>Vos données sont stockées en France et dans l'Union Européenne. Aucun transfert vers des pays tiers n'est effectué sans garanties appropriées.</p>
        
        <h2>8. 🔐 Sécurité</h2>
        <p>Nous mettons en place des mesures techniques et organisationnelles pour protéger vos données :</p>
        <ul>
            <li>Chiffrement des données sensibles</li>
            <li>Accès restreint aux données</li>
            <li>Surveillance des accès</li>
            <li>Sauvegardes sécurisées</li>
        </ul>
        
        <h2>9. 🎯 Vos droits</h2>
        <p>Conformément au RGPD, vous disposez des droits suivants :</p>
        
        <h3>Droit d'accès</h3>
        <p>Vous pouvez demander l'accès à vos données personnelles.</p>
        
        <h3>Droit de rectification</h3>
        <p>Vous pouvez demander la correction de données inexactes.</p>
        
        <h3>Droit à l'effacement</h3>
        <p>Vous pouvez demander la suppression de vos données ("droit à l'oubli").</p>
        
        <h3>Droit à la portabilité</h3>
        <p>Vous pouvez récupérer vos données dans un format structuré.</p>
        
        <h3>Droit d'opposition</h3>
        <p>Vous pouvez vous opposer au traitement de vos données.</p>
        
        <h3>Droit de retrait du consentement</h3>
        <p>Vous pouvez retirer votre consentement à tout moment.</p>
        
        <div class="data-management">
            <h3>🛠️ Gérer vos données</h3>
            <p>Utilisez les outils ci-dessous pour exercer vos droits :</p>
            
            <button class="btn btn-info" onclick="CookieUtils.showPreferences()">
                🍪 Gérer les cookies
            </button>
            
            <button class="btn btn-warning" onclick="exportMyData()">
                📥 Exporter mes données
            </button>
            
            <button class="btn btn-danger" onclick="requestDeletion()">
                🗑️ Demander la suppression
            </button>
        </div>
        
        <h2>10. 📮 Contact</h2>
        <p>Pour toute question concernant cette politique de confidentialité ou pour exercer vos droits :</p>
        <ul>
            <li><strong>Email :</strong> contact@atelierdelist.com</li>
            <li><strong>Adresse :</strong> [Votre adresse]</li>
            <li><strong>Téléphone :</strong> [Votre téléphone]</li>
        </ul>
        
        <h2>11. 📜 Autorité de contrôle</h2>
        <p>Vous avez le droit d'introduire une réclamation auprès de la CNIL :</p>
        <ul>
            <li><strong>Site web :</strong> <a href="https://www.cnil.fr" target="_blank">www.cnil.fr</a></li>
            <li><strong>Adresse :</strong> 3 Place de Fontenoy, 75007 Paris</li>
            <li><strong>Téléphone :</strong> 01 53 73 22 22</li>
        </ul>
        
        <h2>12. 🔄 Mises à jour</h2>
        <p>Cette politique peut être mise à jour. Nous vous informerons des changements importants.</p>
        
        <div class="highlight">
            <p><strong>Note :</strong> Cette politique de confidentialité est conforme au RGPD et à la loi française. Elle est mise à jour régulièrement pour refléter les évolutions légales et techniques.</p>
        </div>
    </div>
    
    <!-- Modal pour demande de suppression -->
    <div id="deletion-modal" class="cookie-modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h2>🗑️ Demande de suppression</h2>
                <button onclick="closeDeletionModal()" class="close-btn">&times;</button>
            </div>
            <div class="modal-body">
                <p>Conformément à votre droit à l'oubli, vous pouvez demander la suppression de vos données personnelles.</p>
                <form id="deletion-form">
                    <div class="form-group">
                        <label for="email">Votre adresse email :</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" required>
                            Je confirme vouloir supprimer toutes mes données personnelles
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button onclick="submitDeletionRequest()" class="btn btn-danger">Confirmer la suppression</button>
                <button onclick="closeDeletionModal()" class="btn btn-secondary">Annuler</button>
            </div>
        </div>
    </div>
    
    <script src="_functions/cookies.js"></script>
    <script>
        function requestDeletion() {
            document.getElementById('deletion-modal').style.display = 'flex';
        }
        
        function closeDeletionModal() {
            document.getElementById('deletion-modal').style.display = 'none';
        }
        
        function submitDeletionRequest() {
            const email = document.getElementById('email').value;
            
            if (!email) {
                alert('Veuillez saisir votre adresse email');
                return;
            }
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=request_deletion&email=${encodeURIComponent(email)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Votre demande de suppression a été enregistrée. Nous la traiterons dans les plus brefs délais.');
                    closeDeletionModal();
                } else {
                    alert('Erreur : ' + data.message);
                }
            })
            .catch(error => {
                alert('Erreur lors de la demande');
                console.error('Erreur:', error);
            });
        }
        
        function exportMyData() {
            alert('Fonctionnalité d\'export en cours de développement');
        }
        
        // Fermer le modal en cliquant en dehors
        window.onclick = function(event) {
            const modal = document.getElementById('deletion-modal');
            if (event.target === modal) {
                closeDeletionModal();
            }
        }
    </script>
    
    <?php require_once('_footer/footer.php'); ?>
</body>
</html>
