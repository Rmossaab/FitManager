<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Vérification de la connexion et du rôle
if (!isLoggedIn() || !hasRole('member')) {
    header('Location: access-denied.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Vérification de l'ID du cours
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    header('Location: courses.php');
    exit;
}

$course_id = (int)$_GET['course_id'];

try {
    // Vérification de l'abonnement actif
    $stmt = $pdo->prepare('
        SELECT id FROM subscriptions 
        WHERE user_id = ? AND end_date >= CURDATE() 
        LIMIT 1
    ');
    $stmt->execute([$user_id]);
    
    if ($stmt->rowCount() === 0) {
        setAlert('danger', 'Vous devez avoir un abonnement actif pour réserver un cours.');
        header('Location: subscription.php');
        exit;
    }
    
    // Vérification que le cours existe et est dans le futur
    $stmt = $pdo->prepare('
        SELECT c.*, 
               (SELECT COUNT(*) FROM reservations WHERE course_id = c.id) as reserved,
               (SELECT COUNT(*) FROM reservations WHERE course_id = c.id AND user_id = ?) as is_reserved
        FROM courses c
        WHERE c.id = ? AND c.date_time > NOW()
    ');
    $stmt->execute([$user_id, $course_id]);
    $course = $stmt->fetch();
    
    if (!$course) {
        setAlert('danger', 'Ce cours n\'existe pas ou n\'est plus disponible.');
        header('Location: courses.php');
        exit;
    }
    
    // Vérification que le cours n'est pas déjà complet
    if ($course['reserved'] >= $course['capacity']) {
        setAlert('danger', 'Ce cours est complet.');
        header('Location: courses.php');
        exit;
    }
    
    // Vérification que l'utilisateur n'a pas déjà réservé ce cours
    if ($course['is_reserved'] > 0) {
        setAlert('danger', 'Vous avez déjà réservé ce cours.');
        header('Location: courses.php');
        exit;
    }
    
    // Création de la réservation
    $stmt = $pdo->prepare('INSERT INTO reservations (user_id, course_id) VALUES (?, ?)');
    $stmt->execute([$user_id, $course_id]);
    
    setAlert('success', 'Votre réservation a été effectuée avec succès.');
    header('Location: my-reservations.php');
    exit;
} catch (PDOException $e) {
    setAlert('danger', 'Erreur lors de la réservation: ' . $e->getMessage());
    header('Location: courses.php');
    exit;
}
