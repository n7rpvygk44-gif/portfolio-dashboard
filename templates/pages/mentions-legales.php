<h1>Mentions légales</h1>

<h2>Éditeur du site</h2>
<p>
    Ce site est le portfolio personnel de <?= e($profile['nom'] ?? '') ?>, étudiant en BTS SIO option SISR.
    Il est réalisé dans le cadre de la formation et n'a pas de but commercial.
    <?php if (!empty($profile['email'])): ?>
        <br>Contact : <a href="mailto:<?= e($profile['email']) ?>"><?= e($profile['email']) ?></a>
    <?php endif; ?>
</p>
<p>Directeur de la publication : <?= e($profile['nom'] ?? '') ?>.</p>

<h2>Hébergement</h2>
<?php if (!empty($config['host'])): ?>
    <p><?= nl2br(e($config['host'])) ?></p>
<?php else: ?>
    <p>GitHub Pages, service de GitHub Inc., 88 Colin P. Kelly Jr. Street, San Francisco, CA 94107, États-Unis (<a href="https://pages.github.com/" rel="noopener">pages.github.com</a>).</p>
<?php endif; ?>

<h2>Données personnelles et cookies</h2>
<p>
    <?php if (!empty($config['audience_enabled'])): ?>
        Ce site ne collecte aucune donnée personnelle et ne dépose aucun cookie. Il n'a pas de formulaire.
        Une mesure d'audience anonyme compte les pages vues : pour chaque page ouverte, le serveur ajoute 1 au compteur du jour pour cette page,
        pour le site d'où vous venez (par exemple « Google ») et pour votre type d'écran (ordinateur, tablette ou mobile).
        Votre adresse IP n'est pas enregistrée, aucun identifiant ne vous est attribué et ces compteurs sont supprimés après 13 mois.
        Si votre navigateur envoie le signal « Ne pas me suivre » (Do Not Track ou Global Privacy Control), rien n'est compté.
    <?php else: ?>
        Ce site ne collecte aucune donnée personnelle : il n'a pas de formulaire, pas de mesure d'audience et ne dépose aucun cookie.
    <?php endif; ?>
    Les réglages choisis (accessibilité, son activé ou coupé) sont enregistrés uniquement dans votre navigateur et ne sont envoyés nulle part.
    La page Supervision mesure le chargement des pages (poids, temps, fluidité) directement dans votre navigateur ; ces mesures restent dans l'onglet ouvert et disparaissent à sa fermeture.
</p>
<p>L'hébergeur peut conserver des journaux techniques de connexion (adresse IP, date), comme tout serveur web.</p>

<h2>Propriété intellectuelle et crédits</h2>
<p>
    Les textes, illustrations et animations (Trantor, Radiant) sont des créations originales.
    L'univers de <i>Fondation</i>, d'Isaac Asimov et de la série adaptée par Apple TV+, est cité comme inspiration et reste la propriété de ses auteurs ; aucun visuel officiel n'est reproduit.
</p>
<p>Le code source du site est publié sous licence MIT<?php if (!empty($profile['github'])): ?> sur <a href="<?= e($profile['github']) ?>" rel="noopener">GitHub</a><?php endif; ?>.</p>

<h2>Accessibilité</h2>
<p>Le site vise le respect du RGAA : textes alternatifs, contrastes suffisants, navigation complète au clavier et panneau de réglages d'accessibilité en haut de chaque page.</p>
