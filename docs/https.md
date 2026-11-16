# Forcer le HTTPS sur le portfolio

> Documentation écrite pour un débutant complet. Elle explique ce qu'est HTTPS, ce qui a été modifié dans le site, ligne par ligne, comment le mettre en ligne et comment vérifier que tout marche.
> Site concerné : https://olivierpizelle.alwaysdata.net (hébergé chez alwaysdata, dépôt GitHub `portfolio-dashboard`).

## 1. En bref

- **Le problème** : quand on tapait `olivierpizelle.alwaysdata.net` dans le navigateur, le site s'ouvrait en `http://` (non chiffré), alors qu'il possède déjà un certificat HTTPS.
- **La cause** : rien n'obligeait le navigateur à passer en HTTPS. Le certificat existait, mais personne ne disait « va plutôt à l'adresse sécurisée ».
- **La solution** (deux ajouts) :
  1. une **redirection** : toute visite en `http://` est renvoyée automatiquement vers la même page en `https://` ;
  2. un **en-tête HSTS** : le site dit au navigateur « pendant un an, ne viens chez moi qu'en HTTPS », même si on tape `http://`.
- **Fichiers modifiés** : `public/.htaccess`, `src/bootstrap.php`, `config.php`.

## 2. Les notions à connaître

### HTTP

**HTTP** (HyperText Transfer Protocol) est la « langue » que parlent le navigateur et le serveur web. Le navigateur envoie une **requête** (« donne-moi la page /contact »), le serveur répond (« voici la page », avec un **code** : 200 = OK, 404 = introuvable, 301 = « c'est déménagé ailleurs »…).

En HTTP, tout circule **en clair** : n'importe qui placé sur le chemin (Wi-Fi public, box, fournisseur d'accès) peut lire ou modifier ce qui passe, y compris un mot de passe tapé sur `/admin`.

### HTTPS et TLS

**HTTPS** = HTTP + **TLS** (Transport Layer Security). TLS crée un tunnel chiffré entre le navigateur et le serveur avant d'envoyer la moindre page. Il apporte trois garanties :

| Garantie | Ce que ça veut dire |
|---|---|
| **Confidentialité** | Personne ne peut lire ce qui passe (pages, mot de passe, cookie de session). |
| **Intégrité** | Personne ne peut modifier la page en chemin (par exemple y injecter une publicité ou un virus). |
| **Authentification** | Le navigateur a la preuve qu'il parle bien au vrai serveur `olivierpizelle.alwaysdata.net`. |

Le port utilisé change aussi : **80** pour HTTP, **443** pour HTTPS.

### Le certificat

Pour prouver son identité, le serveur présente un **certificat** : une sorte de carte d'identité numérique qui dit « ce serveur est bien `olivierpizelle.alwaysdata.net` », signée par une **autorité de certification** (AC) en qui le navigateur a confiance. Un certificat a une **date d'expiration** et doit être renouvelé.

Pour une adresse en `.alwaysdata.net`, **alwaysdata fournit et renouvelle le certificat tout seul** : il n'y a rien à faire. La page **Supervision** du tableau de bord (`/admin`) affiche d'ailleurs l'émetteur du certificat et le nombre de jours avant son expiration.

### La redirection 301

Une **redirection** est une réponse du serveur qui dit « la page n'est pas ici, va à cette autre adresse ». Le code **301** signifie « déménagement **permanent** » : le navigateur et les moteurs de recherche (Google) retiennent la nouvelle adresse. C'est le bon choix pour le référencement : Google n'indexe qu'une seule version du site, celle en HTTPS.

### HSTS

La redirection a un petit défaut : la **toute première** requête part quand même en HTTP, en clair, avant d'être redirigée. Un attaquant sur le même Wi-Fi pourrait intercepter ce premier échange (attaque dite « SSL stripping »).

**HSTS** (HTTP Strict Transport Security) corrige ça. C'est un **en-tête** (une ligne d'information ajoutée à la réponse du serveur) :

```
Strict-Transport-Security: max-age=31536000
```

Il veut dire : « navigateur, pendant 31 536 000 secondes (= 1 an), pour ce site, utilise **uniquement** HTTPS ». Après une première visite en HTTPS, le navigateur transforme lui-même tout `http://` en `https://`, sans même interroger le serveur en clair.

