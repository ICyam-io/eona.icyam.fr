<?php
// Onglet 2 — Journal : solde calorique du jour, saisie repas + tension
// Tab 2 — Journal: daily calorie balance, meal + blood pressure entry

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_auth();
$user  = current_user();
$today = date('Y-m-d');

// Récupérer et vider le message flash (redirect depuis daily.php)
// Retrieve and clear flash message (redirect from daily.php)
$flash = '';
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Calculer le BMR du jour (poids du jour si saisi, sinon poids initial)
// Compute today's BMR (today's weight if entered, otherwise initial weight)
$stmt = get_db()->prepare('SELECT poids_jour FROM daily_logs WHERE user_id = ? AND log_date = ?');
$stmt->execute([$user['id'], $today]);
$log   = $stmt->fetch();
$poids = $log['poids_jour'] ?? $user['poids_initial'];
$bmr   = calculate_bmr((float)$poids, (int)$user['taille_cm'], $user['date_naissance'], $user['sexe']);

// Total kcal ingérées aujourd'hui (repas validés)
// Total kcal ingested today (validated meals)
$stmt = get_db()->prepare('
    SELECT COALESCE(SUM(kcal_final), 0) AS total
    FROM meals
    WHERE user_id = ? AND log_date = ? AND kcal_final IS NOT NULL
');
$stmt->execute([$user['id'], $today]);
$ingere     = (int)$stmt->fetchColumn();
$solde      = $bmr - $ingere;
$solde_class = $solde >= 0 ? 'positive' : 'danger';

// Historique du jour : repas + tensions
// Today's history: meals + blood pressure readings
$stmt = get_db()->prepare('
    SELECT "meal" AS type, id, created_at, description_ia, kcal_final, source, NULL AS systolique, NULL AS diastolique, NULL AS bpm
    FROM meals WHERE user_id = ? AND log_date = ?
    UNION ALL
    SELECT "bp" AS type, id, created_at, NULL, NULL, NULL, systolique, diastolique, bpm
    FROM blood_pressure WHERE user_id = ? AND log_date = ?
    ORDER BY created_at ASC
');
$stmt->execute([$user['id'], $today, $user['id'], $today]);
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — Journal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container">

    <?php if ($flash): ?>
        <div class="card positive" style="margin-top: 1rem;">✅ <?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <!-- Solde calorique du jour -->
    <!-- Daily calorie balance -->
    <div class="budget-bar" style="margin-top: 1rem;">
        <div>
            <div class="value"><?= number_format($bmr) ?></div>
            <div class="label">Budget (BMR)</div>
        </div>
        <div>
            <div class="value"><?= number_format($ingere) ?></div>
            <div class="label">Ingéré</div>
        </div>
        <div>
            <div class="value <?= $solde_class ?>"><?= number_format(abs($solde)) ?></div>
            <div class="label"><?= $solde >= 0 ? 'Reste ✅' : 'Dépassé ⚠️' ?></div>
        </div>
    </div>

    <!-- Formulaire de saisie — Phase 1 -->
    <!-- Entry form — Phase 1 -->
    <div class="card">
        <p style="color: var(--color-text-muted); text-align:center;">
            Saisie des repas — Phase 1
        </p>
    </div>

    <!-- Historique du jour -->
    <!-- Today's history -->
    <?php if ($history): ?>
        <h2 style="margin: 1rem 0 0.5rem;">Aujourd'hui</h2>
        <?php foreach ($history as $entry): ?>
            <div class="card" style="margin-bottom: 0.5rem;">
                <?php if ($entry['type'] === 'meal'): ?>
                    <strong><?= htmlspecialchars($entry['description_ia'] ?? 'Repas') ?></strong>
                    <span style="color: var(--color-accent); float:right;"><?= $entry['kcal_final'] ?> kcal</span>
                    <?php if ($entry['source'] === 'pending'): ?>
                        <p style="color: var(--color-warning); font-size: 0.85rem;">Analyse en cours…</p>
                    <?php endif; ?>
                <?php else: ?>
                    <strong>Tension</strong>
                    <span style="float:right;"><?= $entry['systolique'] ?>/<?= $entry['diastolique'] ?> — <?= $entry['bpm'] ?> bpm</span>
                <?php endif; ?>
                <p style="font-size: 0.75rem; color: var(--color-text-muted);">
                    <?= date('H:i', strtotime($entry['created_at'])) ?>
                </p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

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
