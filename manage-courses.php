<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du rôle
if (!isLoggedIn() || !hasRole('coach')) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Récupération des cours du coach
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.date_time, c.capacity, 
           (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved
    FROM courses c
    WHERE c.coach_id = ?
    ORDER BY c.date_time DESC
');
$stmt->execute([$user_id]);
$courses = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer mes cours - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Gérer mes cours</h1>
            
            <?php displayAlert(); ?>
            
            <div class="action-buttons">
                <a href="add-course.php" class="btn btn-primary">Ajouter un cours</a>
            </div>
            
            <div class="courses-list">
                <?php if (empty($courses)): ?>
                    <p>Vous n'avez pas encore créé de cours.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom du cours</th>
                                    <th>Date et heure</th>
                                    <th>Capacité</th>
                                    <th>Réservations</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($courses as $course): ?>
                                    <?php 
                                        $isPast = strtotime($course['date_time']) < time();
                                        $isFull = $course['reserved'] >= $course['capacity'];
                                    ?>
                                    <tr class="<?php echo $isPast ? 'past-course' : ''; ?>">
                                        <td><?php echo htmlspecialchars($course['name']); ?></td>
                                        <td><?php echo formatDateTime($course['date_time']); ?></td>
                                        <td><?php echo $course['capacity']; ?></td>
                                        <td><?php echo $course['reserved']; ?> / <?php echo $course['capacity']; ?></td>
                                        <td>
                                            <?php if ($isPast): ?>
                                                <span class="badge badge-secondary">Terminé</span>
                                            <?php elseif ($isFull): ?>
                                                <span class="badge badge-warning">Complet</span>
                                            <?php else: ?>
                                                <span class="badge badge-success">Disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="course-participants.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm">Participants</a>
                                            <?php if (!$isPast): ?>
                                                <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">Modifier</a>
                                                <a href="delete-course.php?id=<?php echo $course['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')">Supprimer</a>
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
