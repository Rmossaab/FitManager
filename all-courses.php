<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: access-denied.php');
    exit;
}

//   tous les cours
$stmt = $pdo->prepare('
    SELECT c.id, c.name, c.date_time, c.capacity, u.email as coach_email,
           (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved
    FROM courses c
    JOIN users u ON c.coach_id = u.id
    ORDER BY c.date_time DESC
');
$stmt->execute();
$courses = $stmt->fetchAll();

// Séparation des cours 
$upcoming_courses = [];
$past_courses = [];
$now = time();

foreach ($courses as $course) {
    if (strtotime($course['date_time']) > $now) {
        $upcoming_courses[] = $course;
    } else {
        $past_courses[] = $course;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les cours - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Tous les cours</h1>
            
            <?php displayAlert(); ?>
            
            <div class="courses-section">
                <h2>Cours à venir</h2>
                
                <?php if (empty($upcoming_courses)): ?>
                    <p>Aucun cours à venir.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom du cours</th>
                                    <th>Date et heure</th>
                                    <th>Coach</th>
                                    <th>Places</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming_courses as $course): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($course['name']); ?></td>
                                        <td><?php echo formatDateTime($course['date_time']); ?></td>
                                        <td><?php echo htmlspecialchars($course['coach_email']); ?></td>
                                        <td><?php echo $course['reserved']; ?> / <?php echo $course['capacity']; ?></td>
                                        <td>
                                            <a href="course-participants.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm">Participants</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="courses-section">
                <h2>Cours passés</h2>
                
                <?php if (empty($past_courses)): ?>
                    <p>Aucun cours passé.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Nom du cours</th>
                                    <th>Date et heure</th>
                                    <th>Coach</th>
                                    <th>Places</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past_courses as $course): ?>
                                    <tr class="past-course">
                                        <td><?php echo htmlspecialchars($course['name']); ?></td>
                                        <td><?php echo formatDateTime($course['date_time']); ?></td>
                                        <td><?php echo htmlspecialchars($course['coach_email']); ?></td>
                                        <td><?php echo $course['reserved']; ?> / <?php echo $course['capacity']; ?></td>
                                        <td>
                                            <a href="course-participants.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm">Participants</a>
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
