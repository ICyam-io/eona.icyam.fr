<?php
// Déconnexion — détruit la session BDD + PHP, redirige vers /login.php
// Logout — destroys DB + PHP session, redirects to /login.php

require_once __DIR__ . '/includes/auth.php';

logout();

header('Location: /login.php');
exit;
