<?php
// Vérifie que le site répond et enregistre le résultat dans data/monitoring.json.
// À lancer en tâche planifiée toutes les 15 minutes (AlwaysData › Avancé › Tâches planifiées) :
//   php bin/monitor.php
// Affiche une ligne de résultat ; le code de sortie vaut 1 si le site est en panne.

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    exit('À lancer en ligne de commande uniquement.');
}

putenv('STATIC_BUILD=0');
$_SERVER['REQUEST_URI'] = '/';
require dirname(__DIR__) . '/src/bootstrap.php';

$check = (new Monitor($store, $config))->run(in_array('--complet', $argv, true));
echo date('Y-m-d H:i:s') . ' ' . $config['monitor']['url'] . ' : '
    . ($check['ok'] ? "OK ({$check['code']}, {$check['ms']} ms)" : 'PANNE (' . $check['erreur'] . ')') . PHP_EOL;
exit($check['ok'] ? 0 : 1);
