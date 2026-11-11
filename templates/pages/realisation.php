<?php
$titles = array_column($competences, 'titre', 'code');
// Onglets de la page. Sans JavaScript, ce sont de simples liens vers les sections, toutes visibles.
// Avec JavaScript (main.js), une seule section est affichée à la fois.
$tabs = ['onglet-presentation' => 'Présentation', 'onglet-contexte' => 'Contexte'];
if ($kb) {
    $tabs['reproduire'] = 'Reproduire ce projet';
}
$nbSteps = count($steps);
?>
<p class="breadcrumb"><a href="<?= e(url('/realisations')) ?>">Réalisations</a> › <?= e($item['titre']) ?></p>

<article class="realisation">
    <h1><?= e($item['titre']) ?></h1>
    <p class="meta"><?= e(implode(' · ', array_filter([$item['contexte'] ?? '', $item['date'] ?? '']))) ?></p>
    <p class="lead"><?= e($item['resume'] ?? '') ?></p>

    <nav class="tabs" aria-label="Sections du projet" data-tabs>
        <ul>
            <?php foreach ($tabs as $id => $label): ?>
                <li><a href="#<?= e($id) ?>" id="tab-<?= e($id) ?>" data-tab="<?= e($id) ?>"><?= e($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </nav>

    <section class="tab-panel" id="onglet-presentation" aria-labelledby="titre-presentation">
        <h2 id="titre-presentation" class="visually-hidden">Présentation</h2>
        <div class="facts">
            <?php if (!empty($item['competences'])): ?>
                <div>
                    <h3>Compétences mobilisées</h3>
                    <ul>
                        <?php foreach ($item['competences'] as $code): ?>
                            <li><span class="code"><?= e($code) ?></span> <?= e($titles[$code] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <?php if (!empty($item['outils'])): ?>
                <div>
                    <h3>Outils</h3>
                    <ul class="tags">
                        <?php foreach ($item['outils'] as $tool): ?>
                            <li><?= e($tool) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="facts-more"><a href="#onglet-contexte" data-tab-link="onglet-contexte">Pourquoi ces outils ? Voir le contexte</a></p>
                </div>
            <?php endif; ?>
        </div>

        <?= Markdown::toHtml($item['contenu'] ?? '') ?>
    </section>

    <section class="tab-panel context-panel" id="onglet-contexte" aria-labelledby="titre-contexte">
        <h2 id="titre-contexte">Contexte du projet</h2>
        <p>Voici les logiciels utilisés dans ce projet, la raison de chaque choix, et comment les réutiliser pour construire <strong>votre propre environnement informatique</strong> : fondamental, entièrement autonome et personnel. Ils sont rangés selon les étapes de ce parcours.</p>

        <?php if (!$contexte): ?>
            <p class="empty">Les logiciels de ce projet ne sont pas encore décrits.</p>
        <?php endif; ?>

        <?php foreach ($contexte as $group): ?>
            <div class="context-group">
                <?php if ($group['step']): ?>
                    <?php $n = Contexte::position($group['step']['slug'], $steps); ?>
                    <h3>
                        <span class="context-step-number">Étape <?= $n ?> sur <?= $nbSteps ?></span>
                        <a href="<?= e(url(Contexte::URL . '/' . $group['step']['slug'])) ?>"><?= e($group['step']['titre']) ?></a>
                    </h3>
                <?php else: ?>
                    <h3>Autres logiciels</h3>
                <?php endif; ?>
                <ul class="cards software-cards">
                    <?php foreach ($group['items'] as $l): ?>
                        <li class="card software-card" id="logiciel-<?= e($l['slug']) ?>">
                            <h4><a href="<?= e(url(Contexte::URL . '/logiciels/' . $l['slug'])) ?>"><?= e($l['titre']) ?></a></h4>
                            <p class="meta"><?= e(implode(' · ', array_filter([$l['categorie'] ?? '', $l['licence'] ?? '']))) ?></p>
                            <dl class="software-facts">
                                <?php if (!empty($l['role'])): ?>
                                    <dt>Rôle</dt>
                                    <dd><?= e($l['role']) ?></dd>
                                <?php endif; ?>
                                <?php if (!empty($l['pourquoi'])): ?>
                                    <dt>Pourquoi ce choix</dt>
                                    <dd><?= e($l['pourquoi']) ?></dd>
                                <?php endif; ?>
                            </dl>
                            <?php if (!empty($l['autres_usages'])): ?>
                                <details class="software-more">
                                    <summary>Dans quels autres contextes l'utiliser ?</summary>
                                    <?= Markdown::toHtml($l['autres_usages']) ?>
                                </details>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endforeach; ?>

        <aside class="context-cta">
            <p><strong>Aller plus loin :</strong> la page Contexte présente les <?= $nbSteps ?> étapes pour créer un environnement informatique fondamental, entièrement autonome et personnel, avec les logiciels conseillés à chaque étape.</p>
            <p><a class="button" href="<?= e(url(Contexte::URL)) ?>">Voir la page Contexte</a></p>
        </aside>
    </section>

    <?php if ($kb): ?>
        <?php $total = Kb::totalDuration($kb); ?>
        <section class="tab-panel related kb-reproduce" id="reproduire" aria-labelledby="reproduire-titre">
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
