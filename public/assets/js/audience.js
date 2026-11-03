// Mesure d'audience respectueuse de la vie privée.
// Envoie au serveur la page vue, le site d'où vient le visiteur et la largeur de l'écran.
// Aucun cookie, aucun identifiant, rien n'est gardé dans le navigateur.
// Si le navigateur demande à ne pas être suivi (Do Not Track ou Global Privacy Control), rien n'est envoyé.
(function () {
    'use strict';

    const script = document.currentScript;
    if (!script || navigator.doNotTrack === '1' || window.doNotTrack === '1' || navigator.globalPrivacyControl === true) {
        return;
    }

    // Une visite commence quand on arrive d'un autre site (ou d'aucun) ; les clics internes sont de simples pages vues.
    let fromOutside = true;
    try {
        fromOutside = !document.referrer || new URL(document.referrer).host !== location.host;
    } catch (e) {
        fromOutside = true;
    }

    const hit = JSON.stringify({
        p: location.pathname,
        r: fromOutside ? document.referrer : '',
        v: fromOutside ? 1 : 0,
        w: window.screen ? window.screen.width : 0,
    });

    const endpoint = script.dataset.endpoint;
    if (navigator.sendBeacon) {
        navigator.sendBeacon(endpoint, new Blob([hit], { type: 'application/json' }));
    } else {
        fetch(endpoint, { method: 'POST', body: hit, keepalive: true, headers: { 'Content-Type': 'application/json' } });
    }
})();
