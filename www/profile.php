<?php
// Page profil — affichage et modification des données utilisateur
// Profile page — display and update user data

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_auth();
$user    = current_user();
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $taille_cm      = (int)($_POST['taille_cm']      ?? 0);
    $poids_objectif = $_POST['poids_objectif'] !== '' ? (float)$_POST['poids_objectif'] : null;

    if ($taille_cm) {
        $stmt = get_db()->prepare('
            UPDATE users SET taille_cm = ?, poids_objectif = ?, updated_at = NOW()
            WHERE id = ?
        ');
        $stmt->execute([$taille_cm, $poids_objectif, $user['id']]);
        $user    = current_user();
        $success = 'Profil mis à jour.';
    }
}

$bmr = calculate_bmr(
    (float)$user['poids_initial'],
    (int)$user['taille_cm'],
    $user['date_naissance'],
    $user['sexe']
);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — Profil</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container">
    <h1 style="margin: 1rem 0;">Mon profil</h1>

    <?php if ($success): ?>
        <div class="card positive"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <p><strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong></p>
        <p style="color: var(--color-text-muted);"><?= htmlspecialchars($user['email']) ?></p>
        <p style="margin-top: 0.5rem;">BMR de référence : <strong class="positive"><?= number_format($bmr) ?> kcal</strong></p>
    </div>

    <form method="POST" class="card">
        <div class="form-group">
            <label>Taille (cm)</label>
            <input type="number" name="taille_cm" min="100" max="250"
                   value="<?= htmlspecialchars($user['taille_cm']) ?>" required>
        </div>
        <div class="form-group">
            <label>Objectif de poids (kg)</label>
            <input type="number" name="poids_objectif" step="0.1" min="30" max="300"
                   value="<?= htmlspecialchars($user['poids_objectif'] ?? '') ?>">
        </div>
        <button type="submit" class="btn btn-primary">Mettre à jour</button>
    </form>

    <div style="text-align:center; margin-top: 1rem;">
        <a href="/logout.php" style="color: var(--color-danger); font-size: 0.9rem;">Se déconnecter</a>
    </div>
</main>

<nav class="bottom-nav">
    <a href="/daily.php">📅<span>Journée</span></a>
    <a href="/journal.php">📓<span>Journal</span></a>
    <a href="/dashboard.php">📊<span>Tableau</span></a>
    <a href="/profile.php">👤<span>Profil</span></a>
</nav>

<script src="/assets/js/main.js"></script>
</body>
</html>
