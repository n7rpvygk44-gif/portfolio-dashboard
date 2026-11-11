<?php
// Fiche d'un logiciel : rôle, raison du choix, réalisations qui l'utilisent, autres usages et alternatives.
$base = Contexte::URL;
$facts = array_filter([
    'Famille' => $item['categorie'] ?? '',
    'Licence' => $item['licence'] ?? '',
]);
?>
<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/realisations')) ?>">Réalisations</a>
    › <a href="<?= e(url($base)) ?>">Contexte</a>
    › <a href="<?= e(url($base)) ?>#logiciels">Logiciels</a>
    › <span aria-current="page"><?= e($item['titre']) ?></span>
</nav>

<article class="kb-article context-article">
    <h1><?= e($item['titre']) ?></h1>
    <?php if (!empty($item['role'])): ?>
        <p class="lead"><?= e($item['role']) ?></p>
    <?php endif; ?>

    <dl class="kb-facts">
        <?php foreach ($facts as $label => $value): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
        <?php endforeach; ?>
        <?php if ($itemSteps): ?>
            <div><dt><?= count($itemSteps) > 1 ? 'Étapes' : 'Étape' ?></dt><dd>
                <?php foreach ($itemSteps as $i => $s): ?><?= $i ? ', ' : '' ?><a href="<?= e(url($base . '/' . $s['slug'])) ?>"><?= Contexte::position($s['slug'], $steps) ?>. <?= e($s['titre']) ?></a><?php endforeach; ?>
            </dd></div>
        <?php endif; ?>
        <?php if (!empty($item['site'])): ?>
            <div><dt>Site officiel</dt><dd><a href="<?= e($item['site']) ?>" rel="noopener"><?= e(preg_replace('#^https?://(www\.)?|/$#', '', $item['site'])) ?></a></dd></div>
        <?php endif; ?>
    </dl>

    <?php if (!empty($item['pourquoi'])): ?>
        <h2><?= $usedIn ? 'Pourquoi je l\'utilise' : 'Pourquoi je le propose' ?></h2>
        <p><?= e($item['pourquoi']) ?></p>
    <?php endif; ?>

    <?php if ($usedIn): ?>
        <aside class="kb-used-in">
            <p>Utilisé dans
                <?php foreach ($usedIn as $i => $r): ?><?= $i ? ' et ' : '' ?><a href="<?= e(url('/realisations/' . $r['slug'])) ?>#onglet-contexte">« <?= e($r['titre']) ?> »</a><?php endforeach; ?>.</p>
        </aside>
    <?php else: ?>
        <p class="callout callout-tip"><strong class="callout-label">Pour aller plus loin</strong> Je ne l'ai pas encore utilisé dans une réalisation publiée : il est proposé pour compléter un environnement personnel.</p>
    <?php endif; ?>

    <?php if (!empty($item['autres_usages'])): ?>
        <h2>Dans quels autres contextes l'utiliser ?</h2>
        <?= Markdown::toHtml($item['autres_usages']) ?>
    <?php endif; ?>

    <?php if (!empty($item['alternatives'])): ?>
        <h2>Alternatives</h2>
        <ul class="tags">
            <?php foreach ($item['alternatives'] as $alt): ?>
                <li><?= e($alt) ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <?php if ($kb): ?>
        <aside class="kb-before">
            <h2>Fiches de la base de connaissances</h2>
            <ul>
                <?php foreach ($kb as $k): ?>
                    <li><a href="<?= e(url('/base-de-connaissances/' . $k['slug'])) ?>"><?= e($k['titre']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </aside>
    <?php endif; ?>

    <p><a class="button secondary" href="<?= e(url($base)) ?>">Toutes les étapes du contexte</a></p>
</article>
