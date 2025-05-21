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
$error = '';
$success = '';

// Récupération des informations de l'utilisateur
$stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Traitement du formulaire de modification du profil
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation des entrées
    if (empty($email)) {
        $error = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif (!empty($new_password) && empty($current_password)) {
        $error = 'Veuillez entrer votre mot de passe actuel pour le changer.';
    } elseif (!empty($new_password) && $new_password !== $confirm_password) {
        $error = 'Les nouveaux mots de passe ne correspondent pas.';
    } elseif (!empty($new_password) && strlen($new_password) < 6) {
        $error = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } else {
        try {
            // Vérification si l'email existe déjà (sauf pour l'utilisateur actuel)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Cette adresse email est déjà utilisée par un autre utilisateur.';
            } else {
                // Vérification du mot de passe actuel si un nouveau mot de passe est fourni
                if (!empty($new_password)) {
                    $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch();
                    
                    if (!password_verify($current_password, $user_data['password'])) {
                        $error = 'Mot de passe actuel incorrect.';
                    } else {
                        // Mise à jour avec nouveau mot de passe
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('UPDATE users SET email = ?, password = ? WHERE id = ?');
                        $stmt->execute([$email, $hashed_password, $user_id]);
                        
                        $_SESSION['user_email'] = $email;
                        $success = 'Votre profil a été mis à jour avec succès.';
                    }
                } else {
                    // Mise à jour sans changer le mot de passe
                    $stmt = $pdo->prepare('UPDATE users SET email = ? WHERE id = ?');
                    $stmt->execute([$email, $user_id]);
                    
                    $_SESSION['user_email'] = $email;
                    $success = 'Votre profil a été mis à jour avec succès.';
                }
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la mise à jour du profil: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon profil - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Mon profil</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="profile-info">
                <div class="card">
                    <div class="card-body">
                        <h2>Informations personnelles</h2>
                        <p><strong>Email :</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                        <p><strong>Rôle :</strong> <?php echo ucfirst($user['role']); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="form-container">
                <h2>Modifier mon profil</h2>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="current_password">Mot de passe actuel (requis pour changer le mot de passe)</label>
                        <input type="password" id="current_password" name="current_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="new_password" name="new_password">
                        <small>Minimum 6 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirmer le nouveau mot de passe</label>
                        <input type="password" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                    </div>
                </form>
            </div>
        </main>
        
       
    </div>
</body>
</html>
