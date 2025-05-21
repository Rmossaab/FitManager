<?php
// vérifier si lutilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// vérifier le role de ltilisateur
function hasRole($role) {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
}

// vérifier si ltilisateur un des roles spécifiés
function hasAnyRole($roles) {
    if (!isset($_SESSION['user_role'])) {
        return false;
    }
    
    return in_array($_SESSION['user_role'], $roles);
}

// redirige si utilisateur na pas le role requis
function requireRole($role) {
    if (!hasRole($role)) {
        header('Location: access-denied.php');
        exit;
    }
}

// nettoyer les entrées utilisateur
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// message d'alerte
function setAlert($type, $message) {
    $_SESSION['alert'] = [
        'type' => $type,
        'message' => $message
    ];
}

function displayAlert() {
    if (isset($_SESSION['alert'])) {
        $alert = $_SESSION['alert'];
        echo '<div class="alert alert-' . $alert['type'] . '">' . $alert['message'] . '</div>';
        unset($_SESSION['alert']);
    }
}

// formateage date heure
function formatDateTime($dateTime) {
    $date = new DateTime($dateTime);
    return $date->format('d/m/Y à H:i');
}
