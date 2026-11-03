<?php
$fmtDate = fn (?int $t) => $t ? date('d/m/Y à H:i', $t) : '—';
$cert = $supervision['certificat'];
$pub = $supervision['publication'];
$lastPub = $pub['publication']['date'] ?? $pub['commit']['date'] ?? null;
?>
<h1>Tableau de bord</h1>
<p class="lead">Connecté en tant que <strong><?= e($_SESSION['admin']) ?></strong>. Voici l'essentiel de ton portfolio en un coup d'œil.</p>

<div class="dash-grid">
    <section class="panel" aria-labelledby="dash-supervision">
        <h2 id="dash-supervision"><a href="/admin/supervision">Supervision</a></h2>
        <p><?= View::partial('admin/_status', ['report' => $supervision]) ?></p>
        <dl class="kpis">
            <div><dt>Disponibilité 7 jours</dt><dd><?= $supervision['dispo']['7 jours'] !== null ? e(number_format($supervision['dispo']['7 jours'], 2, ',', ' ')) . ' %' : '—' ?></dd></div>
            <div><dt>Temps de réponse</dt><dd><?= $supervision['latence_moyenne'] !== null ? e($supervision['latence_moyenne']) . ' ms' : '—' ?></dd></div>
            <div><dt>Certificat HTTPS</dt><dd><?= isset($cert['jours_restants']) ? e($cert['jours_restants']) . ' j restants' : '—' ?></dd></div>
            <div><dt>Dernière publication</dt><dd><?= e($lastPub ? date('d/m/Y', $lastPub) : '—') ?></dd></div>
        </dl>
        <p class="muted">Dernière vérification : <?= e($fmtDate($supervision['dernier']['t'] ?? null)) ?></p>
    </section>

    <section class="panel" aria-labelledby="dash-audience">
        <h2 id="dash-audience"><a href="/admin/audience">Audience</a> <small>7 derniers jours</small></h2>
        <dl class="kpis">
            <div><dt>Visites</dt><dd><?= e($audience['visites']) ?></dd></div>
            <div><dt>Pages vues</dt><dd><?= e($audience['vues']) ?></dd></div>
        </dl>
        <?= Chart::bars(array_map(fn ($d) => $d['visites'], $audience['series']), 'Visites par jour sur 7 jours', ' visite(s)', 200) ?>
        <?php if ($audience['sources']): ?>
            <p class="muted">Première provenance : <strong><?= e(array_key_first($audience['sources'])) ?></strong></p>
        <?php endif; ?>
    </section>

    <section class="panel" aria-labelledby="dash-contenu">
        <h2 id="dash-contenu"><a href="/admin/contenu">Contenu</a></h2>
        <ul class="plain">
            <li><a href="/admin/profil">Profil</a></li>
            <?php foreach ($collections as $key => $c): ?>
                <li><a href="/admin/<?= e($key) ?>"><?= e($c['label']) ?></a> <span class="muted">(<?= $counts[$key] ?>)</span> · <a href="/admin/<?= e($key) ?>/edit">ajouter</a></li>
            <?php endforeach; ?>
        </ul>
    </section>
</div>
