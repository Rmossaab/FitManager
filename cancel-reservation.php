<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du role
if (!isLoggedIn() || !hasRole('member')) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérification de lID du cours
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Location: my-reservations.php');
    exit;
}

$course_id = (int)$_GET['course_id'];

try {
    // Vérification que la réservation existe 
    $stmt = $pdo->prepare('
        SELECT r.id, c.date_time 
        FROM reservations r
        JOIN courses c ON r.course_id = c.id
        WHERE r.user_id = ? AND r.course_id = ?
    ');
    $stmt->execute([$user_id, $course_id]);
    $reservation = $stmt->fetch();
    
    if (!$reservation) {
        setAlert('danger', 'Cette réservation n\'existe pas ou ne vous appartient pas.');
        header('Location: my-reservations.php');
        exit;
    }
    
    // Vérifi que le cours nest pas déjà passé
    if (strtotime($reservation['date_time']) < time()) {
        setAlert('danger', 'Vous ne pouvez pas annuler une réservation pour un cours passé.');
        header('Location: my-reservations.php');
        exit;
    }
    
    // Suppression de la réservation
    $stmt = $pdo->prepare('DELETE FROM reservations WHERE id = ?');
    $stmt->execute([$reservation['id']]);
    
    setAlert('success', 'Votre réservation a été annulée avec succès.');
} catch (PDOException $e) {
    setAlert('danger', 'Erreur lors de l\'annulation de la réservation: ' . $e->getMessage());
}

// Redirection vers la page d'origine
$referer = $_SERVER['HTTP_REFERER'] ?? 'my-reservations.php';
if (strpos($referer, 'courses.php') !== false) {
    header('Location: courses.php');
} else {
    header('Location: my-reservations.php');
}
exit;
