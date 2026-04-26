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

// Clé secrète partagée avec n8n pour authentifier les webhooks entrants
// Shared secret with n8n to authenticate incoming webhooks
define('WEBHOOK_SECRET', 'd42dbb0ca8639bfd08f17d9301e26b5ee29e9ddaa143d80554e923f36c74f031');

// URL du webhook n8n — à renseigner après création de WF-106
// n8n webhook URL — to be filled after WF-106 creation
define('WEBHOOK_REGISTRATION_URL', $_ENV['WEBHOOK_REGISTRATION_URL'] ?? 'https://mcp.icyam.fr/webhook/eona-inscription');
