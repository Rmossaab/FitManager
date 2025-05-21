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
$error = '';
$success = '';

// Vérification de l'ID du cours
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-courses.php');
    exit;
}

$course_id = (int)$_GET['id'];

// Récupération des informations du cours
$stmt = $pdo->prepare('
    SELECT c.*, (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved
    FROM courses c
    WHERE c.id = ? AND c.coach_id = ?
');
$stmt->execute([$course_id, $user_id]);
$course = $stmt->fetch();

// Vérification que le cours existe et appartient au coach
if (!$course) {
    setAlert('danger', 'Cours non trouvé ou vous n\'êtes pas autorisé à le modifier.');
    header('Location: manage-courses.php');
    exit;
}

// Vérification que le cours n'est pas passé
if (strtotime($course['date_time']) < time()) {
    setAlert('danger', 'Vous ne pouvez pas modifier un cours passé.');
    header('Location: manage-courses.php');
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $date = $_POST['date'];
    $time = $_POST['time'];
    $capacity = (int)$_POST['capacity'];
    
    // Validation des entrées
    if (empty($name) || empty($date) || empty($time) || empty($capacity)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif ($capacity <= 0 || $capacity > 50) {
        $error = 'La capacité doit être comprise entre 1 et 50.';
    } elseif ($capacity < $course['reserved']) {
        $error = 'La capacité ne peut pas être inférieure au nombre de réservations actuelles (' . $course['reserved'] . ').';
    } else {
        // Formatage de la date et de l'heure
        $date_time = $date . ' ' . $time . ':00';
        
        // Vérification que la date est future
        if (strtotime($date_time) <= time()) {
            $error = 'La date et l\'heure doivent être dans le futur.';
        } else {
            try {
                // Mise à jour du cours
                $stmt = $pdo->prepare('UPDATE courses SET name = ?, date_time = ?, capacity = ? WHERE id = ? AND coach_id = ?');
                $stmt->execute([$name, $date_time, $capacity, $course_id, $user_id]);
                
                setAlert('success', 'Le cours a été modifié avec succès.');
                header('Location: manage-courses.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Erreur lors de la modification du cours: ' . $e->getMessage();
            }
        }
    }
}

// Préparation des valeurs pour le formulaire
$course_date = date('Y-m-d', strtotime($course['date_time']));
$course_time = date('H:i', strtotime($course['date_time']));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un cours - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Modifier un cours</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="name">Nom du cours</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($course['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo $course_date; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Heure</label>
                        <input type="time" id="time" name="time" value="<?php echo $course_time; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Capacité (nombre de places)</label>
                        <input type="number" id="capacity" name="capacity" value="<?php echo $course['capacity']; ?>" min="<?php echo $course['reserved']; ?>" max="50" required>
                        <small>Réservations actuelles: <?php echo $course['reserved']; ?></small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Modifier le cours</button>
                        <a href="manage-courses.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </main>
        
       
    </div>
</body>
</html>
