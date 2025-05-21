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

// Récupération des réservations de l'utilisateur
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.date_time, c.capacity, u.email as coach_email
    FROM reservations r
    JOIN courses c ON r.course_id = c.id
    JOIN users u ON c.coach_id = u.id
    WHERE r.user_id = ?
    ORDER BY c.date_time ASC
');
$stmt->execute([$user_id]);
$reservations = $stmt->fetchAll();

// Séparation des réservations passées et à venir
$upcoming_reservations = [];
$past_reservations = [];
$now = time();

foreach ($reservations as $reservation) {
    if (strtotime($reservation['date_time']) > $now) {
        $upcoming_reservations[] = $reservation;
    } else {
        $past_reservations[] = $reservation;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes réservations - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Mes réservations</h1>
            
            <?php displayAlert(); ?>
            
            <div class="action-buttons">
                <a href="courses.php" class="btn btn-primary">Voir les cours disponibles</a>
            </div>
            
            <div class="reservations-section">
                <h2>Réservations à venir</h2>
                
                <?php if (empty($upcoming_reservations)): ?>
                    <p>Vous n'avez pas de réservations à venir. <a href="courses.php">Réserver un cours</a></p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom du cours</th>
                                    <th>Date et heure</th>
                                    <th>Coach</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reservation['name']); ?></td>
                                        <td><?php echo formatDateTime($reservation['date_time']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['coach_email']); ?></td>
                                        <td>
                                            <a href="cancel-reservation.php?course_id=<?php echo $reservation['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">Annuler</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="reservations-section">
                <h2>Historique des réservations</h2>
                
                <?php if (empty($past_reservations)): ?>
                    <p>Vous n'avez pas d'historique de réservations.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom du cours</th>
                                    <th>Date et heure</th>
                                    <th>Coach</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past_reservations as $reservation): ?>
                                    <tr class="past-reservation">
                                        <td><?php echo htmlspecialchars($reservation['name']); ?></td>
                                        <td><?php echo formatDateTime($reservation['date_time']); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['coach_email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
      
    </div>
</body>
</html>
