<?php
// Point d'entrée — redirige vers /journal.php si connecté, sinon /login.php
// Entry point — redirects to /journal.php if logged in, otherwise /login.php

require_once __DIR__ . '/includes/auth.php';

session_start_secure();

if (!empty($_SESSION['user_id'])) {
    header('Location: /journal.php');
} else {
    header('Location: /login.php');
}
exit;
