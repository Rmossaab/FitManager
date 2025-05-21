<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du role
if (!isLoggedIn() || !hasRole('coach')) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérification de ID du cours
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: manage-courses.php');
    exit;
}

$course_id = (int)$_GET['id'];

try {
    // Vérification que le cours existe et appartient au coach
    $stmt = $pdo->prepare('SELECT date_time FROM courses WHERE id = ? AND coach_id = ?');
    $stmt->execute([$course_id, $user_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        setAlert('danger', 'Cours non trouvé ou vous n\'êtes pas autorisé à le supprimer.');
        header('Location: manage-courses.php');
        exit;
    }
    
    // Vérification que le cours n'est pas passé
    if (strtotime($course['date_time']) < time()) {
        setAlert('danger', 'Vous ne pouvez pas supprimer un cours passé.');
        header('Location: manage-courses.php');
        exit;
    }
    
    // Début de la transaction
    $pdo->beginTransaction();
    
    // Suppression des réservations associées
    $stmt = $pdo->prepare('DELETE FROM reservations WHERE course_id = ?');
    $stmt->execute([$course_id]);
    
    // Suppression du cours
    $stmt = $pdo->prepare('DELETE FROM courses WHERE id = ? AND coach_id = ?');
    $stmt->execute([$course_id, $user_id]);
    
    // Validation de la transaction
    $pdo->commit();
    
    setAlert('success', 'Le cours a été supprimé avec succès.');
} catch (PDOException $e) {
    // Annulation de la transaction en cas d'erreur
    $pdo->rollBack();
    setAlert('danger', 'Erreur lors de la suppression du cours: ' . $e->getMessage());
}

header('Location: manage-courses.php');
exit;
