<?php
// Fiche de la base de connaissances. Les champs facultatifs (niveau, durée…) ne s'affichent que s'ils sont remplis :
// une fiche ancienne, sans ces champs, s'affiche comme avant.
$facts = array_filter([
    'Type' => $item['categorie'] ?? '',
    'Niveau' => $item['niveau'] ?? '',
    'Durée' => $item['duree'] ?? '',
    'Système' => $item['systeme'] ?? '',
    'Outil' => $item['outil'] ?? '',
    'Versions testées' => $item['versions'] ?? '',
    'Vérifiée le' => !empty($item['verifie_le']) && ($t = strtotime($item['verifie_le'])) ? date('d/m/Y', $t) : '',
]);
$kbUrl = '/base-de-connaissances';
?>
<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url($kbUrl)) ?>">Base de connaissances</a>
    <?php if (!empty($item['domaine'])): ?>
        › <a href="<?= e(url($kbUrl . '?domaine=' . rawurlencode($item['domaine']))) ?>"><?= e($item['domaine']) ?></a>
    <?php endif; ?>
    › <span aria-current="page"><?= e($item['titre']) ?></span>
</nav>

<article class="kb-article" data-kb-slug="<?= e($item['slug']) ?>">
    <h1><?= e($item['titre']) ?></h1>

    <?php if (($item['statut'] ?? '') === 'Archive'): ?>
        <p class="callout callout-warning"><strong class="callout-label">Archive</strong> Cette fiche décrit une méthode que je n'utilise plus. Elle est gardée pour l'historique de mes réalisations.</p>
    <?php endif; ?>

    <?php if (!empty($item['resume'])): ?>
        <p class="lead"><?= e($item['resume']) ?></p>
    <?php endif; ?>

    <?php if ($facts): ?>
        <dl class="kb-facts">
            <?php foreach ($facts as $label => $value): ?>
                <div><dt><?= e($label) ?></dt><dd><?= e($value) ?></dd></div>
            <?php endforeach; ?>
        </dl>
    <?php endif; ?>

    <?php foreach ($usedIn as $u): ?>
        <aside class="kb-used-in">
            <p>Étape <strong><?= $u['position'] ?> sur <?= $u['total'] ?></strong> du parcours
                <a href="<?= e(url('/realisations/' . $u['realisation']['slug'])) ?>#reproduire">Reproduire « <?= e($u['realisation']['titre']) ?> »</a></p>
        </aside>
    <?php endforeach; ?>

    <?php if ($prerequis): ?>
        <aside class="kb-before">
            <h2>À lire avant</h2>
            <ul>
                <?php foreach ($prerequis as $p): ?>
                    <li><a href="<?= e(url($kbUrl . '/' . $p['slug'])) ?>"><?= e($p['titre']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </aside>
    <?php endif; ?>

    <?php if (count($toc) >= 3): ?>
        <nav class="kb-toc" aria-labelledby="sommaire">
            <h2 id="sommaire">Sommaire</h2>
            <ol>
                <?php foreach ($toc as $h): ?>
                    <li><a href="#<?= e($h['id']) ?>"><?= e($h['titre']) ?></a></li>
                <?php endforeach; ?>
            </ol>
        </nav>
    <?php endif; ?>

    <div class="kb-content">
        <?= Markdown::toHtml($item['contenu'] ?? '') ?>
    </div>

    <?php foreach ($usedIn as $u): ?>
        <nav class="kb-step-nav" aria-label="Étapes du parcours <?= e($u['realisation']['titre']) ?>">
            <?php if ($u['previous']): ?>
                <a class="kb-prev" href="<?= e(url($kbUrl . '/' . $u['previous']['slug'])) ?>"><span>Étape précédente</span><?= e($u['previous']['titre']) ?></a>
            <?php else: ?>
                <a class="kb-prev" href="<?= e(url('/realisations/' . $u['realisation']['slug'])) ?>#reproduire"><span>Début du parcours</span><?= e($u['realisation']['titre']) ?></a>
            <?php endif; ?>
            <?php if ($u['next']): ?>
                <a class="kb-next" href="<?= e(url($kbUrl . '/' . $u['next']['slug'])) ?>"><span>Étape suivante</span><?= e($u['next']['titre']) ?></a>
            <?php else: ?>
                <a class="kb-next" href="<?= e(url('/realisations/' . $u['realisation']['slug'])) ?>"><span>Parcours terminé</span>Retour à la réalisation</a>
            <?php endif; ?>
        </nav>
    <?php endforeach; ?>
</article>
