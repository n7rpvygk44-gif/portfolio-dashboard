<?php
// Configuration générale du site. À adapter avant la mise en ligne.
return [
    // Adresse OFFICIELLE du site, sans slash final : site de l'école (hébergé chez Infomaniak).
    // Toutes les copies (alwaysdata, GitHub Pages) indiquent cette adresse aux moteurs de recherche
    // (balise canonical, og:url, sitemap.xml) : Google ne voit qu'un seul site et pas des doublons.
    'canonical_url' => 'https://olivier.pizelle.mds-nantes.fr',

    // Adresse de cette copie du site (affichée par bin/build-static.php).
    'base_url' => 'https://olivier.pizelle.mds-nantes.fr',
    'site_name' => 'Portfolio BTS SIO SISR',
    'lang' => 'fr',

    // Hébergeur affiché dans les mentions légales (obligatoire) : celui du site officiel.
    'host' => "Infomaniak Network SA, rue Eugène-Marziano 25, 1227 Les Acacias (Genève), Suisse (https://www.infomaniak.com)",

    // Réglages propres à chaque copie du site, choisis selon le nom de domaine visité.
    // Seuls les domaines de cette liste sont reconnus ; les autres gardent les réglages ci-dessus.
    'hosts' => [
        'olivierpizelle.alwaysdata.net' => [
            'base_url' => 'https://olivierpizelle.alwaysdata.net',
            'host' => "alwaysdata, 91 rue du Faubourg Saint-Honoré, 75008 Paris, France (https://www.alwaysdata.com)",
        ],
    ],

    // Préfixe des liens quand le site n'est pas à la racine du domaine (ex. '/Portefolio').
    'base_path' => '',

    // Backoffice et tableau de bord : false les désactive complètement (/admin renvoie une page introuvable).
    // Ils ont besoin de PHP : ils fonctionnent en local et sur AlwaysData, pas sur GitHub Pages.
    'admin_enabled' => true,

    // Réglages utilisés pour générer la version statique (bin/build-static.php, GitHub Pages).
    'static' => [
        'base_url' => 'https://n7rpvygk44-gif.github.io/Portefolio',
        'base_path' => '/Portefolio',
        'host' => '',   // vide : les mentions légales affichent GitHub Pages
    ],

    // Mesure d'audience maison : sans cookie, sans adresse IP, données gardées en JSON sur le serveur.
    // Désactivée automatiquement dans la version statique (GitHub Pages n'exécute pas de PHP).
    'audience_enabled' => true,
    'audience_keep_months' => 13,     // durée de conservation recommandée par la CNIL

    // Supervision : site à surveiller et dépôt GitHub d'où vient la dernière publication.
    // Les vérifications sont faites par bin/monitor.php (tâche planifiée toutes les 15 minutes sur AlwaysData,
    // qui surveille donc le site officiel de l'école).
    'monitor' => [
        'url' => 'https://olivier.pizelle.mds-nantes.fr/',   // le site officiel
        'github_repo' => 'n7rpvygk44-gif/portfolio-dashboard',
        'github_workflow' => null,         // workflow de publication GitHub Actions ; null : mise à jour par git pull
        'timeout' => 10,                  // secondes avant de considérer le site comme injoignable
        'slow_ms' => 1500,                // au-delà, le site est signalé comme lent
        'keep_days' => 30,                // historique conservé
    ],

    // Dossier des données JSON (hors de public/, donc inaccessible depuis le web).
    'data_dir' => __DIR__ . '/data',

    // HTTPS : durée (en secondes) pendant laquelle le navigateur doit rester en HTTPS (en-tête HSTS).
    // 31536000 = 1 an. Mettre 0 pour ne plus envoyer l'en-tête. Explications : docs/https.md.
    'hsts_max_age' => 31536000,

    // Sécurité du backoffice.
    'login_max_attempts' => 5,        // tentatives avant blocage
    'login_lock_seconds' => 15 * 60,  // durée du blocage
    'session_lifetime' => 30 * 60,    // déconnexion après 30 min d'inactivité
];
