<?php
$fmtDate = fn (?int $t) => $t ? date('d/m/Y à H:i', $t) : '—';
$fmtPct = fn (?float $p) => $p === null ? '—' : number_format($p, 2, ',', ' ') . ' %';
$cert = $report['certificat'];
$pub = $report['publication'];
$last = $report['dernier'];
?>
<h1>Supervision</h1>
<p class="lead">Surveillance de <a href="<?= e($report['url']) ?>" target="_blank" rel="noopener"><?= e($report['url']) ?></a> : le site répond-il, à quelle vitesse, et son certificat HTTPS est-il valide ?</p>

<?php if ($checked): ?>
    <p class="alert success" role="status">Vérification effectuée.</p>
<?php endif; ?>

<div class="status-bar">
    <?= View::partial('admin/_status', ['report' => $report]) ?>
    <span class="muted">
        <?php if ($last): ?>
            Dernière vérification le <?= e($fmtDate($last['t'])) ?>
            <?= $last['ok'] ? '(code HTTP ' . e($last['code']) . ', ' . e($last['ms']) . ' ms)' : '(' . e($last['erreur']) . ')' ?>
        <?php else: ?>
            Aucune vérification enregistrée pour l'instant.
        <?php endif; ?>
    </span>
    <form method="post" action="/admin/supervision">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <button type="submit">Vérifier maintenant</button>
    </form>
</div>

<dl class="kpis kpis-large">
    <?php foreach ($report['dispo'] as $period => $pct): ?>
        <div><dt>Disponibilité <?= e($period) ?></dt><dd><?= e($fmtPct($pct)) ?></dd></div>
    <?php endforeach; ?>
    <div><dt>Temps de réponse moyen (24 h)</dt><dd><?= $report['latence_moyenne'] !== null ? e($report['latence_moyenne']) . ' ms' : '—' ?></dd></div>
</dl>

<section class="panel">
    <h2>Temps de réponse sur 24 heures</h2>
    <p class="muted">Moyenne par heure. Au-delà de <?= e($slowMs) ?> ms, le site est considéré comme lent.</p>
    <?= Chart::line($report['latence'], 'Temps de réponse moyen par heure', ' ms') ?>
</section>

<div class="dash-grid">
    <section class="panel">
        <h2>Certificat HTTPS</h2>
        <?php if (!empty($cert['ok'])): ?>
            <p class="big <?= $cert['jours_restants'] < 15 ? 'warn' : 'good' ?>"><?= e($cert['jours_restants']) ?> jours restants</p>
            <dl class="facts">
                <dt>Domaine</dt><dd><?= e($cert['domaine']) ?></dd>
                <dt>Émis par</dt><dd><?= e($cert['emetteur']) ?></dd>
                <dt>Expire le</dt><dd><?= e(date('d/m/Y', $cert['expire'])) ?></dd>
            </dl>
            <p class="muted">Le certificat est renouvelé automatiquement par l'hébergeur ; une alerte apparaît sous 15 jours.</p>
        <?php else: ?>
            <p class="empty"><?= e($cert['erreur'] ?? 'Pas encore vérifié.') ?></p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Publication</h2>
        <?php if (!empty($pub['ok'])): ?>
            <dl class="facts">
                <?php if (isset($pub['publication'])): ?>
                    <dt>Dernière publication</dt><dd><a href="<?= e($pub['publication']['lien']) ?>" target="_blank" rel="noopener"><?= e($fmtDate($pub['publication']['date'])) ?></a></dd>
                <?php endif; ?>
                <?php if (isset($pub['commit'])): ?>
                    <dt>Dernière modification du code</dt><dd><a href="<?= e($pub['commit']['lien']) ?>" target="_blank" rel="noopener"><?= e($fmtDate($pub['commit']['date'])) ?></a></dd>
                    <dt>Message</dt><dd><?= e($pub['commit']['message']) ?></dd>
                <?php endif; ?>
                <dt>Dépôt</dt><dd><a href="https://github.com/<?= e($pub['depot']) ?>" target="_blank" rel="noopener"><?= e($pub['depot']) ?></a></dd>
            </dl>
        <?php else: ?>
            <p class="empty"><?= e($pub['erreur'] ?? 'Pas encore vérifié.') ?></p>
        <?php endif; ?>
    </section>

    <section class="panel">
        <h2>Incidents récents</h2>
        <?php if (!$report['incidents']): ?>
            <p class="empty">Aucune panne enregistrée sur la période.</p>
        <?php else: ?>
            <ul class="plain incidents">
                <?php foreach ($report['incidents'] as $i): ?>
                    <li>
                        <strong><?= e($fmtDate($i['debut'])) ?></strong>
                        <?= $i['fin'] ? '→ rétabli le ' . e($fmtDate($i['fin'])) . ' (' . e((int) ceil(($i['fin'] - $i['debut']) / 60)) . ' min)' : '<span class="warn">en cours</span>' ?>
                        <br><span class="muted"><?= e($i['erreur']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<details class="help">
    <summary>Comment fonctionne la supervision ?</summary>
    <ul>
        <li>Toutes les 15 minutes, une tâche planifiée du serveur lance <code>php bin/monitor.php</code>. Le script ouvre le site comme un visiteur et note le code HTTP et le temps de réponse.</li>
        <li>Le certificat HTTPS est lu directement auprès du serveur (connexion TLS), toutes les 6 heures.</li>
        <li>La date de publication vient de l'API publique de GitHub, interrogée au plus une fois par heure.</li>
        <li>L'historique des <?= e($report['nb_controles']) ?> vérifications est gardé 30 jours dans <code>data/monitoring.json</code>.</li>
    </ul>
</details>
