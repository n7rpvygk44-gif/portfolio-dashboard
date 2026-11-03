<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title) ?> | Backoffice</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="/assets/js/admin.js" defer></script>
</head>
<body class="admin">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="/admin">Backoffice</a>
        <?php if (!empty($_SESSION['admin'])): ?>
            <nav class="main-nav" aria-label="Menu du backoffice">
                <ul>
                    <?php foreach (['/admin' => 'Tableau de bord', '/admin/contenu' => 'Contenu', '/admin/audience' => 'Audience', '/admin/supervision' => 'Supervision', '/admin/apparence' => 'Apparence'] as $href => $label): ?>
                        <li><a href="<?= e($href) ?>"<?= $section === $href ? ' aria-current="page"' : '' ?>><?= e($label) ?></a></li>
                    <?php endforeach; ?>
                    <li><a href="/" target="_blank" rel="noopener">Voir le site</a></li>
                    <li>
                        <form method="post" action="/admin/logout">
                            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                            <button type="submit" class="link">Se déconnecter</button>
                        </form>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</header>
<main id="contenu" class="container">
    <?= $content ?>
</main>
</body>
</html>
