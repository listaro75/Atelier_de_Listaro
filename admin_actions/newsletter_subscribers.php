<?php
/**
 * Liste des abonnés Newsletter - Admin Panel
 * Atelier de Listaro
 */

session_start();
include_once('../_db/connexion_DB.php');
include_once('../_functions/auth.php');

// Vérifier si l'utilisateur est admin
if (!is_admin()) {
    echo '<div class="alert alert-error">Accès non autorisé</div>';
    exit();
}

try {
    // Récupérer les abonnés newsletter
    $stmt = $DB->prepare("
        SELECT id, username, email, created_at 
        FROM users 
        WHERE newsletter = 1 
        ORDER BY created_at DESC
    ");
    $stmt->execute();
    $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($subscribers) === 0) {
        echo '<div class="alert alert-info">Aucun abonné à la newsletter pour le moment.</div>';
        exit();
    }
    
    echo '<div class="table-responsive">';
    echo '<table class="table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Nom d\'utilisateur</th>';
    echo '<th>Email</th>';
    echo '<th>Date d\'inscription</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($subscribers as $subscriber) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($subscriber['id']) . '</td>';
        echo '<td>' . htmlspecialchars($subscriber['username']) . '</td>';
        echo '<td>' . htmlspecialchars($subscriber['email']) . '</td>';
        echo '<td>' . date('d/m/Y H:i', strtotime($subscriber['created_at'])) . '</td>';
        echo '<td>';
        echo '<button class="btn btn-sm btn-danger" onclick="unsubscribeUser(' . $subscriber['id'] . ')" title="Désabonner">';
        echo '<i class="fas fa-times"></i>';
        echo '</button>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    echo '<style>
    .table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .table th, .table td {
        padding: 12px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .table th {
        background-color: #f8f9fa;
        font-weight: bold;
    }
    .table tr:hover {
        background-color: #f8f9fa;
    }
    .btn-sm {
        padding: 5px 10px;
        font-size: 0.8em;
    }
    .table-responsive {
        overflow-x: auto;
    }
    </style>';
    
    echo '<script>
    function unsubscribeUser(userId) {
        if (confirm("Êtes-vous sûr de vouloir désabonner cet utilisateur de la newsletter ?")) {
            fetch("admin_actions/unsubscribe_user.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                },
                body: JSON.stringify({user_id: userId})
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNewsletterAlert("success", "Utilisateur désabonné avec succès");
                    loadNewsletterSubscribers(); // Recharger la liste
                    loadNewsletterStats(); // Recharger les stats
                } else {
                    showNewsletterAlert("error", data.message);
                }
            })
            .catch(error => {
                showNewsletterAlert("error", "Erreur lors du désabonnement");
            });
        }
    }
    </script>';
    
} catch (Exception $e) {
    echo '<div class="alert alert-error">Erreur lors de la récupération des abonnés : ' . htmlspecialchars($e->getMessage()) . '</div>';
}
?>
