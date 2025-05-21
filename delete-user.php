<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du role
if (!isLoggedIn() || !hasRole('admin')) {
    header('Location: access-denied.php');
    exit;
}

// Vérification de lID de l'utilisateur
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-users.php');
    exit;
}

$user_id = (int)$_GET['id'];

// Protection contre la suppression de son propre compte
if ($user_id === (int)$_SESSION['user_id']) {
    setAlert('danger', 'Vous ne pouvez pas supprimer votre propre compte.');
    header('Location: manage-users.php');
    exit;
}

try {
    // Vérification que l'utilisateur existe
    $stmt = $pdo->prepare('SELECT id, role FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setAlert('danger', 'Utilisateur non trouvé.');
        header('Location: manage-users.php');
        exit;
    }
    
    // Début de la transaction
    $pdo->beginTransaction();
    
    // Suppression des réservations de l'utilisateur
    $stmt = $pdo->prepare('DELETE FROM reservations WHERE user_id = ?');
    $stmt->execute([$user_id]);
    
    // Suppression des abonnements de l'utilisateur
    $stmt = $pdo->prepare('DELETE FROM subscriptions WHERE user_id = ?');
    $stmt->execute([$user_id]);
    
    // Si c'est un coach, suppression de ses cours et des réservations associées
    if ($user['role'] === 'coach') {
        // Récupération des IDs des cours du coach
        $stmt = $pdo->prepare('SELECT id FROM courses WHERE coach_id = ?');
        $stmt->execute([$user_id]);
        $courses = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($courses)) {
            // Suppression des réservations pour ces cours
            $placeholders = implode(',', array_fill(0, count($courses), '?'));
            $stmt = $pdo->prepare("DELETE FROM reservations WHERE course_id IN ($placeholders)");
            $stmt->execute($courses);
            
            // Suppression des cours
            $stmt = $pdo->prepare('DELETE FROM courses WHERE coach_id = ?');
            $stmt->execute([$user_id]);
        }
    }
    
    // Suppression de l'utilisateur
    $stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $stmt->execute([$user_id]);
    
    // Validation de la transaction
    $pdo->commit();
    
    setAlert('success', 'L\'utilisateur a été supprimé avec succès.');
} catch (PDOException $e) {
    // Annulation de la transaction en cas d'erreur
    $pdo->rollBack();
    setAlert('danger', 'Erreur lors de la suppression de l\'utilisateur: ' . $e->getMessage());
}

header('Location: manage-users.php');
exit;
