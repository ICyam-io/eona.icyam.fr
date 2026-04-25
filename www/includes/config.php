<?php
// Configuration globale de l'application EonA
// Global configuration for the EonA application

define('APP_NAME', 'EonA');
define('APP_ENV',  $_ENV['APP_ENV']          ?? 'prod');
define('SESSION_LIFETIME', (int)($_ENV['SESSION_LIFETIME'] ?? 2592000));

// URL de base déduite depuis l'hôte HTTP
// Base URL derived from HTTP host
define('BASE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'eona.icyam.fr'));

// Chemin absolu vers le dossier uploads (hors DocumentRoot)
// Absolute path to the uploads folder (outside DocumentRoot)
define('UPLOADS_PATH', '/var/www/uploads');
define('PENDING_PATH', UPLOADS_PATH . '/pending');
