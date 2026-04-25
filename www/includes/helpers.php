<?php
// Fonctions utilitaires partagées — BMR, score sommeil, formatage
// Shared utility functions — BMR, sleep score, formatting

// Calculer le BMR selon la formule Mifflin-St Jeor
// Calculate BMR using the Mifflin-St Jeor formula
function calculate_bmr(float $poids, int $taille, string $date_naissance, string $sexe): int
{
    $age = (int)(new DateTime())->diff(new DateTime($date_naissance))->y;
    $bmr = (10 * $poids) + (6.25 * $taille) - (5 * $age);
    return (int)round($bmr + ($sexe === 'M' ? 5 : -161));
}

// Calculer le score de sommeil 0–100 depuis les 4 durées (en minutes)
// Calculate sleep score 0–100 from the 4 durations (in minutes)
function calculate_sleep_score(int $eveil, int $paradoxal, int $lent, int $profond): int
{
    $total = $paradoxal + $lent + $profond;

    if ($total === 0) {
        return 0;
    }

    // 40 pts : durée totale — objectif 7h30 = 450 min
    // 40 pts: total duration — target 7h30 = 450 min
    $pts_duree = min($total / 450.0, 1.0) * 40;

    // 30 pts : proportion sommeil profond — objectif 20% du total
    // 30 pts: deep sleep proportion — target 20% of total
    $pts_profond = min($profond / ($total * 0.20), 1.0) * 30;

    // 20 pts : proportion sommeil paradoxal (REM) — objectif 25% du total
    // 20 pts: REM sleep proportion — target 25% of total
    $pts_rem = min($paradoxal / ($total * 0.25), 1.0) * 20;

    // 10 pts : temps d'éveil — 0 min = 10 pts, -1 pt par 10 min d'éveil
    // 10 pts: wake time — 0 min = 10 pts, -1 pt per 10 min of wake time
    $pts_eveil = max(10.0 - ($eveil / 10.0), 0.0);

    return (int)round($pts_duree + $pts_profond + $pts_rem + $pts_eveil);
}

// Formater une durée en minutes vers Xh YYmin
// Format a duration in minutes to Xh YYmin
function format_duration(int $minutes): string
{
    if ($minutes < 60) {
        return "{$minutes}min";
    }
    $h   = intdiv($minutes, 60);
    $min = $minutes % 60;
    return $min > 0 ? sprintf('%dh%02dmin', $h, $min) : "{$h}h";
}
