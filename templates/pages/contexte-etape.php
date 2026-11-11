<?php
// Une étape de la page Contexte : objectif, marche à suivre, logiciels conseillés, fiches et réalisations liées.
$facts = array_filter([
    'Étape' => $number . ' sur ' . $total,
    'Niveau' => $item['niveau'] ?? '',
    'Durée' => $item['duree'] ?? '',
]);
$base = Contexte::URL;
?>
<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/realisations')) ?>">Réalisations</a>
    › <a href="<?= e(url($base)) ?>">Contexte</a>
    › <span aria-current="page">Étape <?= $number ?></span>
</nav>

<article class="kb-article context-article">
    <h1><?= e($item['titre']) ?></h1>
    <?php if (!empty($item['objectif'])): ?>
        <p class="lead"><?= e($item['objectif']) ?></p>
    <?php endif; ?>

    <dl class="kb-facts">
        <?php foreach ($facts as $label => $value): ?>
            <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
        <?php endforeach; ?>
    </dl>

    <?php if (count($toc) >= 3): ?>
        <nav class="kb-toc" aria-labelledby="sommaire">
            <h2 id="sommaire">Sommaire</h2>
            <ol>
                <?php foreach ($toc as $h): ?>
                    <li><a href="#<?= e($h['id']) ?>"><?= e($h['titre']) ?></a></li>
                <?php endforeach; ?>
                <?php if ($logiciels): ?>
                    <li><a href="#logiciels-etape">Logiciels conseillés</a></li>
                <?php endif; ?>
            </ol>
        </nav>
    <?php endif; ?>

    <div class="kb-content">
        <?= Markdown::toHtml($item['contenu'] ?? '') ?>
    </div>

    <?php if ($logiciels): ?>
        <section aria-labelledby="logiciels-etape">
            <h2 id="logiciels-etape">Logiciels conseillés</h2>
            <ul class="cards software-index">
                <?php foreach ($logiciels as $l): ?>
                    <li class="card">
                        <h3><a href="<?= e(url($base . '/logiciels/' . $l['slug'])) ?>"><?= e($l['titre']) ?></a></h3>
                        <p class="meta"><?= e(implode(' · ', array_filter([$l['categorie'] ?? '', $l['licence'] ?? '']))) ?></p>
                        <p><?= e($l['role'] ?? '') ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>

    <?php if ($kb): ?>
        <aside class="kb-before">
            <h2>Fiches pas à pas</h2>
            <ul>
                <?php foreach ($kb as $k): ?>
                    <li><a href="<?= e(url('/base-de-connaissances/' . $k['slug'])) ?>"><?= e($k['titre']) ?></a>
                        <?php $badges = array_filter([$k['categorie'] ?? '', $k['niveau'] ?? '', $k['duree'] ?? '']); ?>
                        <?php if ($badges): ?><span class="meta">· <?= e(implode(' · ', $badges)) ?></span><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </aside>
    <?php endif; ?>

    <?php if ($realisations): ?>
        <aside class="kb-used-in">
            <p>Mis en pratique dans
                <?php foreach ($realisations as $i => $r): ?><?= $i ? ' et ' : '' ?><a href="<?= e(url('/realisations/' . $r['slug'])) ?>#onglet-contexte">« <?= e($r['titre']) ?> »</a><?php endforeach; ?>.</p>
        </aside>
    <?php endif; ?>

    <nav class="kb-step-nav" aria-label="Étapes du contexte">
        <?php if ($previous): ?>
            <a class="kb-prev" href="<?= e(url($base . '/' . $previous['slug'])) ?>"><span>Étape précédente</span><?= e($previous['titre']) ?></a>
        <?php else: ?>
            <a class="kb-prev" href="<?= e(url($base)) ?>"><span>Début du parcours</span>Toutes les étapes</a>
        <?php endif; ?>
        <?php if ($next): ?>
            <a class="kb-next" href="<?= e(url($base . '/' . $next['slug'])) ?>"><span>Étape suivante</span><?= e($next['titre']) ?></a>
        <?php else: ?>
            <a class="kb-next" href="<?= e(url($base)) ?>"><span>Parcours terminé</span>Retour au contexte</a>
        <?php endif; ?>
    </nav>
</article>
