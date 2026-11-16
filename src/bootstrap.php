<?php
// Point de départ commun : configuration, classes, session et en-têtes de sécurité.

declare(strict_types=1);

define('ROOT_DIR', dirname(__DIR__));

require ROOT_DIR . '/src/JsonStore.php';
require ROOT_DIR . '/src/View.php';
require ROOT_DIR . '/src/Markdown.php';
require ROOT_DIR . '/src/Kb.php';
require ROOT_DIR . '/src/Seo.php';
require ROOT_DIR . '/src/Auth.php';
require ROOT_DIR . '/src/Admin.php';
require ROOT_DIR . '/src/Theme.php';
require ROOT_DIR . '/src/Chart.php';
require ROOT_DIR . '/src/Audience.php';
require ROOT_DIR . '/src/Monitor.php';

// Toutes les dates (audience, supervision) sont à l'heure de Paris.
date_default_timezone_set('Europe/Paris');

$config = require ROOT_DIR . '/config.php';

// Réglages propres à la copie visitée (alwaysdata...), d'après le nom de domaine.
// Le nom vient du navigateur : il sert seulement à choisir dans la liste de config.php.
$host = strtolower(explode(':', (string) ($_SERVER['HTTP_HOST'] ?? ''))[0]);
if (isset($config['hosts'][$host])) {
    $config = array_merge($config, $config['hosts'][$host]);
}

// Génération de la version statique : on remplace l'adresse du site et on n'ouvre pas de session.
$config['static_build'] = getenv('STATIC_BUILD') === '1';
if ($config['static_build']) {
    $config = array_merge($config, $config['static']);
    $config['static_build'] = true;
    $config['admin_enabled'] = false;
    $config['audience_enabled'] = false;
}

$store = new JsonStore($config['data_dir']);

// En-têtes de sécurité envoyés sur toutes les pages.
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; style-src 'self'; script-src 'self'; form-action 'self'; frame-ancestors 'none'; base-uri 'self'");

// La visite est-elle en HTTPS ? Chez alwaysdata, un proxy chiffre la connexion devant Apache :
// on regarde donc aussi l'en-tête X-Forwarded-Proto qu'il ajoute (même logique que public/.htaccess).
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

// HSTS : demande au navigateur de n'utiliser que HTTPS pour ce site pendant hsts_max_age secondes,
// même si on tape « http:// ». Envoyé seulement en HTTPS (en HTTP, les navigateurs l'ignorent). Voir docs/https.md.
if ($https && !$config['static_build'] && (int) ($config['hsts_max_age'] ?? 0) > 0) {
    header('Strict-Transport-Security: max-age=' . (int) $config['hsts_max_age']);
}

// Session : cookie inaccessible au JavaScript, limité au site, sécurisé en HTTPS.
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_name('portfolio_session');
if ($config['static_build']) {
    $_SESSION = [];
} else {
    session_start();
}

$auth = new Auth($store, $config);
$theme = Theme::current($store);
