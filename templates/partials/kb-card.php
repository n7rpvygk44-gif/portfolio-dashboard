<?php
// Carte d'une fiche de la base de connaissances (liste, parcours, résultats de recherche).
$badges = array_filter([$k['categorie'] ?? '', $k['niveau'] ?? '', $k['duree'] ?? '']);
?>
<li class="card kb-card"
    data-domaine="<?= e($k['domaine'] ?? '') ?>"
    data-categorie="<?= e($k['categorie'] ?? '') ?>"
    data-niveau="<?= e($k['niveau'] ?? '') ?>">
    <h3><a href="<?= e(url('/base-de-connaissances/' . $k['slug'])) ?>"><?= e($k['titre']) ?></a></h3>
    <?php if ($badges || !empty($k['outil']) || ($k['statut'] ?? '') === 'Archive'): ?>
        <p class="kb-badges">
            <?php foreach ($badges as $badge): ?>
                <span class="kb-badge"><?= e($badge) ?></span>
            <?php endforeach; ?>
            <?php if (!empty($k['outil'])): ?>
                <span class="kb-badge kb-badge-tool"><?= e($k['outil']) ?></span>
            <?php endif; ?>
            <?php if (($k['statut'] ?? '') === 'Archive'): ?>
                <span class="kb-badge kb-badge-archive">Archive</span>
            <?php endif; ?>
        </p>
    <?php endif; ?>
    <p><?= e($k['resume'] ?? '') ?></p>
</li>
