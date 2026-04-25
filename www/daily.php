<?php
// Onglet 1 — Données journalières : type de journée, sommeil, poids, activité
// Tab 1 — Daily data: day type, sleep, weight, activity

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_auth();
$user = current_user();
$today = date('Y-m-d');

// Charger l'entrée du jour si elle existe déjà
// Load today's entry if it already exists
$stmt = get_db()->prepare('SELECT * FROM daily_logs WHERE user_id = ? AND log_date = ?');
$stmt->execute([$user['id'], $today]);
$log = $stmt->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_journee    = $_POST['type_journee']  ?? '';
    $score_source    = $_POST['score_source']  ?? 'manuel';
    $score_manuel    = $_POST['score_manuel']  !== '' ? (int)$_POST['score_manuel']  : null;
    $eveil           = $_POST['eveil']         !== '' ? (int)$_POST['eveil']         : null;
    $paradoxal       = $_POST['paradoxal']     !== '' ? (int)$_POST['paradoxal']     : null;
    $lent            = $_POST['lent']          !== '' ? (int)$_POST['lent']          : null;
    $profond         = $_POST['profond']       !== '' ? (int)$_POST['profond']       : null;
    $poids_jour      = $_POST['poids_jour']    !== '' ? (float)$_POST['poids_jour']  : null;
    $cal_exercice    = $_POST['cal_exercice']  !== '' ? (int)$_POST['cal_exercice']  : null;
    $nb_pas          = $_POST['nb_pas']        !== '' ? (int)$_POST['nb_pas']        : null;

    // Calculer le score si les 4 durées sont fournies
    // Compute score if all 4 durations are provided
    $score_final  = null;
    $score_source_final = null;

    if ($score_source === 'calcule' && $eveil !== null && $paradoxal !== null && $lent !== null && $profond !== null) {
        $score_final        = calculate_sleep_score($eveil, $paradoxal, $lent, $profond);
        $score_source_final = 'calcule';
    } elseif ($score_source === 'manuel' && $score_manuel !== null) {
        $score_final        = min(100, max(0, $score_manuel));
        $score_source_final = 'manuel';
    }

    if ($type_journee) {
        try {
            // Insérer ou mettre à jour l'entrée du jour (UNIQUE user_id + log_date)
            // Insert or update today's entry (UNIQUE user_id + log_date)
            $stmt = get_db()->prepare('
                INSERT INTO daily_logs
                    (user_id, log_date, type_journee, eveil_min, sommeil_paradoxal_min,
                     sommeil_lent_min, sommeil_profond_min, score_sommeil, score_sommeil_source,
                     poids_jour, calories_exercice, nb_pas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    type_journee           = VALUES(type_journee),
                    eveil_min              = VALUES(eveil_min),
                    sommeil_paradoxal_min  = VALUES(sommeil_paradoxal_min),
                    sommeil_lent_min       = VALUES(sommeil_lent_min),
                    sommeil_profond_min    = VALUES(sommeil_profond_min),
                    score_sommeil          = VALUES(score_sommeil),
                    score_sommeil_source   = VALUES(score_sommeil_source),
                    poids_jour             = VALUES(poids_jour),
                    calories_exercice      = VALUES(calories_exercice),
                    nb_pas                 = VALUES(nb_pas),
                    updated_at             = NOW()
            ');
            $stmt->execute([
                $user['id'], $today, $type_journee,
                $eveil, $paradoxal, $lent, $profond,
                $score_final, $score_source_final,
                $poids_jour, $cal_exercice, $nb_pas,
            ]);
            $success = 'Données enregistrées.';
            // Recharger l'entrée du jour
            // Reload today's entry
            $stmt = get_db()->prepare('SELECT * FROM daily_logs WHERE user_id = ? AND log_date = ?');
            $stmt->execute([$user['id'], $today]);
            $log = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'enregistrement.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — Ma journée</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container">
    <h1 style="margin: 1rem 0;">Ma journée</h1>
    <p style="color: var(--color-text-muted); margin-bottom: 1rem;"><?= date('l d F Y') ?></p>

    <?php if ($success): ?>
        <div class="card positive"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="card danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <!-- Type de journée -->
        <div class="form-group">
            <label>Type de journée *</label>
            <select name="type_journee" required>
                <option value="">— Choisir —</option>
                <?php
                $types = ['travail_sedentaire' => 'Travail sédentaire', 'travail_actif' => 'Travail actif',
                          'repos' => 'Repos', 'sport' => 'Sport / entraînement', 'fete' => 'Fête / événement',
                          'vacances' => 'Vacances', 'maladie' => 'Maladie / convalescence',
                          'stress' => 'Stress intense', 'voyage' => 'Voyage'];
                foreach ($types as $val => $label):
                    $selected = ($log['type_journee'] ?? '') === $val ? 'selected' : '';
                ?>
                    <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Score sommeil -->
        <div class="card">
            <p style="font-weight:600; margin-bottom: 1rem;">Sommeil de la nuit</p>

            <div class="form-group">
                <label>Mode de saisie</label>
                <select name="score_source" id="score_source">
                    <option value="manuel">Score direct (0–100)</option>
                    <option value="calcule">Détail depuis ma montre</option>
                </select>
            </div>

            <div id="bloc-manuel">
                <div class="form-group">
                    <label>Score de sommeil (0–100)</label>
                    <input type="number" name="score_manuel" min="0" max="100"
                           value="<?= htmlspecialchars($log['score_sommeil'] ?? '') ?>">
                </div>
            </div>

            <div id="bloc-calcule" style="display:none;">
                <div class="form-group">
                    <label>Temps d'éveil (min)</label>
                    <input type="number" name="eveil" min="0" value="<?= htmlspecialchars($log['eveil_min'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sommeil paradoxal / REM (min)</label>
                    <input type="number" name="paradoxal" min="0" value="<?= htmlspecialchars($log['sommeil_paradoxal_min'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sommeil lent / léger (min)</label>
                    <input type="number" name="lent" min="0" value="<?= htmlspecialchars($log['sommeil_lent_min'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sommeil profond (min)</label>
                    <input type="number" name="profond" min="0" value="<?= htmlspecialchars($log['sommeil_profond_min'] ?? '') ?>">
                </div>
                <?php if ($log && $log['score_sommeil_source'] === 'calcule'): ?>
                    <p style="color: var(--color-accent);">Score calculé : <?= $log['score_sommeil'] ?>/100</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="form-group">
            <label>Poids à jeun (kg)</label>
            <input type="number" name="poids_jour" step="0.1" min="30" max="300"
                   value="<?= htmlspecialchars($log['poids_jour'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Calories brûlées hier soir (exercice)</label>
            <input type="number" name="cal_exercice" min="0"
                   value="<?= htmlspecialchars($log['calories_exercice'] ?? '') ?>">
        </div>
        <div class="form-group">
            <label>Nombre de pas (hier)</label>
            <input type="number" name="nb_pas" min="0"
                   value="<?= htmlspecialchars($log['nb_pas'] ?? '') ?>">
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</main>

<nav class="bottom-nav">
    <a href="/daily.php">📅<span>Journée</span></a>
    <a href="/journal.php">📓<span>Journal</span></a>
    <a href="/dashboard.php">📊<span>Tableau</span></a>
    <a href="/profile.php">👤<span>Profil</span></a>
</nav>

<script src="/assets/js/main.js"></script>
<script>
// Basculer entre mode score direct et mode détaillé montre
// Toggle between direct score mode and detailed watch mode
document.getElementById('score_source').addEventListener('change', function() {
    document.getElementById('bloc-manuel').style.display   = this.value === 'manuel'  ? '' : 'none';
    document.getElementById('bloc-calcule').style.display  = this.value === 'calcule' ? '' : 'none';
});
</script>
</body>
</html>
