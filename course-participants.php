<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Vérification des rôles autorisés (coach or admin)
if (!hasAnyRole(['coach', 'admin'])) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Vérification de ID du cours
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Location: dashboard.php');
    exit;
}

$course_id = (int)$_GET['course_id'];

// Récupération des informations du cours
if ($user_role === 'coach') {
    // Pour un coach, vérifier qu'il est bien le propriétaire du cours
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ? AND coach_id = ?');
    $stmt->execute([$course_id, $user_id]);
} else {
    // Pour un admin, pas de restriction
    $stmt = $pdo->prepare('SELECT * FROM courses WHERE id = ?');
    $stmt->execute([$course_id]);
}

$course = $stmt->fetch();

// Vérification que le cours existe et que l'utilisateur a les droits
if (!$course) {
    setAlert('danger', 'Cours non trouvé ou vous n\'êtes pas autorisé à voir ses participants.');
    header('Location: dashboard.php');
    exit;
}

// Récupération des participants
$stmt = $pdo->prepare('
    SELECT u.id, u.email
    FROM reservations r
    JOIN users u ON r.user_id = u.id
    WHERE r.course_id = ?
    ORDER BY u.email
');
$stmt->execute([$course_id]);
$participants = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Participants au cours - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Participants au cours</h1>
            
            <div class="course-info">
                <h2><?php echo htmlspecialchars($course['name']); ?></h2>
                <p><strong>Date et heure :</strong> <?php echo formatDateTime($course['date_time']); ?></p>
                <p><strong>Capacité :</strong> <?php echo $course['capacity']; ?> places</p>
            </div>
            
            <div class="participants-list">
                <h3>Liste des participants (<?php echo count($participants); ?> / <?php echo $course['capacity']; ?>)</h3>
                
                <?php if (empty($participants)): ?>
                    <p>Aucun participant inscrit à ce cours.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $counter = 1; ?>
                                <?php foreach ($participants as $participant): ?>
                                    <tr>
                                        <td><?php echo $counter++; ?></td>
                                        <td><?php echo htmlspecialchars($participant['email']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="action-buttons">
                <?php if ($user_role === 'coach'): ?>
                    <a href="manage-courses.php" class="btn btn-secondary">Retour à mes cours</a>
                <?php else: ?>
                    <a href="all-courses.php" class="btn btn-secondary">Retour aux cours</a>
                <?php endif; ?>
            </div>
        </main>
        
       
    </div>
</body>
</html>
