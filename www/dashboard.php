<?php
// Onglet 3 — Tableau de bord : graphiques, indicateurs, bilan journalier
// Tab 3 — Dashboard: charts, indicators, daily summary

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

require_auth();
$user = current_user();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — Tableau de bord</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container">
    <h1 style="margin: 1rem 0;">Tableau de bord</h1>

    <!-- Sélecteur de période — à implémenter en Phase 1 -->
    <!-- Period selector — to be implemented in Phase 1 -->
    <div class="card" style="text-align:center; color: var(--color-text-muted);">
        Graphiques et indicateurs — Phase 1
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
