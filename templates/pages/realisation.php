<?php
$titles = array_column($competences, 'titre', 'code');
?>
<p class="breadcrumb"><a href="<?= e(url('/realisations')) ?>">Réalisations</a> › <?= e($item['titre']) ?></p>

<article>
    <h1><?= e($item['titre']) ?></h1>
    <p class="meta"><?= e(implode(' · ', array_filter([$item['contexte'] ?? '', $item['date'] ?? '']))) ?></p>
    <p class="lead"><?= e($item['resume'] ?? '') ?></p>

    <div class="facts">
        <?php if (!empty($item['competences'])): ?>
            <div>
                <h2>Compétences mobilisées</h2>
                <ul>
                    <?php foreach ($item['competences'] as $code): ?>
                        <li><span class="code"><?= e($code) ?></span> <?= e($titles[$code] ?? '') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if (!empty($item['outils'])): ?>
            <div>
                <h2>Outils</h2>
                <ul class="tags">
                    <?php foreach ($item['outils'] as $tool): ?>
                        <li><?= e($tool) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>

    <?= Markdown::toHtml($item['contenu'] ?? '') ?>

    <?php if ($kb): ?>
        <?php $total = Kb::totalDuration($kb); ?>
        <section class="related kb-reproduce" id="reproduire" aria-labelledby="reproduire-titre">
            <h2 id="reproduire-titre">Reproduire ce projet</h2>
            <p>Suivez ces fiches de la base de connaissances dans l'ordre<?= $total !== '' ? ' (environ ' . e($total) . ' au total)' : '' ?>. Chacune détaille les commandes, le résultat attendu et les solutions aux erreurs courantes.</p>
            <ol class="kb-path-steps">
                <?php foreach ($kb as $k): ?>
                    <li>
                        <a href="<?= e(url('/base-de-connaissances/' . $k['slug'])) ?>"><?= e($k['titre']) ?></a>
                        <?php $badges = array_filter([$k['categorie'] ?? '', $k['niveau'] ?? '', $k['duree'] ?? '']); ?>
                        <?php if ($badges): ?>
                            <span class="meta">· <?= e(implode(' · ', $badges)) ?></span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>
</article>
