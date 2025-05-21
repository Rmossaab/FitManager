<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du rôle
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: access-denied.php');
    exit;
}

// Récupération de tous les utilisateurs
$stmt = $pdo->prepare('
    SELECT u.id, u.email, u.role, 
           (SELECT COUNT(*) FROM courses WHERE coach_id = u.id) as courses_count,
           (SELECT COUNT(*) FROM reservations WHERE user_id = u.id) as reservations_count,
           (SELECT COUNT(*) FROM subscriptions WHERE user_id = u.id AND end_date >= CURDATE()) as has_subscription
    FROM users u
    ORDER BY u.role, u.email
');
$stmt->execute();
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des utilisateurs - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Gestion des utilisateurs</h1>
            
            <?php displayAlert(); ?>
            
            <div class="users-list">
                <?php if (empty($users)): ?>
                    <p>Aucun utilisateur trouvé.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Email</th>
                                    <th>Rôle</th>
                                    <th>Informations</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo ucfirst($user['role']); ?></td>
                                        <td>
                                            <?php if ($user['role'] === 'coach'): ?>
                                                <span class="badge badge-info"><?php echo $user['courses_count']; ?> cours</span>
                                            <?php elseif ($user['role'] === 'member'): ?>
                                                <span class="badge badge-info"><?php echo $user['reservations_count']; ?> réservations</span>
                                                <?php if ($user['has_subscription']): ?>
                                                    <span class="badge badge-success">Abonnement actif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Sans abonnement</span>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="edit-user.php?id=<?php echo $user['id']; ?>" class="btn btn-primary btn-sm">Modifier</a>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <a href="delete-user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ? Cette action est irréversible.')">Supprimer</a>
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
