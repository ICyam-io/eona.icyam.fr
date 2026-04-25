<?php
// Inscription — création de compte utilisateur avec profil complet
// Registration — user account creation with full profile

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

session_start_secure();

// Rediriger si déjà connecté
// Redirect if already logged in
if (!empty($_SESSION['user_id'])) {
    header('Location: /journal.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom          = trim($_POST['prenom']          ?? '');
    $nom             = trim($_POST['nom']             ?? '');
    $email           = trim($_POST['email']           ?? '');
    $password        = $_POST['password']             ?? '';
    $date_naissance  = $_POST['date_naissance']       ?? '';
    $sexe            = $_POST['sexe']                 ?? '';
    $taille_cm       = (int)($_POST['taille_cm']      ?? 0);
    $poids_initial   = (float)($_POST['poids_initial']?? 0);
    $poids_objectif  = $_POST['poids_objectif'] !== '' ? (float)$_POST['poids_objectif'] : null;

    if ($prenom && $nom && $email && $password && $date_naissance && $sexe && $taille_cm && $poids_initial) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        try {
            $stmt = get_db()->prepare('
                INSERT INTO users
                    (prenom, nom, email, password_hash, date_naissance, sexe, taille_cm, poids_initial, poids_objectif)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([$prenom, $nom, $email, $hash, $date_naissance, $sexe, $taille_cm, $poids_initial, $poids_objectif]);
            $user_id = (int)get_db()->lastInsertId();
            login($user_id);
            header('Location: /journal.php');
            exit;
        } catch (PDOException $e) {
            // Email déjà utilisé (contrainte UNIQUE)
            // Email already in use (UNIQUE constraint)
            $error = 'Cet email est déjà utilisé.';
        }
    } else {
        $error = 'Tous les champs obligatoires doivent être remplis.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — Inscription</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container" style="padding-top: 2rem; padding-bottom: 2rem;">
    <h1 style="text-align:center; margin-bottom: 2rem;">Créer un compte</h1>

    <?php if ($error): ?>
        <div class="card" style="color: var(--color-danger);"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" class="card">
        <div class="form-group">
            <label>Prénom *</label>
            <input type="text" name="prenom" required>
        </div>
        <div class="form-group">
            <label>Nom *</label>
            <input type="text" name="nom" required>
        </div>
        <div class="form-group">
            <label>Email *</label>
            <input type="email" name="email" required>
        </div>
        <div class="form-group">
            <label>Mot de passe *</label>
            <input type="password" name="password" required minlength="8">
        </div>
        <div class="form-group">
            <label>Date de naissance *</label>
            <input type="date" name="date_naissance" required>
        </div>
        <div class="form-group">
            <label>Sexe *</label>
            <select name="sexe" required>
                <option value="">—</option>
                <option value="M">Homme</option>
                <option value="F">Femme</option>
            </select>
        </div>
        <div class="form-group">
            <label>Taille (cm) *</label>
            <input type="number" name="taille_cm" min="100" max="250" required>
        </div>
        <div class="form-group">
            <label>Poids actuel (kg) *</label>
            <input type="number" name="poids_initial" step="0.1" min="30" max="300" required>
        </div>
        <div class="form-group">
            <label>Objectif de poids (kg) — optionnel</label>
            <input type="number" name="poids_objectif" step="0.1" min="30" max="300">
        </div>
        <button type="submit" class="btn btn-primary">Créer mon compte</button>
    </form>

    <p style="text-align:center; margin-top: 1rem; font-size: 0.9rem; color: var(--color-text-muted);">
        Déjà un compte ? <a href="/login.php" style="color: var(--color-accent);">Se connecter</a>
    </p>
</main>
</body>
</html>
