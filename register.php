<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirection si déjà connecté
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    
    // Validation des entrées
    if (empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Veuillez remplir tous les champs.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif ($password !== $confirm_password) {
        $error = 'Les mots de passe ne correspondent pas.';
    } elseif (strlen($password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!in_array($role, ['member', 'coach'])) {
        $error = 'Rôle invalide.';
    } else {
        try {
            // Vérification si l'email existe déjà
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Cette adresse email est déjà utilisée.';
            } else {
                // Hachage du mot de passe
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Insertion du nouvel utilisateur
                $stmt = $pdo->prepare('INSERT INTO users (email, password, role) VALUES (?, ?, ?)');
                $stmt->execute([$email, $hashed_password, $role]);
                
                $success = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            }
        } catch (PDOException $e) {
            $error = 'Erreur d\'inscription: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-form fadeIn">
            <h1>FitManager</h1>
            <h2>Inscription</h2>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <?php echo $success; ?>
                    <p><a href="login.php" class="btn btn-primary btn-sm">Se connecter</a></p>
                </div>
            <?php else: ?>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password"><i class="fas fa-check-circle"></i> Confirmer le mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-user-tag"></i> Je suis :</label>
                        <div class="radio-group">
                            <label>
                                <input type="radio" name="role" value="member" checked> <i class="fas fa-user"></i> Membre
                            </label>
                            <label>
                                <input type="radio" name="role" value="coach"> <i class="fas fa-dumbbell"></i> Coach
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">
                            <i class="fas fa-user-plus"></i> S'inscrire
                        </button>
                    </div>
                    
                    <p class="text-center">Déjà inscrit ? <a href="login.php">Se connecter</a></p>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
