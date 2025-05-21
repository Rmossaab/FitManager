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

// Traitement  dajout de cours
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
    } else {
        // Formatage de  date heure
        $date_time = $date . ' ' . $time . ':00';
        
        // Vérification que la date est future
        if (strtotime($date_time) <= time()) {
            $error = 'La date et l\'heure doivent être dans le futur.';
        } else {
            try {
                // Insertion du nouveau cours
                $stmt = $pdo->prepare('INSERT INTO courses (name, date_time, capacity, coach_id) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $date_time, $capacity, $user_id]);
                
                setAlert('success', 'Le cours a été ajouté avec succès.');
                header('Location: manage-courses.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Erreur lors de l\'ajout du cours: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un cours - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Ajouter un cours</h1>
            
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
                        <input type="text" id="name" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="time">Heure</label>
                        <input type="time" id="time" name="time" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="capacity">Capacité (nombre de places)</label>
                        <input type="number" id="capacity" name="capacity" min="1" max="50" required>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Ajouter le cours</button>
                        <a href="manage-courses.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </main>
        
        
    </div>
</body>
</html>
