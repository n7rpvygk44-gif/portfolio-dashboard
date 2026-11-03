<h1>Contenu</h1>
<p class="lead">Modifier le site sans toucher au code. Chaque enregistrement met à jour le fichier JSON correspondant dans <code>data/</code>.</p>

<ul class="cards">
    <li class="card">
        <h2><a href="/admin/profil">Profil</a></h2>
        <p>Nom, accroche, parcours, liens de contact, sujet de veille.</p>
    </li>
    <?php foreach ($collections as $key => $c): ?>
        <li class="card">
            <h2><a href="/admin/<?= e($key) ?>"><?= e($c['label']) ?></a></h2>
            <p><?= $counts[$key] ?> élément(s)</p>
            <p><a href="/admin/<?= e($key) ?>/edit">+ Ajouter</a></p>
        </li>
    <?php endforeach; ?>
</ul>
