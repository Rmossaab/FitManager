<header>
    <div class="container">
        <div class="logo">
            <h1>FitManager</h1>
        </div>
        <?php if (isLoggedIn()): ?>
        <nav>
            <ul>
                <li><a href="dashboard.php">Tableau de bord</a></li>
                
                <?php if (hasRole('member')): ?>
                <li><a href="courses.php">Cours disponibles</a></li>
                <li><a href="my-reservations.php">Mes réservations</a></li>
                <li><a href="subscription.php">Mon abonnement</a></li>
                <?php endif; ?>
                
                <?php if (hasRole('coach')): ?>
                <li><a href="manage-courses.php">Gérer mes cours</a></li>
                <?php endif; ?>
                
                <?php if (hasRole('admin')): ?>
                <li><a href="all-courses.php">Tous les cours</a></li>
                <li><a href="manage-users.php">Gérer les utilisateurs</a></li>
                <?php endif; ?>
                
                <li><a href="profile.php">Mon profil</a></li>
                <li><a href="logout.php">Déconnexion</a></li>
            </ul>
        </nav>
        <?php endif; ?>
        <!--  Font Awesome -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    </div>
</header>
