<?php
// Pastille d'état de la supervision, partagée entre le tableau de bord et la page Supervision.
$labels = ['ok' => 'En ligne', 'lent' => 'En ligne, mais lent', 'panne' => 'En panne', 'inconnu' => 'Pas encore vérifié'];
?>
<span class="status status-<?= e($report['etat']) ?>"><span class="status-dot" aria-hidden="true"></span><?= e($labels[$report['etat']]) ?></span>
