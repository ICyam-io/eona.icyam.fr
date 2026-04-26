<?php
// Onglet 1 — Données journalières : type de journée, sommeil, poids, activité
// Tab 1 — Daily data: day type, sleep, weight, activity

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_auth();
$user      = current_user();
$yesterday = date('Y-m-d', strtotime('yesterday'));
$today     = date('Y-m-d');

// Charger l'entrée d'hier si elle existe déjà
// Load yesterday's entry if it already exists
$stmt = get_db()->prepare('SELECT * FROM daily_logs WHERE user_id = ? AND log_date = ?');
$stmt->execute([$user['id'], $yesterday]);
$log = $stmt->fetch();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type_journee = $_POST['type_journee']  ?? '';
    $score_source = $_POST['score_source']  ?? 'manuel';
    $score_manuel = $_POST['score_manuel']  !== '' ? (int)$_POST['score_manuel']  : null;
    $eveil        = $_POST['eveil']         !== '' ? (int)$_POST['eveil']         : null;
    $paradoxal    = $_POST['paradoxal']     !== '' ? (int)$_POST['paradoxal']     : null;
    $lent         = $_POST['lent']          !== '' ? (int)$_POST['lent']          : null;
    $profond      = $_POST['profond']       !== '' ? (int)$_POST['profond']       : null;
    $poids_jour   = $_POST['poids_jour']    !== '' ? (float)$_POST['poids_jour']  : null;
    $cal_exercice = $_POST['cal_exercice']  !== '' ? (int)$_POST['cal_exercice']  : null;
    $nb_pas       = $_POST['nb_pas']        !== '' ? (int)$_POST['nb_pas']        : null;

    // Calculer le score si les 4 durées sont fournies
    // Compute score if all 4 durations are provided
    $score_final        = null;
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
            // Insérer ou mettre à jour l'entrée d'hier (UNIQUE user_id + log_date)
            // Insert or update yesterday's entry (UNIQUE user_id + log_date)
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
                $user['id'], $yesterday, $type_journee,
                $eveil, $paradoxal, $lent, $profond,
                $score_final, $score_source_final,
                $poids_jour, $cal_exercice, $nb_pas,
            ]);
            // Message flash + redirection vers le journal
            // Flash message + redirect to journal
            $_SESSION['flash'] = 'Données enregistrées.';
            header('Location: /journal.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'enregistrement.';
        }
    }
}

// Retourne le nom du jour en français (Lundi, Mardi…)
// Returns the French day name (Lundi, Mardi…)
function jour_fr(string $ymd): string
{
    $jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    return $jours[(int)date('w', strtotime($ymd))];
}

// Retourne "vendredi 24 avril" depuis une date Y-m-d
// Returns "vendredi 24 avril" from a Y-m-d date
function date_court(string $ymd): string
{
    $mois = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    $ts = strtotime($ymd);
    return strtolower(jour_fr($ymd)) . ' ' . date('j', $ts) . ' ' . $mois[(int)date('n', $ts)];
}

$label_hier = date_court($yesterday);  // ex. "vendredi 24 avril"
$label_auj  = date_court($today);      // ex. "samedi 25 avril"
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
    <div class="page-header">
    <img src="/assets/img/logo_eona.svg" alt="EonA" class="app-logo">
    <h1>Ma journée</h1>
</div>

    <?php if ($error): ?>
        <div class="card danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <!-- ── Section 1 : Journée de la veille ── -->
        <!-- Section 1: Yesterday's day data -->
        <div class="card" style="margin-bottom: 1rem;">
            <p class="section-label">📅 Journée du <?= $label_hier ?></p>

            <div class="form-group">
                <label>Type de journée *</label>
                <select name="type_journee" required>
                    <option value="">— Choisir —</option>
                    <?php
                    $types = [
                        'travail_sedentaire' => 'Travail sédentaire',
                        'travail_actif'      => 'Travail actif',
                        'repos'              => 'Repos',
                        'sport'              => 'Sport / entraînement',
                        'fete'               => 'Fête / événement',
                        'vacances'           => 'Vacances',
                        'maladie'            => 'Maladie / convalescence',
                        'stress'             => 'Stress intense',
                        'voyage'             => 'Voyage',
                    ];
                    foreach ($types as $val => $label):
                        $selected = ($log['type_journee'] ?? '') === $val ? 'selected' : '';
                    ?>
                        <option value="<?= $val ?>" <?= $selected ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Calories d'exercice (bracelet / montre)</label>
                <input type="number" name="cal_exercice" min="0"
                       placeholder="kcal actives mesurées"
                       value="<?= htmlspecialchars($log['calories_exercice'] ?? '') ?>">
                <small style="color:var(--color-text-muted);">Dépenses actives uniquement.</small>
            </div>

            <div class="form-group">
                <label>Nombre de pas</label>
                <input type="number" name="nb_pas" min="0"
                       value="<?= htmlspecialchars($log['nb_pas'] ?? '') ?>">
            </div>
        </div>

        <!-- ── Section 2 : Nuit de la veille au jour actuel ── -->
        <!-- Section 2: Night from yesterday to today -->
        <div class="card" style="margin-bottom: 1rem;">
            <p class="section-label">🌙 Nuit du <?= $label_hier ?> au <?= $label_auj ?></p>

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
                    <input type="number" name="eveil" min="0"
                           value="<?= htmlspecialchars($log['eveil_min'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sommeil paradoxal / REM (min)</label>
                    <input type="number" name="paradoxal" min="0"
                           value="<?= htmlspecialchars($log['sommeil_paradoxal_min'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sommeil lent / léger (min)</label>
                    <input type="number" name="lent" min="0"
                           value="<?= htmlspecialchars($log['sommeil_lent_min'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Sommeil profond (min)</label>
                    <input type="number" name="profond" min="0"
                           value="<?= htmlspecialchars($log['sommeil_profond_min'] ?? '') ?>">
                </div>
                <?php if ($log && $log['score_sommeil_source'] === 'calcule'): ?>
                    <p style="color: var(--color-accent);">Score calculé : <?= $log['score_sommeil'] ?>/100</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- ── Section 3 : Matin du jour actuel ── -->
        <!-- Section 3: This morning's data -->
        <div class="card" style="margin-bottom: 1rem;">
            <p class="section-label">☀️ Matin du <?= $label_auj ?></p>

            <div class="form-group">
                <label>Poids à jeun (kg)</label>
                <input type="number" name="poids_jour" step="0.1" min="30" max="300"
                       value="<?= htmlspecialchars($log['poids_jour'] ?? '') ?>">
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
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
// Basculer entre mode score direct et mode détaillé montre
// Toggle between direct score mode and detailed watch mode
document.getElementById('score_source').addEventListener('change', function() {
    document.getElementById('bloc-manuel').style.display  = this.value === 'manuel'  ? '' : 'none';
    document.getElementById('bloc-calcule').style.display = this.value === 'calcule' ? '' : 'none';
});
</script>
</body>
</html>
