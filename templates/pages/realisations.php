<h1>Réalisations</h1>
<p class="lead">Mes projets et situations professionnelles, en formation, en stage ou à titre personnel.</p>
<p>Chaque réalisation a un onglet <strong>Contexte</strong> qui présente les logiciels utilisés et la raison de leur choix. Pour les réunir dans votre propre environnement, voir <a href="<?= e(url('/contexte')) ?>">Contexte : construire son environnement informatique personnel</a>.</p>

<?php if ($realisations): ?>
    <?= View::partial('partials/cards', ['items' => $realisations, 'base' => '/realisations/']) ?>
<?php else: ?>
    <p class="empty">Aucune réalisation publiée pour le moment.</p>
<?php endif; ?>
