<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du rôle
if (!isLoggedIn() || !hasRole('member')) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupération de l'abonnement actif
$stmt = $pdo->prepare('
    SELECT id, type, start_date, end_date
    FROM subscriptions
    WHERE user_id = ? AND end_date >= CURDATE()
    ORDER BY end_date DESC
    LIMIT 1
');
$stmt->execute([$user_id]);
$active_subscription = $stmt->fetch();

// Définition des types d'abonnements disponibles
$subscription_types = [
    'monthly' => [
        'name' => 'Abonnement Mensuel',
        'price' => 29.99,
        'duration' => '1 mois',
        'description' => 'Accès illimité à tous les cours pendant 1 mois.'
    ],
    'annual' => [
        'name' => 'Abonnement Annuel',
        'price' => 299.99,
        'duration' => '12 mois',
        'description' => 'Accès illimité à tous les cours pendant 12 mois. Économisez 20% par rapport à l\'abonnement mensuel !'
    ]
];

// Traitement de la souscription à un abonnement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subscribe'])) {
    $type = $_POST['type'];
    
    if (!array_key_exists($type, $subscription_types)) {
        setAlert('danger', 'Type d\'abonnement invalide.');
    } else {
        try {
            // Simulation de paiement (dans une application réelle, intégrer un système de paiement)
            
            // Calcul des dates
            $start_date = date('Y-m-d');
            $end_date = ($type === 'monthly') 
                ? date('Y-m-d', strtotime('+1 month')) 
                : date('Y-m-d', strtotime('+1 year'));
            
            // Insertion de l'abonnement
            $stmt = $pdo->prepare('INSERT INTO subscriptions (user_id, type, start_date, end_date) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_id, $type, $start_date, $end_date]);
            
            setAlert('success', 'Votre abonnement a été souscrit avec succès.');
            header('Location: subscription.php');
            exit;
        } catch (PDOException $e) {
            setAlert('danger', 'Erreur lors de la souscription: ' . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon abonnement - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Mon abonnement</h1>
            
            <?php displayAlert(); ?>
            
            <?php if ($active_subscription): ?>
                <div class="subscription-info">
                    <h2>Abonnement actif</h2>
                    <div class="card">
                        <div class="card-body">
                            <h3><?php echo $subscription_types[$active_subscription['type']]['name']; ?></h3>
                            <p><strong>Date de début :</strong> <?php echo date('d/m/Y', strtotime($active_subscription['start_date'])); ?></p>
                            <p><strong>Date d'expiration :</strong> <?php echo date('d/m/Y', strtotime($active_subscription['end_date'])); ?></p>
                            <p><strong>Statut :</strong> <span class="badge badge-success">Actif</span></p>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <p>Vous n'avez pas d'abonnement actif. Veuillez souscrire à l'un de nos abonnements ci-dessous.</p>
                </div>
                
                <div class="subscription-plans">
                    <h2>Nos abonnements</h2>
                    <div class="plans-container">
                        <?php foreach ($subscription_types as $type => $plan): ?>
                            <div class="plan-card">
                                <div class="plan-header">
                                    <h3><?php echo $plan['name']; ?></h3>
                                    <p class="plan-price"><?php echo number_format($plan['price'], 2); ?> €</p>
                                    <p class="plan-duration"><?php echo $plan['duration']; ?></p>
                                </div>
                                <div class="plan-body">
                                    <p><?php echo $plan['description']; ?></p>
                                    <form method="post" action="">
                                        <input type="hidden" name="type" value="<?php echo $type; ?>">
                                        <button type="submit" name="subscribe" class="btn btn-primary">Souscrire</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
        
       
    </div>
</body>
</html>
