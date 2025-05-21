<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Si l'utilisateur est déjà connecté, redirection vers le tableau de bord
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Sinon, affichage de la landing page
include 'index.html';
?>
