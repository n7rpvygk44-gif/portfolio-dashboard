# Portfolio BTS SIO SISR, version avec tableau de bord

Copie indépendante du dépôt [Portefolio](https://github.com/n7rpvygk44-gif/Portefolio), qui ajoute un **tableau de bord** sur `/admin` : gestion du contenu, audience et supervision. Le dépôt d'origine et le site publié sur GitHub Pages ne sont pas concernés.

Site portfolio pour l'épreuve E5 du BTS SIO option SISR : présentation, parcours, compétences du bloc 1, réalisations, tableau de synthèse, veille technologique, base de connaissances et contact.

- **PHP natif** (8.1 ou plus), sans framework ni base de données
- **Données en JSON** dans `data/`, hors de la partie publique
- **Tableau de bord sécurisé** sur `/admin` : contenu, audience, supervision (voir plus bas)
- **Référencement** : URL propres, balises meta et Open Graph, `sitemap.xml` et `robots.txt` générés
- **Accessibilité** : panneau de réglages (texte agrandi, contraste, lecture facilitée, animations), navigation au clavier
- **Thèmes interchangeables**, choisis depuis le backoffice
- **Ambiance sonore** (`public/assets/js/sound.js`) : sons synthétisés en direct (Web Audio API, aucun fichier audio), dans l'esprit des tableaux de bord épurés en verre et aluminium. Rituel de démarrage à l'activation, bande-son synchronisée avec l'introduction du Radiant, nappe d'ambiance, déclics et effleurements sur les liens et boutons, tintements quand le thème Fondation déploie l'interface. Coupé par défaut ; bouton rond en bas à gauche, choix mémorisé dans le navigateur.

## Organisation des fichiers

```
config.php                 configuration (adresse du site, sécurité)
public/                    racine web : seul dossier accessible depuis Internet
  index.php                contrôleur frontal (toutes les adresses passent par lui)
  .htaccess                URL propres (Apache)
  assets/css/style.css     style de base
  assets/css/themes/       un fichier CSS par thème
  assets/js/main.js        menu, accessibilité, filtre de la base de connaissances
  assets/js/themes/        script optionnel d'un thème
src/                       logique PHP (données JSON, authentification, SEO, Markdown, thèmes)
templates/                 gabarits HTML (pages publiques et backoffice)
data/                      contenus en JSON (profil, réalisations, veille, base de connaissances)
bin/create-admin.php       création du compte administrateur
bin/router.php             routeur pour tester en local
bin/build-static.php       génération de la version statique (dist/)
bin/monitor.php            vérification du site, à lancer en tâche planifiée
src/Audience.php           mesure d'audience (compteurs JSON, sans cookie)
src/Monitor.php            supervision (disponibilité, certificat HTTPS, publication)
src/Chart.php              graphiques SVG du tableau de bord
public/assets/js/audience.js  envoi d'une page vue au serveur
```

## Tester en local

```
php -S localhost:8000 -t public bin/router.php
```

Puis ouvrir http://localhost:8000. Pour le tableau de bord : lancer une fois `php bin/create-admin.php` (choix de l'identifiant et du mot de passe), puis ouvrir http://localhost:8000/admin. Pour remplir la supervision : `php bin/monitor.php`.

## Version statique (GitHub Pages) : dépôt d'origine seulement

> Dans ce dépôt `portfolio-dashboard`, le workflow de publication sur GitHub Pages a été retiré : le tableau de bord a besoin de PHP et se publie sur AlwaysData. La section ci-dessous décrit le fonctionnement du dépôt d'origine `Portefolio`.

Pour l'instant le site est publié **sans PHP** sur GitHub Pages : https://n7rpvygk44-gif.github.io/Portefolio/

- `php bin/build-static.php` génère toutes les pages en HTML dans `dist/` (avec `sitemap.xml` et `robots.txt`).
- La publication se lance **à la main** : sur GitHub, onglet **Actions › Publier sur GitHub Pages › Run workflow** (workflow `.github/workflows/pages.yml`). Un push seul ne change pas le site en ligne.
- Le contenu se modifie dans les fichiers `data/*.json`, puis on pousse sur GitHub et on publie quand c'est prêt.
- La recherche de la base de connaissances fonctionne en JavaScript.
- Le **backoffice est désactivé** (`'admin_enabled' => false` dans `config.php`) : son code est conservé, mais `/admin` renvoie une page introuvable et il n'est pas inclus dans la version statique.

Adresse et préfixe de la version statique : section `static` de `config.php`.

## Tableau de bord

| Rubrique | Ce qu'elle montre | D'où viennent les données |
|---|---|---|
| **Tableau de bord** | Résumé des trois rubriques | — |
| **Contenu** | Profil, réalisations, base de connaissances, veille | `data/*.json`, modifiés par les formulaires |
| **Audience** | Visites, pages vues, pages les plus lues, provenance, type d'écran (7 jours à 12 mois) | `data/audience-AAAA-MM.json` |
| **Supervision** | État du site, disponibilité (24 h, 7 j, 30 j), temps de réponse, certificat HTTPS, dernière publication, incidents | `data/monitoring.json` |

**Audience, conforme au RGPD sans bandeau de consentement** : `audience.js` envoie à `/api/visite` la page, le site d'origine et la largeur d'écran. Le serveur n'ajoute que des compteurs par jour : pas de cookie, pas d'identifiant, pas d'adresse IP. Les robots et les navigateurs « Do Not Track » ou « Global Privacy Control » ne sont pas comptés. Conservation 13 mois (`audience_keep_months`). L'audience a besoin de PHP : elle est absente de la version GitHub Pages.

**Supervision** : `php bin/monitor.php` ouvre le site (code HTTP, temps de réponse), lit le certificat HTTPS (toutes les 6 h) et demande à l'API publique de GitHub la date de la dernière publication (toutes les heures). Le bouton « Vérifier maintenant » fait la même chose. Réglages dans la section `monitor` de `config.php`.

## Mettre en ligne sur AlwaysData


1. Créer un compte gratuit sur alwaysdata.com.
2. **Web › Sites** : type PHP, racine du site = `www/public`.
3. Se connecter en SSH et cloner le dépôt dans `www` : `git clone https://github.com/<compte>/<depot>.git www`
4. `php www/bin/create-admin.php` pour créer le compte du backoffice.
5. Renseigner `base_url` dans `config.php`, puis décommenter la redirection HTTPS dans `public/.htaccess`.

6. **Avancé › Tâches planifiées** : ajouter la commande `php ~/www/bin/monitor.php`, toutes les 15 minutes.
7. Adresse surveillée : `monitor.url` dans `config.php` (ici le site AlwaysData lui-même). Quand elle change, l'historique de supervision repart de zéro.

Pour une mise à jour du code : `cd www && git pull`.

Les contenus saisis dans le backoffice sont écrits dans `data/` sur le serveur. Pensez à les récupérer régulièrement (SFTP) et à les committer, pour les sauvegarder et garder le dépôt à jour.

## Base de connaissances

Fiches pas à pas pour reproduire les réalisations (`data/kb.json`, modifiables dans **Contenu › Base de connaissances**).

- **Types de fiche** : Procédure, Guide, Documentation, Mémo, Notion, Glossaire. **Domaines** : hébergement, systèmes, réseau, sécurité, supervision, outils.
- **Champs facultatifs** : niveau, durée, système, versions testées, date de dernière vérification, fiches à lire avant, statut (Archive). Une fiche sans ces champs s'affiche comme avant.
- **Parcours** : le champ « Parcours » d'une réalisation liste les fiches dans l'ordre. La réalisation affiche « Reproduire ce projet », chaque fiche affiche son étape et des liens précédent / suivant.
- **Gabarit d'une procédure** : `## Objectif`, `## Prérequis`, `## Étapes` avec des `### Étape 1 : …` (case « fait » mémorisée dans le navigateur), `## Vérification`, `## Dépannage` (tableau symptôme / cause / solution), `## Sécurité et bonnes pratiques`, `## Pour aller plus loin`.
- **Markdown** (`src/Markdown.php`) : blocs ```` ```bash ```` avec bouton Copier, tableaux `| a | b |`, images `![légende](/assets/img/…)`, encadrés `> Attention :`, `> Astuce :`, `> Remarque :`, `> Résultat attendu :`.
- **Recherche et filtres** : texte, domaine, type, niveau (côté serveur, et instantané en JavaScript), index de A à Z.
- **Référencement** : données structurées TechArticle, BreadcrumbList et HowTo (`src/Kb.php`), date `lastmod` du sitemap tirée de la dernière vérification.

## Sécurité du backoffice

- Mot de passe haché avec `password_hash` (jamais stocké en clair), fichier `data/admin.json` exclu de Git
- Blocage après 5 échecs de connexion pendant 15 minutes
- Jeton CSRF sur tous les formulaires, actions de modification en POST uniquement
- Cookie de session `HttpOnly`, `SameSite=Strict`, `Secure` en HTTPS, nouvel identifiant à la connexion, expiration après 30 minutes d'inactivité
- Échappement de tout le contenu affiché (protection XSS), Markdown sans HTML brut
- En-têtes de sécurité (Content-Security-Policy, X-Frame-Options, nosniff), `data/` inaccessible depuis le web

## Thèmes

Un thème est un fichier `public/assets/css/themes/<nom>.css` qui surcharge `style.css` (et, en option, `public/assets/js/themes/<nom>.js`). Il apparaît automatiquement dans **Backoffice › Apparence**, où l'on peut le prévisualiser (visible par l'administrateur seul) avant de le publier.

Thèmes fournis :
- `defaut` : sobre, clair ou sombre
- `codec` : univers Metal Gear Solid (écran de codec, HUD) et Matrix (vert phosphore, pluie de caractères). La pluie se coupe avec « Réduire les animations » ou le contraste renforcé.
- `seldon` : `codec` + univers de *Fondation* d'Asimov (ciel galactique, Radiant de Hari Seldon, Terminus, Encyclopédie Galactique, symboles de psychohistoire dans la pluie).
- `fondation` (actif) : thème clair inspiré de l'esthétique de la série *Fondation* (Apple TV+) : ivoire, or et encre, titres en capitales espacées, Trantor seule dans le ciel. Le **Radiant Prime** (icosaèdre de verre ambré à treillis doré et cœur lumineux, dessiné en 3D dans un canvas, d'où se déploient des sphères d'équations) ouvre la visite par une introduction de 12 secondes (puis une version de 3 secondes à chaque changement de page) : le visiteur choisit « Activer le Radiant » (avec la bande-son) ou « Entrer sans le son », et peut la passer (bouton ou Échap), puis « projette » les éléments de l'interface qui se déploient à l'écran ; sur l'accueil, les 6 compétences jaillissent de son cœur. Sans JavaScript ou avec « Réduire les animations », tout s'affiche directement. Illustrations originales (aucun visuel de la série).
- `fondation-technique` : la version précédente, claire et technique (grille de plan, HUD, planètes, vaisseau de Seldon).
- `fondation-classique` : la version claire précédente (titres à empattements), conservée pour comparer.
- `fondation-nuit` : l'ancienne version sombre (espace profond, pluie d'équations).

En version statique, le thème actif se choisit dans `data/settings.json`.
