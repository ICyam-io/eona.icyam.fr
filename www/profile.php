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
    $poids_objectif = $_POST['poids_objectif'] !== '' ? (float)$_POST['poids_objectif'] : null;

    // La taille n'est mise à jour que si la case de déblocage est cochée
    // Height is only updated when the unlock checkbox is checked
    $taille_cm = isset($_POST['modifier_taille']) && $_POST['taille_cm'] !== ''
        ? (int)$_POST['taille_cm']
        : (int)$user['taille_cm'];

    if ($taille_cm >= 100 && $taille_cm <= 250) {
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
    <div class="page-header">
    <img src="/assets/img/logo_eona.svg" alt="EonA" class="app-logo">
    <h1>Mon profil</h1>
</div>

    <?php if ($success): ?>
        <div class="card positive"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card">
        <p><strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong></p>
        <p style="color: var(--color-text-muted);"><?= htmlspecialchars($user['email']) ?></p>
        <p style="margin-top: 0.5rem;">BMR de référence : <strong class="positive"><?= number_format($bmr) ?> kcal</strong></p>
    </div>

    <form method="POST" class="card">

        <!-- Taille — champ verrouillé par défaut, débloqué par la case à cocher -->
        <!-- Height — locked by default, unlocked via checkbox -->
        <div class="form-group">
            <label style="display:flex; align-items:center; gap:0.6rem; margin-bottom:0.4rem;">
                Taille (cm)
                <span style="font-size:0.8rem; color:var(--color-text-muted); display:flex; align-items:center; gap:0.3rem; cursor:pointer;">
                    <input type="checkbox" id="modifier_taille" name="modifier_taille" onchange="toggleTaille(this)">
                    Modifier
                </span>
            </label>
            <input type="number" name="taille_cm" id="champ_taille"
                   min="100" max="250"
                   value="<?= htmlspecialchars($user['taille_cm']) ?>"
                   disabled
                   style="opacity:0.45; cursor:not-allowed;">
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
    <a href="/daily.php"><i data-lucide="sun"></i><span>Journée</span></a>
    <a href="/journal.php"><i data-lucide="book-open"></i><span>Journal</span></a>
    <a href="/dashboard.php"><i data-lucide="bar-chart-2"></i><span>Tableau</span></a>
    <a href="/profile.php"><i data-lucide="user"></i><span>Profil</span></a>
</nav>

<script src="https://unpkg.com/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="/assets/js/main.js"></script>
<script>
// Débloquer ou reverrouiller le champ taille selon la case cochée
// Unlock or re-lock the height field based on checkbox state
function toggleTaille(checkbox) {
    const champ = document.getElementById('champ_taille');
    champ.disabled = !checkbox.checked;
    champ.style.opacity = checkbox.checked ? '1' : '0.45';
    champ.style.cursor  = checkbox.checked ? '' : 'not-allowed';
}
</script>
</body>
</html>
