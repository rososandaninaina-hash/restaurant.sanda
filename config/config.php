<?php
declare(strict_types=1);

/*
 * Configuration de la connexion MySQL.
 * Adaptez ces valeurs si votre installation WAMP utilise un autre mot de passe.
 */
$host = '127.0.0.1';
$dbname = 'restaurant_db';
$dbuser = 'root';
$dbpass = ''; // Par défaut, WAMP utilise souvent root sans mot de passe en local.

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO(
        "mysql:host={$host};dbname={$dbname};charset=utf8mb4",
        $dbuser,
        $dbpass,
        $options
    );
} catch (PDOException $e) {
    // Ne pas afficher les détails techniques de la connexion aux visiteurs.
    http_response_code(500);
    exit("Connexion à la base impossible. Vérifiez config/config.php et importez database.sql.");
}

/* Réglages de session : cookies non accessibles à JavaScript et protection HTTPS si disponible. */
$isHttps = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_set_cookie_params([
    'httponly' => true,
    'secure'   => $isHttps,
    'samesite' => 'Lax',
]);
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