### Le proxy d'alwaysdata

Chez alwaysdata, le visiteur ne parle pas directement à Apache (le logiciel qui exécute le site). Il parle d'abord à un **proxy** (un serveur intermédiaire) qui gère le chiffrement TLS, puis transmet la requête à Apache.

```
Navigateur ──HTTPS chiffré──▶ Proxy alwaysdata ──▶ Apache + PHP (le site)
                                     │
                    ajoute « X-Forwarded-Proto: https »
```

Conséquence importante : Apache peut voir arriver une requête « en clair » même quand le visiteur est bien en HTTPS. Pour savoir ce qu'utilise vraiment le visiteur, il faut aussi lire l'en-tête **`X-Forwarded-Proto`** que le proxy ajoute (`https` ou `http`). Si on l'oubliait, le site croirait que tout le monde est en HTTP, redirigerait vers HTTPS… qui arriverait encore « en clair » chez Apache… qui redirigerait encore : c'est une **boucle de redirection** (le navigateur affiche « ERR_TOO_MANY_REDIRECTS »). Notre code vérifie donc les deux indices.

## 3. Ce qui a été modifié, ligne par ligne

### 3.1 `public/.htaccess` : la redirection

Le fichier `.htaccess` contient des règles lues par Apache à chaque visite. Le module **mod_rewrite** permet de réécrire ou rediriger des adresses.

```apache
RewriteEngine On
```
Active le moteur de réécriture d'adresses (il l'était déjà, pour les URL propres).

```apache
RewriteCond %{HTTPS} !=on
```
**Condition 1** : la variable `HTTPS` d'Apache n'est **pas** égale à `on` (donc Apache ne sait pas que la connexion est chiffrée). `!=` veut dire « n'est pas égal à ».

