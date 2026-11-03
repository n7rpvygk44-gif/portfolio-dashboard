<?php
$total = fn (array $list) => max(1, array_sum($list));
$table = function (string $caption, string $col, array $rows, int $limit = 10) use ($total): string {
    if (!$rows) {
        return '<p class="empty">Pas encore de données.</p>';
    }
    $sum = $total($rows);
    $html = '<table class="admin-table compact"><caption>' . e($caption) . '</caption><thead><tr><th scope="col">' . e($col) . '</th><th scope="col" class="num">Nombre</th><th scope="col" class="num">Part</th></tr></thead><tbody>';
    foreach (array_slice($rows, 0, $limit, true) as $name => $n) {
        $pct = round(100 * $n / $sum);
        $html .= '<tr><th scope="row">' . e($name) . '</th><td class="num">' . e($n) . '</td><td class="num"><span class="share"><span class="share-bar share-' . (int) $pct . '"></span>' . e($pct) . ' %</span></td></tr>';
    }
    return $html . '</tbody></table>';
};
?>
<h1>Audience</h1>
<p class="lead">Qui visite le portfolio, quelles pages et d'où viennent les visiteurs. Mesure sans cookie ni adresse IP : seuls des compteurs par jour sont enregistrés.</p>

<?php if (!$enabled): ?>
    <p class="alert error">La mesure d'audience est désactivée dans <code>config.php</code> (<code>audience_enabled</code>).</p>
<?php endif; ?>

<nav class="tabs" aria-label="Période">
    <?php foreach ([7 => '7 jours', 30 => '30 jours', 90 => '3 mois', 365 => '12 mois'] as $d => $label): ?>
        <a href="/admin/audience?jours=<?= $d ?>"<?= $report['jours'] === $d ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<dl class="kpis kpis-large">
    <div><dt>Visites</dt><dd><?= e($report['visites']) ?></dd></div>
    <div><dt>Pages vues</dt><dd><?= e($report['vues']) ?></dd></div>
    <div><dt>Pages par visite</dt><dd><?= e(str_replace('.', ',', (string) $report['pages_par_visite'])) ?></dd></div>
</dl>

<section class="panel">
    <h2>Visites par jour</h2>
    <?= Chart::bars(array_map(fn ($d) => $d['visites'], $report['series']), 'Visites par jour', ' visite(s)') ?>
</section>

<div class="dash-grid">
    <section class="panel"><h2>Pages les plus vues</h2><?= $table('Pages les plus vues', 'Page', $report['pages']) ?></section>
    <section class="panel"><h2>Provenance</h2><?= $table('Provenance des visites', 'Source', $report['sources']) ?></section>
    <section class="panel"><h2>Appareils</h2><?= $table('Type d\'écran des visiteurs', 'Appareil', $report['appareils']) ?></section>
</div>

<details class="help">
    <summary>Comment ces chiffres sont-ils calculés ?</summary>
    <ul>
        <li>Chaque page ouverte envoie au serveur : l'adresse de la page, le site d'où vient le visiteur et la largeur de son écran.</li>
        <li>Une <strong>visite</strong> commence quand on arrive depuis un autre site, un moteur de recherche ou un lien direct. Les clics à l'intérieur du portfolio comptent comme des <strong>pages vues</strong>.</li>
        <li>Les robots (moteurs de recherche, outils de test) et les navigateurs qui demandent à ne pas être suivis ne sont pas comptés.</li>
        <li>Les données sont gardées 13 mois, dans <code>data/audience-AAAA-MM.json</code>, puis supprimées automatiquement.</li>
    </ul>
</details>
