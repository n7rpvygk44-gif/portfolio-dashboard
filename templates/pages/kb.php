<?php
// Page d'accueil de la base de connaissances : recherche, filtres, parcours, fiches classées, index A à Z.
$labels = ['domaine' => 'Domaine', 'categorie' => 'Type de fiche', 'niveau' => 'Niveau'];
$options = [
    'domaine' => Kb::values($all, 'domaine'),
    'categorie' => Kb::values($all, 'categorie'),
    'niveau' => array_values(array_intersect(['Débutant', 'Intermédiaire', 'Avancé'], Kb::values($all, 'niveau'))),
];
$filtering = $q !== '' || array_filter($filters);

$groups = [];
foreach ($items as $item) {
    $key = $item[$groupBy] ?? '';
    $groups[$key !== '' ? $key : 'Autres fiches'][] = $item;
}
// Ordre des domaines : celui du tableau de bord ; « Autres fiches » en dernier.
$order = $groupBy === 'domaine' ? Admin::KB_DOMAINS : Admin::KB_CATEGORIES;
uksort($groups, function ($a, $b) use ($order) {
    $ia = array_search($a, $order, true);
    $ib = array_search($b, $order, true);
    return ($ia === false ? 99 : $ia) <=> ($ib === false ? 99 : $ib) ?: strcmp($a, $b);
});

$byLetter = [];
$sorted = $all;
usort($sorted, fn ($a, $b) => strcasecmp(Admin::slugify($a['titre']), Admin::slugify($b['titre'])));
foreach ($sorted as $k) {
    $byLetter[strtoupper(Admin::slugify($k['titre'])[0])][] = $k;
}
?>
<h1>Base de connaissances</h1>
<p class="lead">Procédures pas à pas, guides d'outils et notions : tout ce qu'il faut pour reproduire mes réalisations, expliqué pour un débutant.</p>

<form class="search kb-search" method="get" action="<?= e(url('/base-de-connaissances')) ?>" role="search">
    <p class="kb-search-text">
        <label for="q">Rechercher une fiche</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="ex. SSH, cron, certificat, Git…" data-kb-filter>
    </p>
    <?php foreach ($labels as $name => $label): ?>
        <?php if ($options[$name]): ?>
            <p>
                <label for="f-<?= e($name) ?>"><?= e($label) ?></label>
                <select id="f-<?= e($name) ?>" name="<?= e($name) ?>" data-kb-select="<?= e($name) ?>">
                    <option value="">Tous</option>
                    <?php foreach ($options[$name] as $value): ?>
                        <option<?= $filters[$name] === $value ? ' selected' : '' ?>><?= e($value) ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
        <?php endif; ?>
    <?php endforeach; ?>
    <p>
        <label for="f-classer">Classer par</label>
        <select id="f-classer" name="classer" data-kb-group>
            <option value="domaine">Domaine</option>
            <option value="categorie"<?= $groupBy === 'categorie' ? ' selected' : '' ?>>Type de fiche</option>
        </select>
    </p>
    <p class="kb-search-actions">
        <button type="submit">Rechercher</button>
        <?php if ($filtering): ?>
            <a href="<?= e(url('/base-de-connaissances')) ?>">Tout afficher</a>
        <?php endif; ?>
    </p>
</form>

<?php if ($paths && !$filtering): ?>
    <section class="kb-paths" aria-labelledby="parcours">
        <h2 id="parcours">Reproduire une réalisation</h2>
        <p>Chaque parcours rassemble, dans l'ordre, les fiches à suivre pour refaire un de mes projets.</p>
        <ul class="cards">
            <?php foreach ($paths as $p): ?>
                <?php $total = Kb::totalDuration($p['steps']); ?>
                <li class="card kb-path">
                    <h3><a href="<?= e(url('/realisations/' . $p['realisation']['slug'])) ?>#reproduire"><?= e($p['realisation']['titre']) ?></a></h3>
                    <p class="kb-badges">
                        <span class="kb-badge"><?= count($p['steps']) ?> étape<?= count($p['steps']) > 1 ? 's' : '' ?></span>
                        <?php if ($total !== ''): ?>
                            <span class="kb-badge">environ <?= e($total) ?></span>
                        <?php endif; ?>
                    </p>
                    <ol class="kb-path-steps">
                        <?php foreach ($p['steps'] as $s): ?>
                            <li><a href="<?= e(url('/base-de-connaissances/' . $s['slug'])) ?>"><?= e($s['titre']) ?></a></li>
                        <?php endforeach; ?>
                    </ol>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>

<?php if (!$items): ?>
    <p class="empty"><?= $filtering ? 'Aucune fiche ne correspond à cette recherche.' : 'Aucune fiche pour le moment.' ?></p>
<?php endif; ?>

<?php foreach ($groups as $group => $list): ?>
    <section class="kb-category" data-kb-section>
        <h2><?= e($group) ?></h2>
        <ul class="cards">
            <?php foreach ($list as $k): ?>
                <?= View::partial('partials/kb-card', ['k' => $k]) ?>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endforeach; ?>
<p class="empty" data-kb-empty hidden>Aucune fiche ne correspond à cette recherche.</p>

<?php if ($byLetter): ?>
    <section class="kb-index" aria-labelledby="index-a-z">
        <h2 id="index-a-z">Index de A à Z</h2>
        <p class="kb-letters">
            <?php foreach (array_keys($byLetter) as $letter): ?>
                <a href="#lettre-<?= e(strtolower((string) $letter)) ?>"><?= e($letter) ?></a>
            <?php endforeach; ?>
        </p>
        <dl>
            <?php foreach ($byLetter as $letter => $list): ?>
                <dt id="lettre-<?= e(strtolower((string) $letter)) ?>"><?= e($letter) ?></dt>
                <?php foreach ($list as $k): ?>
                    <dd><a href="<?= e(url('/base-de-connaissances/' . $k['slug'])) ?>"><?= e($k['titre']) ?></a><?= !empty($k['categorie']) ? ' <span class="meta">(' . e($k['categorie']) . ')</span>' : '' ?></dd>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </dl>
    </section>
<?php endif; ?>
