<?php
// Page de connexion — email + mot de passe, session 30 jours
// Login page — email + password, 30-day session

require_once __DIR__ . '/includes/auth.php';

session_start_secure();

// Rediriger si déjà connecté
// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: /journal.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email && $password) {
        $stmt = get_db()->prepare('SELECT id, password_hash FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Vérifier le hash bcrypt — login si valide
        // Verify bcrypt hash — log in if valid
        if ($user && password_verify($password, $user['password_hash'])) {
            login((int)$user['id']);
            header('Location: /journal.php');
            exit;
        }
    }
    $error = 'Email ou mot de passe incorrect.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — Connexion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container" style="padding-top: 3rem;">
    <img src="/assets/img/logo_eona.svg" alt="EonA" class="login-logo">
    <h1 class="login-title">EonA</h1>

    <?php if ($error): ?>
        <div class="card" style="color: var(--color-danger); margin-bottom: 1rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="card">
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required autocomplete="email">
        </div>
        <div class="form-group">
            <label for="password">Mot de passe</label>
            <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>

    <p style="text-align:center; margin-top: 1rem; font-size: 0.9rem; color: var(--color-text-muted);">
        Pas encore de compte ? <a href="/register.php" style="color: var(--color-accent);">S'inscrire</a>
    </p>
</main>
</body>
</html>
