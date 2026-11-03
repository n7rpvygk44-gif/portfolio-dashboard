<?php
// Configuration générale du site. À adapter avant la mise en ligne.
return [
    // URL publique du site, sans slash final (sert au référencement : canonical, sitemap).
    'base_url' => 'https://votre-compte.alwaysdata.net',
    'site_name' => 'Portfolio BTS SIO SISR',
    'lang' => 'fr',

    // Préfixe des liens quand le site n'est pas à la racine du domaine (ex. '/Portefolio').
    'base_path' => '',

    // Backoffice et tableau de bord : false les désactive complètement (/admin renvoie une page introuvable).
    // Ils ont besoin de PHP : ils fonctionnent en local et sur AlwaysData, pas sur GitHub Pages.
    'admin_enabled' => true,

    // Réglages utilisés pour générer la version statique (bin/build-static.php, GitHub Pages).
    'static' => [
        'base_url' => 'https://n7rpvygk44-gif.github.io/Portefolio',
        'base_path' => '/Portefolio',
    ],

    // Mesure d'audience maison : sans cookie, sans adresse IP, données gardées en JSON sur le serveur.
    // Désactivée automatiquement dans la version statique (GitHub Pages n'exécute pas de PHP).
    'audience_enabled' => true,
    'audience_keep_months' => 13,     // durée de conservation recommandée par la CNIL

    // Supervision : site à surveiller et dépôt GitHub d'où vient la dernière publication.
    // Les vérifications sont faites par bin/monitor.php (tâche planifiée toutes les 15 minutes sur AlwaysData).
    'monitor' => [
        'url' => 'https://n7rpvygk44-gif.github.io/Portefolio/',
        'github_repo' => 'n7rpvygk44-gif/Portefolio',
        'github_workflow' => 'pages.yml',  // workflow de publication ; null si le site n'en a pas
        'timeout' => 10,                  // secondes avant de considérer le site comme injoignable
        'slow_ms' => 1500,                // au-delà, le site est signalé comme lent
        'keep_days' => 30,                // historique conservé
    ],

    // Dossier des données JSON (hors de public/, donc inaccessible depuis le web).
    'data_dir' => __DIR__ . '/data',

    // Sécurité du backoffice.
    'login_max_attempts' => 5,        // tentatives avant blocage
    'login_lock_seconds' => 15 * 60,  // durée du blocage
    'session_lifetime' => 30 * 60,    // déconnexion après 30 min d'inactivité
];
