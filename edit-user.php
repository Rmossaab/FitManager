<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du rôle
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: access-denied.php');
    exit;
}

// Vérification de l'ID de l'utilisateur
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-users.php');
    exit;
}

$user_id = (int)$_GET['id'];
$error = '';
$success = '';

// Récupération des informations de l'utilisateur
$stmt = $pdo->prepare('SELECT id, email, role FROM users WHERE id = ?');
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    setAlert('danger', 'Utilisateur non trouvé.');
    header('Location: manage-users.php');
    exit;
}

// Traitement du formulaire de modification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $role = $_POST['role'];
    $password = $_POST['password']; // Optionnel, peut être vide
    
    // Validation des entrées
    if (empty($email)) {
        $error = 'L\'email est requis.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez entrer une adresse email valide.';
    } elseif (!in_array($role, ['admin', 'coach', 'member'])) {
        $error = 'Rôle invalide.';
    } else {
        try {
            // Vérification si l'email existe déjà (sauf pour l'utilisateur actuel)
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user_id]);
            
            if ($stmt->rowCount() > 0) {
                $error = 'Cette adresse email est déjà utilisée par un autre utilisateur.';
            } else {
                // Mise à jour de l'utilisateur
                if (!empty($password)) {
                    // Mise à jour avec nouveau mot de passe
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('UPDATE users SET email = ?, role = ?, password = ? WHERE id = ?');
                    $stmt->execute([$email, $role, $hashed_password, $user_id]);
                } else {
                    // Mise à jour sans changer le mot de passe
                    $stmt = $pdo->prepare('UPDATE users SET email = ?, role = ? WHERE id = ?');
                    $stmt->execute([$email, $role, $user_id]);
                }
                
                setAlert('success', 'L\'utilisateur a été modifié avec succès.');
                header('Location: manage-users.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Erreur lors de la modification de l\'utilisateur: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un utilisateur - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <h1>Modifier un utilisateur</h1>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <div class="form-container">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="role">Rôle</label>
                        <select id="role" name="role" required>
                            <option value="member" <?php echo $user['role'] === 'member' ? 'selected' : ''; ?>>Membre</option>
                            <option value="coach" <?php echo $user['role'] === 'coach' ? 'selected' : ''; ?>>Coach</option>
                            <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Administrateur</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                        <input type="password" id="password" name="password">
                        <small>Minimum 6 caractères</small>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                        <a href="manage-users.php" class="btn btn-secondary">Annuler</a>
                    </div>
                </form>
            </div>
        </main>
        
     
    </div>
</body>
</html>
