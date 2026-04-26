<?php
// Validation ou refus d'une inscription — appelé depuis les liens dans l'email n8n
// Registration approval or refusal — called from the links in the n8n email

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

$action = $_GET['action'] ?? '';
$token  = $_GET['token']  ?? '';

// Valider les paramètres
// Validate parameters
if (!in_array($action, ['accept', 'refuse'], true) || strlen($token) !== 64) {
    http_response_code(400);
    die('Lien invalide.');
}

// Trouver l'utilisateur par son token de validation
// Find the user by their validation token
$stmt = get_db()->prepare('SELECT id, prenom, nom, email, statut FROM users WHERE validation_token = ?');
$stmt->execute([$token]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    die('Lien expiré ou déjà utilisé.');
}

// Refuser une action sur un compte déjà traité
// Reject action on an already-processed account
if ($user['statut'] !== 'pending') {
    die('Ce compte a déjà été traité (statut : ' . htmlspecialchars($user['statut']) . ').');
}

// Appliquer la décision et invalider le token
// Apply the decision and invalidate the token
$nouveau_statut = $action === 'accept' ? 'active' : 'refused';
$stmt = get_db()->prepare('UPDATE users SET statut = ?, validation_token = NULL WHERE id = ?');
$stmt->execute([$nouveau_statut, $user['id']]);

// Si accepté, notifier l'utilisateur par email via n8n
// If accepted, notify the user by email via n8n
if ($action === 'accept' && WEBHOOK_CONFIRMATION_URL) {
    $payload = json_encode([
        'event'  => 'confirmation',
        'prenom' => $user['prenom'],
        'nom'    => $user['nom'],
        'email'  => $user['email'],
    ]);
    $ch = curl_init(WEBHOOK_CONFIRMATION_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'X-EonA-Secret: ' . WEBHOOK_SECRET,
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

$label   = $action === 'accept' ? 'Accès accordé ✅' : 'Accès refusé ❌';
$couleur = $action === 'accept' ? '#6EA593' : '#E74C3C';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EonA — <?= $label ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/main.css">
</head>
<body>
<main class="container" style="padding-top: 3rem; text-align:center;">
    <img src="/assets/img/logo_eona.svg" alt="EonA" class="login-logo">
    <h1 style="margin: 1rem 0 0.5rem; color: <?= $couleur ?>;"><?= $label ?></h1>
    <p style="color: var(--color-text-muted);">
        <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
        — <?= htmlspecialchars($user['email']) ?>
    </p>
</main>
</body>
</html>
