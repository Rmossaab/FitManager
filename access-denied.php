<?php
session_start();
require_once 'includes/functions.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès refusé - FitManager</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'includes/header.php'; ?>
        
        <main>
            <div class="access-denied">
                <h1>Accès refusé</h1>
                <p>Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
                <p><a href="dashboard.php" class="btn btn-primary">Retour au tableau de bord</a></p>
            </div>
        </main>
        
      
    </div>
</body>
</html>