```apache
RewriteCond %{HTTP:X-Forwarded-Proto} !=https
```
**Condition 2** : l'en-tête `X-Forwarded-Proto` envoyé par le proxy n'est **pas** égal à `https`. Deux conditions à la suite doivent être vraies **toutes les deux** (c'est un « ET »). On ne redirige donc que si **aucun** indice ne dit HTTPS : impossible de tomber dans une boucle.

```apache
RewriteRule ^ https://%{HTTP_HOST}%{REQUEST_URI} [R=301,L]
```
**La règle** :
- `^` : s'applique à toutes les adresses (le symbole veut dire « début du texte », présent partout) ;
- `https://%{HTTP_HOST}%{REQUEST_URI}` : la nouvelle adresse, construite avec le nom du site (`HTTP_HOST`, ici `olivierpizelle.alwaysdata.net`) et le chemin demandé avec ses paramètres (`REQUEST_URI`, par exemple `/base-de-connaissances?q=ssh`). On garde donc exactement la même page ;
- `R=301` : c'est une **R**edirection permanente ;
- `L` : **L**ast, c'est la dernière règle à appliquer, Apache s'arrête là.

Cette règle est placée **avant** celle des URL propres (`index.php`), pour que la redirection passe en premier.

Avant, le fichier contenait déjà deux lignes de redirection, mais **en commentaire** (précédées de `#`, donc ignorées), et sans la vérification de `X-Forwarded-Proto` : les activer telles quelles aurait pu créer une boucle chez alwaysdata.

### 3.2 `src/bootstrap.php` : l'en-tête HSTS

`bootstrap.php` est exécuté au début de chaque page PHP. Il envoyait déjà des en-têtes de sécurité (`X-Frame-Options`, `Content-Security-Policy`…). On y ajoute HSTS :

```php
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
```
Calcule si la visite est en HTTPS, avec les **mêmes deux indices** que le `.htaccess`. (`||` veut dire « OU » : l'un des deux suffit.) Cette ligne existait déjà, elle servait au cookie de session ; elle est simplement remontée pour servir aussi à HSTS.

```php
if ($https && !$config['static_build'] && (int) ($config['hsts_max_age'] ?? 0) > 0) {
    header('Strict-Transport-Security: max-age=' . (int) $config['hsts_max_age']);
}
```
Envoie l'en-tête HSTS **seulement si** :
- la visite est en HTTPS (en HTTP, les navigateurs ignorent HSTS, c'est la règle du protocole) ;
- on n'est pas en train de générer la version statique pour GitHub Pages ;
- la durée réglée dans `config.php` est supérieure à 0.

`header(...)` est la fonction PHP qui ajoute un en-tête à la réponse.

### 3.3 `config.php` : le réglage

```php
'hsts_max_age' => 31536000,
```
La durée HSTS, en secondes (1 an, valeur recommandée). Mettre `0` coupe l'envoi de l'en-tête.

Volontairement, on n'a **pas** ajouté :
- `includeSubDomains` (« applique aussi aux sous-domaines ») : inutile, le site n'a pas de sous-domaine ;
- `preload` (inscription dans la liste HSTS intégrée aux navigateurs) : impossible pour une adresse en `.alwaysdata.net`, qui appartient à alwaysdata.

### 3.4 Ce qui ne change pas

| Élément | Pourquoi il n'est pas touché |
|---|---|
| **`/admin`** | Il passe par les mêmes règles : en HTTP il est redirigé vers HTTPS **avant** d'afficher le formulaire de connexion, donc le mot de passe ne circule jamais en clair. Le cookie de session était déjà marqué `secure` en HTTPS. |
| **Supervision (`bin/monitor.php`, toutes les 15 min)** | Elle surveille déjà l'adresse `https://` (section `monitor` de `config.php`) : elle n'est jamais redirigée. Elle lit aussi le certificat, qui ne change pas. |
| **Fichiers CSS, JS, images** | Redirigés eux aussi s'ils sont demandés en HTTP, mais les pages les appellent avec des adresses relatives, donc déjà en HTTPS. |
| **Test en local** (`php -S localhost:8000 …`) | Le serveur intégré de PHP ne lit pas le `.htaccess` : pas de redirection, et pas de HSTS puisqu'on est en HTTP. Rien ne change pour travailler sur le Mac. |
| **Version GitHub Pages** | GitHub Pages n'utilise ni Apache ni PHP. |

## 4. L'autre méthode : l'option « Forcer HTTPS » d'alwaysdata

alwaysdata propose aussi une case à cocher qui fait la redirection **au niveau du proxy**, sans toucher au code :

1. Se connecter à l'administration alwaysdata (admin.alwaysdata.com).
2. Aller dans **Web › Sites**.
3. Cliquer sur la roue crantée (modifier) du site.
4. Dans la section **SSL**, cocher **Forcer HTTPS** (l'intitulé exact peut légèrement varier), puis enregistrer.

**Pourquoi on a quand même choisi le code ?**

| | Case alwaysdata | `.htaccess` + HSTS dans le code |
|---|---|---|
| Redirection HTTP → HTTPS | Oui | Oui |
| En-tête HSTS | Non | Oui |
| Visible et versionné dans GitHub | Non | Oui (preuve pour l'E5) |
| Suit le site si on change d'hébergeur Apache | Non | Oui |

Les deux peuvent être activées **en même temps** sans problème : si la case est cochée, le proxy redirige en premier et la règle du `.htaccess` ne se déclenche simplement jamais. La cocher en plus est une bonne sécurité.

## 5. Tests réalisés avant la mise en ligne

Un serveur Apache a été installé dans un environnement de test, avec les mêmes fichiers. On a simulé les deux cas du proxy alwaysdata (avec et sans `X-Forwarded-Proto: https`).

| Requête | Résultat obtenu | Attendu |
|---|---|---|
| `http://…/` | `301` vers `https://…/` | ✔ |
| `http://…/admin` | `301` vers `https://…/admin` | ✔ |
| `http://…/base-de-connaissances?q=ssh` | `301` vers `https://…/base-de-connaissances?q=ssh` (paramètre gardé) | ✔ |
| `http://…/assets/css/style.css` | `301` vers la version `https` | ✔ |
| Requête arrivée du proxy avec `X-Forwarded-Proto: https` | `200 OK` + `Strict-Transport-Security: max-age=31536000`, **pas de redirection** (donc pas de boucle) | ✔ |
| Même chose sur `/admin` | `303` vers `/admin/login` (comportement normal du tableau de bord) + HSTS | ✔ |
| Vraie connexion HTTPS (Apache avec certificat de test) | `200 OK` + HSTS | ✔ |
| Requête avec `X-Forwarded-Proto: http` | `301` vers HTTPS | ✔ |
| Serveur local `php -S` en HTTP | `200 OK`, pas de HSTS | ✔ |

## 6. Mettre en ligne

### 6.1 Sur GitHub

1. Ouvrir la **Pull Request** (PR, « demande de fusion ») « Forcer le HTTPS » du dépôt `portfolio-dashboard`.
2. Relire l'onglet **Files changed** (les 3 fichiers modifiés + cette documentation).
3. Cliquer sur **Merge pull request**, puis **Confirm merge**. Les changements sont maintenant dans `main`, mais **le site en ligne n'a pas encore changé**.

Rappel GitHub Desktop : si tu travailles aussi sur le Mac, fais **Fetch origin** puis **Pull origin** sur `main` pour récupérer la version fusionnée, et vérifie en haut de la fenêtre que tu es sur la bonne branche avant tout commit.

### 6.2 Sur le serveur alwaysdata

Dans le Terminal du Mac :

```bash
ssh olivierpizelle@ssh-olivierpizelle.alwaysdata.net
```
Se connecte au serveur d'alwaysdata en **SSH** (Secure Shell, une connexion chiffrée en ligne de commande). Le mot de passe demandé est celui du compte SSH alwaysdata ; rien ne s'affiche pendant la frappe, c'est normal.

```bash
cd www
```
Entre dans le dossier `www`, où se trouve le site (`cd` = change directory, « changer de dossier »).

```bash
git status
```
Affiche les fichiers modifiés **sur le serveur** depuis le dernier `git pull`. Les fichiers `data/*.json` peuvent avoir été changés par le tableau de bord : c'est normal. Si `public/.htaccess`, `src/bootstrap.php` ou `config.php` apparaissent comme modifiés, **s'arrêter** et demander de l'aide avant la suite.

```bash
git pull
```
Télécharge depuis GitHub les nouveautés de `main` et les applique aux fichiers du serveur. Le site en ligne utilise immédiatement la nouvelle version : pas besoin de redémarrer quoi que ce soit.

```bash
exit
```
Ferme la connexion SSH.

## 7. Vérifier que ça marche

### Avec le navigateur

1. Ouvrir une **fenêtre de navigation privée** (Cmd+Maj+N dans Chrome, Cmd+Maj+P dans Firefox/Safari), pour éviter le cache.
2. Taper `http://olivierpizelle.alwaysdata.net` (avec `http://` écrit en entier).
3. L'adresse doit se transformer toute seule en `https://…`, et un **cadenas** doit apparaître à gauche de la barre d'adresse.
4. Cliquer sur le cadenas › « La connexion est sécurisée » › « Le certificat est valide » pour voir le certificat (émetteur, date d'expiration).

### Avec `curl` (dans le Terminal du Mac)

`curl` est un outil qui envoie des requêtes web en ligne de commande. L'option `-I` n'affiche que les **en-têtes** de la réponse (pas la page).

```bash
curl -I http://olivierpizelle.alwaysdata.net/
```
Résultat attendu (extrait) :
```
HTTP/1.1 301 Moved Permanently
Location: https://olivierpizelle.alwaysdata.net/
```
Le serveur répond « déménagé définitivement » et donne l'adresse en HTTPS.

```bash
curl -I https://olivierpizelle.alwaysdata.net/
```
Résultat attendu (extrait) :
```
HTTP/2 200
strict-transport-security: max-age=31536000
```
La page s'affiche (`200`) et l'en-tête HSTS est bien présent. (Le nom de l'en-tête peut être en minuscules : c'est normal en HTTP/2.)

```bash
curl -sL -o /dev/null -w "%{http_code} après %{num_redirects} redirection(s)\n" http://olivierpizelle.alwaysdata.net/admin
```
Suit les redirections (`-L`) et affiche le code final : on doit obtenir `200 après 2 redirection(s)` (HTTP → HTTPS, puis `/admin` → `/admin/login`). Si la commande tourne longtemps puis affiche une erreur « Maximum redirects followed », c'est une boucle : voir le dépannage.

### Avec un outil en ligne (facultatif)

- https://securityheaders.com : note les en-têtes de sécurité du site (HSTS, CSP…).
- https://www.ssllabs.com/ssltest/ : analyse la configuration TLS et le certificat (géré par alwaysdata).

Une capture de ces résultats fait une bonne preuve pour le dossier E5.

## 8. Dépannage

| Symptôme | Cause probable | Que faire |
|---|---|---|
| « ERR_TOO_MANY_REDIRECTS » / « La page n'est pas redirigée correctement » | Le serveur ne reçoit aucun des deux indices HTTPS. | En SSH : `cd www`, `nano public/.htaccess`, mettre un `#` au début des trois lignes `RewriteCond … RewriteRule https…`, enregistrer (Ctrl+O, Entrée, Ctrl+X). Le site revient en HTTP. Cocher alors plutôt « Forcer HTTPS » dans alwaysdata (section 4), et signaler le problème. |
| Le site reste en `http://` | `git pull` pas fait, ou cache du navigateur. | Vérifier avec `curl -I http://…` (pas de cache). Refaire `git pull`. |
| `git pull` refuse (« would be overwritten ») | Un fichier du code a été modifié directement sur le serveur. | Ne rien forcer, demander de l'aide. |
| Besoin de revenir en arrière sur HSTS | Les navigateurs déjà venus retiennent HSTS pendant 1 an. | Mettre `'hsts_max_age' => 0` ne suffit pas pour eux. Il faudrait garder HTTPS fonctionnel. Pour son propre Chrome : `chrome://net-internals/#hsts` › « Delete domain security policies ». En pratique, avec le certificat géré par alwaysdata, ce cas ne devrait pas arriver. |

## 9. Pour l'épreuve E5

Cette évolution peut être présentée comme une tâche de sécurisation du service web :

- **Contexte** : le portfolio, publié chez alwaysdata, était accessible en HTTP non chiffré malgré la présence d'un certificat.
- **Risques** : interception du mot de passe du tableau de bord ou du cookie de session, modification des pages en transit, double indexation HTTP/HTTPS par les moteurs de recherche.
- **Mesures** : redirection permanente 301 côté Apache compatible avec un reverse proxy (`X-Forwarded-Proto`), en-tête HSTS d'un an, cookie de session `Secure`/`HttpOnly`/`SameSite=Strict` (déjà en place), supervision de l'expiration du certificat (tableau de bord).
- **Tests** : tableau de la section 5, captures `curl -I`, cadenas du navigateur, securityheaders.com.
- **Compétences mobilisées** (bloc 1) : *Mettre à disposition des utilisateurs un service informatique* (tests d'intégration, déploiement), *Développer la présence en ligne de l'organisation* (référencement : une seule version HTTPS indexée), et notions de cybersécurité (protection des données en transit).

## 10. Glossaire

| Terme | Définition |
|---|---|
| **Apache** | Logiciel serveur web qui exécute le site chez alwaysdata. |
| **Autorité de certification (AC)** | Organisme de confiance qui signe les certificats. |
| **Certificat** | « Carte d'identité » numérique d'un site, utilisée par TLS. |
| **curl** | Outil en ligne de commande pour envoyer des requêtes web. |
| **En-tête HTTP** | Ligne d'information envoyée avec une requête ou une réponse (ex. `Location`, `Strict-Transport-Security`). |
| **HSTS** | En-tête qui oblige le navigateur à n'utiliser que HTTPS pour un site pendant une durée donnée. |
| **HTTP / HTTPS** | Protocole du web, en clair / chiffré par TLS. |
| **mod_rewrite** | Module d'Apache qui réécrit ou redirige les adresses selon des règles. |
| **Proxy (inverse)** | Serveur intermédiaire placé devant le site, qui reçoit les visiteurs et transmet leurs requêtes. |
| **Redirection 301** | Réponse « page déplacée définitivement à telle adresse ». |
| **SSH** | Connexion chiffrée en ligne de commande vers un serveur. |
| **TLS** | Protocole qui chiffre et authentifie la connexion (successeur de SSL). |
| **X-Forwarded-Proto** | En-tête ajouté par un proxy pour indiquer si le visiteur est arrivé en `http` ou `https`. |
