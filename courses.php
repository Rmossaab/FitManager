<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du role
if (!isLoggedIn() || !hasRole('member')) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérification de l'abonnement actif
$stmt = $pdo->prepare('
    SELECT id FROM subscriptions 
    WHERE user_id = ? AND end_date >= CURDATE() 
    LIMIT 1
');
$stmt->execute([$user_id]);
$has_subscription = $stmt->rowCount() > 0;

// Récupération des cours disponibles
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.date_time, c.capacity, u.email as coach_email,
           (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved,
           (SELECT COUNT(*) FROM reservations WHERE course_id = c.id AND user_id = ?) as is_reserved
    FROM courses c
    JOIN users u ON c.coach_id = u.id
    WHERE c.date_time > NOW()
    ORDER BY c.date_time ASC
');
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cours disponibles - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Cours disponibles</h1>
            
            <?php displayAlert(); ?>
            
            <?php if (!$has_subscription): ?>
                <div class="alert alert-warning">
                    <p>Vous n'avez pas d'abonnement actif. Veuillez souscrire à un abonnement pour réserver des cours.</p>
                    <p><a href="subscription.php" class="btn btn-primary">Voir les abonnements</a></p>
                </div>
            <?php endif; ?>
            
            <div class="courses-list">
                <?php if (empty($courses)): ?>
                    <p>Aucun cours disponible pour le moment.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom du cours</th>
                                    <th>Date et heure</th>
                                    <th>Coach</th>
                                    <th>Places</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <?php 
                                        $isFull = $course['reserved'] >= $course['capacity'];
                                        $isReserved = $course['is_reserved'] > 0;
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['name']); ?></td>
                                        <td><?php echo formatDateTime($course['date_time']); ?></td>
                                        <td><?php echo htmlspecialchars($course['coach_email']); ?></td>
                                        <td><?php echo $course['reserved']; ?> / <?php echo $course['capacity']; ?></td>
                                        <td>
                                            <?php if ($isReserved): ?>
                                                <span class="badge badge-primary">Réservé</span>
                                            <?php elseif ($isFull): ?>
                                                <span class="badge badge-warning">Complet</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($isReserved): ?>
                                                <a href="cancel-reservation.php?course_id=<?php echo $course['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">Annuler</a>
                                            <?php elseif (!$isFull && $has_subscription): ?>
                                                <a href="reserve-course.php?course_id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">Réserver</a>
                                            <?php elseif (!$has_subscription): ?>
                                                <a href="subscription.php" class="btn btn-warning btn-sm">Abonnement requis</a>
                                            <?php else: ?>
                                                <span class="btn btn-secondary btn-sm disabled">Complet</span>
                                            <?php endif; ?>
                                        </td>
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
