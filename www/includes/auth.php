<?php
// Gestion de l'authentification et des sessions PHP 30 jours
// Authentication and 30-day PHP session management

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Démarrer la session avec cookie sécurisé httpOnly
// Start session with secure httpOnly cookie
function session_start_secure(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'lifetime' => SESSION_LIFETIME,
            'path'     => '/',
            'secure'   => true,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}

// Vérifier si l'utilisateur est connecté, sinon rediriger vers /login.php
// Check if user is logged in, redirect to /login.php otherwise
function require_auth(): void
{
    session_start_secure();
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Récupérer l'utilisateur connecté depuis la session
// Get the currently logged-in user from the session
function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    $stmt = get_db()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

// Connecter un utilisateur — créer la session PHP et persister le token en BDD
// Log in a user — create PHP session and persist the token in DB
function login(int $user_id): void
{
    $_SESSION['user_id'] = $user_id;
    $_SESSION['token']   = bin2hex(random_bytes(32));

    $stmt = get_db()->prepare('
        INSERT INTO sessions (user_id, token, expires_at)
        VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))
    ');
    $stmt->execute([$user_id, $_SESSION['token'], SESSION_LIFETIME]);
}

// Déconnecter l'utilisateur — supprimer le token BDD et détruire la session PHP
// Log out the user — delete DB token and destroy PHP session
function logout(): void
{
    session_start_secure();
    if (!empty($_SESSION['token'])) {
        $stmt = get_db()->prepare('DELETE FROM sessions WHERE token = ?');
        $stmt->execute([$_SESSION['token']]);
    }
    session_destroy();
}
