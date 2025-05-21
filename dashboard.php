<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Récupération des données selon le rôle
if ($user_role === 'member') {
    // Récupération des réservations du membre
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.date_time, c.capacity, u.email as coach_email
        FROM reservations r
        JOIN courses c ON r.course_id = c.id
        JOIN users u ON c.coach_id = u.id
        WHERE r.user_id = ? AND c.date_time >= NOW()
        ORDER BY c.date_time ASC
        LIMIT 5
    ');
    $stmt->execute([$user_id]);
    $upcoming_courses = $stmt->fetchAll();
    
    // Récupération de l'abonnement actif
    $stmt = $pdo->prepare('
        SELECT type, start_date, end_date
        FROM subscriptions
        WHERE user_id = ? AND end_date >= CURDATE()
        ORDER BY end_date DESC
        LIMIT 1
    ');
    $stmt->execute([$user_id]);
    $subscription = $stmt->fetch();
} elseif ($user_role === 'coach') {
    // Récupération des cours du coach
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.date_time, c.capacity, 
               (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved
        FROM courses c
        WHERE c.coach_id = ? AND c.date_time >= NOW()
        ORDER BY c.date_time ASC
    ');
    $stmt->execute([$user_id]);
    $coach_courses = $stmt->fetchAll();
} elseif ($user_role === 'admin') {
    // Récupération de tous les cours à venir
    $stmt = $pdo->prepare('
        SELECT c.id, c.name, c.date_time, c.capacity, u.email as coach_email,
               (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved
        FROM courses c
        JOIN users u ON c.coach_id = u.id
        WHERE c.date_time >= NOW()
        ORDER BY c.date_time ASC
        LIMIT 10
    ');
    $stmt->execute();
    $all_courses = $stmt->fetchAll();
    
    // Récupération du nombre d'utilisateurs par rôle
    $stmt = $pdo->prepare('
        SELECT role, COUNT(*) as count
        FROM users
        GROUP BY role
    ');
    $stmt->execute();
    $users_count = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <main>
            <h1>Tableau de bord</h1>
            
            <?php displayAlert(); ?>
            
            <div class="dashboard">
                <div class="welcome-message">
                    <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION['user_email']); ?></h2>
                    <p>Rôle : <?php echo ucfirst($user_role); ?></p>
                </div>
                
                <?php if ($user_role === 'member'): ?>
                    <!-- Tableau de bord Membre -->
                    <div class="dashboard-section">
                        <h3>Mes prochains cours</h3>
                        <?php if (empty($upcoming_courses)): ?>
                            <p>Vous n'avez pas de cours à venir. <a href="courses.php">Réserver un cours</a></p>
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
                                        <?php foreach ($upcoming_courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                                <td><?php echo formatDateTime($course['date_time']); ?></td>
                                                <td><?php echo htmlspecialchars($course['coach_email']); ?></td>
                                                <td>
                                                    <a href="cancel-reservation.php?course_id=<?php echo $course['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette réservation ?')">Annuler</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="dashboard-section">
                        <h3>Mon abonnement</h3>
                        <?php if (empty($subscription)): ?>
                            <p>Vous n'avez pas d'abonnement actif. <a href="subscription.php">Souscrire à un abonnement</a></p>
                        <?php else: ?>
                            <div class="subscription-info">
                                <p><strong>Type :</strong> <?php echo $subscription['type'] === 'monthly' ? 'Mensuel' : 'Annuel'; ?></p>
                                <p><strong>Date de début :</strong> <?php echo date('d/m/Y', strtotime($subscription['start_date'])); ?></p>
                                <p><strong>Date d'expiration :</strong> <?php echo date('d/m/Y', strtotime($subscription['end_date'])); ?></p>
                                <p><a href="subscription.php">Gérer mon abonnement</a></p>
                            </div>
                        <?php endif; ?>
                    </div>
                
                <?php elseif ($user_role === 'coach'): ?>
                    <!-- Tableau de bord Coach -->
                    <div class="dashboard-section">
                        <h3>Mes cours</h3>
                        <p><a href="manage-courses.php" class="btn btn-primary">Gérer mes cours</a></p>
                        
                        <?php if (empty($coach_courses)): ?>
                            <p>Vous n'avez pas de cours programmés. <a href="manage-courses.php">Créer un cours</a></p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nom du cours</th>
                                            <th>Date et heure</th>
                                            <th>Capacité</th>
                                            <th>Réservations</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($coach_courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                                <td><?php echo formatDateTime($course['date_time']); ?></td>
                                                <td><?php echo $course['capacity']; ?></td>
                                                <td><?php echo $course['reserved']; ?> / <?php echo $course['capacity']; ?></td>
                                                <td>
                                                    <a href="course-participants.php?course_id=<?php echo $course['id']; ?>" class="btn btn-info btn-sm">Participants</a>
                                                    <a href="edit-course.php?id=<?php echo $course['id']; ?>" class="btn btn-primary btn-sm">Modifier</a>
                                                    <a href="delete-course.php?id=<?php echo $course['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce cours ?')">Supprimer</a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                
                <?php elseif ($user_role === 'admin'): ?>
                    <!-- Tableau de bord Admin -->
                    <div class="dashboard-section">
                        <h3>Statistiques</h3>
                        <div class="stats-container">
                            <?php foreach ($users_count as $stat): ?>
                                <div class="stat-card">
                                    <h4><?php echo ucfirst($stat['role']); ?>s</h4>
                                    <p class="stat-number"><?php echo $stat['count']; ?></p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="dashboard-section">
                        <h3>Cours à venir</h3>
                        <p><a href="all-courses.php" class="btn btn-primary">Voir tous les cours</a></p>
                        
                        <?php if (empty($all_courses)): ?>
                            <p>Aucun cours programmé.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Nom du cours</th>
                                            <th>Date et heure</th>
                                            <th>Coach</th>
                                            <th>Capacité</th>
                                            <th>Réservations</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($all_courses as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($course['name']); ?></td>
                                                <td><?php echo formatDateTime($course['date_time']); ?></td>
                                                <td><?php echo htmlspecialchars($course['coach_email']); ?></td>
                                                <td><?php echo $course['capacity']; ?></td>
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
                    
                    <div class="dashboard-section">
                        <h3>Gestion des utilisateurs</h3>
                        <p><a href="manage-users.php" class="btn btn-primary">Gérer les utilisateurs</a></p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
       
    </div>
</body>
</html>
