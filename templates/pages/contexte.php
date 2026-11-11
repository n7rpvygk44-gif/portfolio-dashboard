<?php
// Page Contexte : les étapes pour construire un environnement informatique fondamental, autonome et personnel,
// puis le catalogue des logiciels (ceux de mes projets, et ceux proposés pour aller plus loin).
$usedSlugs = array_unique(array_merge(...array_map(fn ($r) => $r['logiciels'] ?? [], $realisations ?: [[]])));
$used = array_values(array_filter($logiciels, fn ($l) => in_array($l['slug'], $usedSlugs, true)));
$suggested = array_values(array_filter($logiciels, fn ($l) => !in_array($l['slug'], $usedSlugs, true)));
$withContext = array_values(array_filter($realisations, fn ($r) => !empty($r['logiciels'])));
?>
<nav class="breadcrumb" aria-label="Fil d'Ariane">
    <a href="<?= e(url('/realisations')) ?>">Réalisations</a> › <span aria-current="page">Contexte</span>
</nav>

<h1>Contexte : construire son environnement informatique personnel</h1>
<p class="lead">Les logiciels de mes projets ne sont pas choisis au hasard. Cette page explique leur rôle et propose, étape par étape, comment les combiner pour créer <strong>votre propre environnement informatique</strong> : fondamental, entièrement autonome et personnel.</p>

<section aria-labelledby="definition">
    <h2 id="definition">De quoi parle-t-on ?</h2>
    <dl class="context-definition">
        <div>
            <dt>Fondamental</dt>
            <dd>Il contient les briques de base d'un système d'information : matériel, système, réseau, services, sécurité, supervision et sauvegarde.</dd>
        </div>
        <div>
            <dt>Entièrement autonome</dt>
            <dd>Il fonctionne sans dépendre d'un fournisseur : les tâches se lancent seules, les pannes sont signalées, et tout peut être reconstruit à partir de la documentation.</dd>
        </div>
        <div>
            <dt>Personnel</dt>
            <dd>Vous en gardez la maîtrise : vos données restent chez vous, et chaque service répond à un besoin que vous avez choisi.</dd>
        </div>
    </dl>
</section>

<section aria-labelledby="etapes">
    <h2 id="etapes">Les <?= count($steps) ?> étapes</h2>
    <p>Suivez-les dans l'ordre : chacune s'appuie sur la précédente. Chaque étape indique les logiciels conseillés et renvoie vers les fiches pas à pas de la <a href="<?= e(url('/base-de-connaissances')) ?>">base de connaissances</a>.</p>
    <ol class="context-steps">
        <?php foreach ($steps as $i => $s): ?>
            <li class="card context-step">
                <span class="context-step-number" aria-hidden="true"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
                <h3><a href="<?= e(url(Contexte::URL . '/' . $s['slug'])) ?>"><span class="visually-hidden">Étape <?= $i + 1 ?> : </span><?= e($s['titre']) ?></a></h3>
                <?php $badges = array_filter([$s['niveau'] ?? '', $s['duree'] ?? '']); ?>
                <?php if ($badges): ?>
                    <p class="meta"><?= e(implode(' · ', $badges)) ?></p>
                <?php endif; ?>
                <p><?= e($s['objectif'] ?? '') ?></p>
                <?php $names = Contexte::pick($s['logiciels'] ?? [], $logiciels); ?>
                <?php if ($names): ?>
                    <ul class="tags" aria-label="Logiciels de l'étape">
                        <?php foreach ($names as $l): ?>
                            <li><?= e($l['titre']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</section>

<section aria-labelledby="logiciels">
    <h2 id="logiciels">Les logiciels</h2>
    <?php foreach ([['Utilisés dans mes projets', 'Ils ont servi à mes réalisations : leur fiche explique pourquoi je les ai choisis.', $used], ['Proposés pour aller plus loin', 'Je ne les ai pas encore utilisés dans un projet publié, mais ils complètent naturellement un environnement personnel.', $suggested]] as [$label, $intro, $list]): ?>
        <?php if ($list): ?>
            <h3><?= e($label) ?></h3>
            <p><?= e($intro) ?></p>
            <ul class="cards software-index">
                <?php foreach ($list as $l): ?>
                    <li class="card">
                        <h4><a href="<?= e(url(Contexte::URL . '/logiciels/' . $l['slug'])) ?>"><?= e($l['titre']) ?></a></h4>
                        <p class="meta"><?= e($l['categorie'] ?? '') ?></p>
                        <p><?= e($l['role'] ?? '') ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>
</section>

<?php if ($withContext): ?>
    <section class="related" aria-labelledby="depuis-projets">
        <h2 id="depuis-projets">Le contexte de chaque réalisation</h2>
        <ul>
            <?php foreach ($withContext as $r): ?>
                <li><a href="<?= e(url('/realisations/' . $r['slug'])) ?>#onglet-contexte"><?= e($r['titre']) ?></a> · <?= count($r['logiciels']) ?> logiciels</li>
            <?php endforeach; ?>
        </ul>
    </section>
<?php endif; ?>
