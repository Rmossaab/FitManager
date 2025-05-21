<?php
// Param de connexion
$host = 'localhost';
$dbname = 'fitmanager';
$username = 'root';
$password = '';

try {
    // Connexion  PDO
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // erreur de connexion
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}
