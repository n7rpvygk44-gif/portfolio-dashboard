// Scripts du site public : menu mobile, panneau d'accessibilité, filtre de la base de connaissances.

(function () {
    'use strict';

    const root = document.documentElement;
    const STORAGE_KEY = 'portfolio-a11y';

    // Chaque option du panneau correspond à une classe CSS posée sur <html>.
    const OPTIONS = {
        theme: 'theme-dark',
        large: 'a11y-large',
        contrast: 'a11y-contrast',
        contrastDark: 'a11y-contrast-dark',
        readable: 'a11y-readable',
        motion: 'a11y-motion',
    };

    const EXCLUSIVE = { contrast: 'contrastDark', contrastDark: 'contrast' };

    function loadSettings() {
        try {
            return JSON.parse(localStorage.getItem(STORAGE_KEY)) || {};
        } catch (e) {
            return {};
        }
    }

    function saveSettings(settings) {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(settings));
        } catch (e) {
            // Stockage indisponible (navigation privée) : les réglages restent valables pour la page.
        }
    }

    function applySettings(settings) {
        Object.entries(OPTIONS).forEach(([key, className]) => {
            root.classList.toggle(className, Boolean(settings[key]));
            const button = document.querySelector('[data-a11y="' + key + '"]');
            if (button) {
                button.setAttribute('aria-pressed', settings[key] ? 'true' : 'false');
            }
        });
        // Si l'utilisateur a désactivé le mode sombre explicitement, on force le clair.
        root.classList.toggle('theme-light', settings.theme === false);
    }

    let settings = loadSettings();
    // Réglages enregistrés avec les deux contrastes à la fois : on garde le contraste sombre.
    if (settings.contrast && settings.contrastDark) {
        settings.contrast = false;
        saveSettings(settings);
    }
    applySettings(settings);

    document.querySelectorAll('[data-a11y]').forEach((button) => {
        button.addEventListener('click', () => {
            const key = button.dataset.a11y;
            settings[key] = !settings[key];
            // Les deux contrastes (clair et sombre) s'excluent : en activer un désactive l'autre.
            if (settings[key] && EXCLUSIVE[key]) {
                settings[EXCLUSIVE[key]] = false;
            }
            applySettings(settings);
            saveSettings(settings);
            document.dispatchEvent(new CustomEvent('a11y-change', { detail: settings }));
        });
    });

    const reset = document.querySelector('[data-a11y-reset]');
    if (reset) {
        reset.addEventListener('click', () => {
            settings = {};
            applySettings(settings);
            saveSettings(settings);
            document.dispatchEvent(new CustomEvent('a11y-change', { detail: settings }));
        });
    }

    // Menu mobile
    const toggle = document.querySelector('.menu-toggle');
    const menu = document.getElementById('menu');
    if (toggle && menu) {
        toggle.addEventListener('click', () => {
            const open = menu.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
    }

    // Base de connaissances : filtre instantané pendant la saisie et au choix d'un filtre
    // (le formulaire fonctionne aussi sans JavaScript grâce à la recherche côté serveur).
    const filter = document.querySelector('[data-kb-filter]');
    if (filter) {
        const empty = document.querySelector('[data-kb-empty]');
        const selects = document.querySelectorAll('[data-kb-select]');
        const apply = () => {
            const q = filter.value.trim().toLowerCase();
            const wanted = {};
            selects.forEach((s) => { wanted[s.dataset.kbSelect] = s.value; });
            let visible = 0;
            document.querySelectorAll('[data-kb-section]').forEach((section) => {
                let inSection = 0;
                section.querySelectorAll('.card').forEach((card) => {
                    let match = card.textContent.toLowerCase().includes(q);
                    Object.keys(wanted).forEach((name) => {
                        if (wanted[name] && card.dataset[name] !== wanted[name]) {
                            match = false;
                        }
                    });
                    card.hidden = !match;
                    if (match) {
                        inSection++;
                    }
                });
                section.hidden = inSection === 0;
                visible += inSection;
            });
            if (empty) {
                empty.hidden = visible > 0;
            }
        };
        filter.addEventListener('input', apply);
        selects.forEach((s) => s.addEventListener('change', apply));

        // Changer le classement recharge la page (le regroupement est fait par le serveur).
        const group = document.querySelector('[data-kb-group]');
        if (group && group.form) {
            group.addEventListener('change', () => group.form.submit());
        }

        // Version statique : la recherche arrive dans l'adresse (?q=...), on l'applique au chargement.
        const params = new URLSearchParams(window.location.search);
        if (params.get('q')) {
            filter.value = params.get('q');
        }
        selects.forEach((s) => {
            const v = params.get(s.dataset.kbSelect);
            if (v) {
                s.value = v;
            }
        });
        if (params.toString()) {
            apply();
        }
    }

    // Fiches : bouton « Copier » sur chaque bloc de commandes.
    document.querySelectorAll('.kb-content pre').forEach((pre) => {
        const wrap = document.createElement('div');
        wrap.className = 'code-block';
        pre.parentNode.insertBefore(wrap, pre);
        wrap.appendChild(pre);
        const code = pre.querySelector('code');
        const lang = code && /language-([a-z0-9_-]+)/.exec(code.className);
        if (lang) {
            const label = document.createElement('span');
            label.className = 'code-lang';
            label.textContent = lang[1];
            label.setAttribute('aria-hidden', 'true');
            wrap.appendChild(label);
        }
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'copy-button';
        button.textContent = 'Copier';
        button.setAttribute('aria-label', 'Copier la commande');
        button.addEventListener('click', async () => {
            const text = pre.innerText.replace(/\n$/, '');
            try {
                await navigator.clipboard.writeText(text);
            } catch (err) {
                // Ancienne méthode si le presse-papiers moderne est refusé.
                const area = document.createElement('textarea');
                area.value = text;
                document.body.appendChild(area);
                area.select();
                document.execCommand('copy');
                area.remove();
            }
            button.textContent = 'Copié ✓';
            setTimeout(() => { button.textContent = 'Copier'; }, 2000);
        });
        wrap.appendChild(button);
    });

    // Fiches : case à cocher sur chaque « ### Étape … » pour suivre sa progression.
    // Mémorisée seulement dans ce navigateur (localStorage), aucun cookie, rien n'est envoyé.
    const article = document.querySelector('.kb-article[data-kb-slug]');
    if (article) {
        const steps = [...article.querySelectorAll('.kb-content h3')].filter((h) => /^[ée]tape\b/i.test(h.textContent.trim()));
        const key = 'kb-etapes-' + article.dataset.kbSlug;
        let done = {};
        try {
            done = JSON.parse(localStorage.getItem(key) || '{}') || {};
        } catch (err) {
            done = {};
        }
        let progress = null;
        if (steps.length > 1) {
            progress = document.createElement('p');
            progress.className = 'kb-progress';
            progress.setAttribute('aria-live', 'polite');
            steps[0].parentNode.insertBefore(progress, steps[0]);
        }
        const update = () => {
            if (progress) {
                const n = steps.filter((h) => done[h.id]).length;
                progress.textContent = 'Progression : ' + n + ' étape' + (n > 1 ? 's' : '') + ' faite' + (n > 1 ? 's' : '') + ' sur ' + steps.length + '.';
            }
        };
        steps.forEach((h) => {
            const label = document.createElement('label');
            label.className = 'step-check';
            const box = document.createElement('input');
            box.type = 'checkbox';
            box.checked = !!done[h.id];
            h.classList.toggle('step-done', box.checked);
            box.addEventListener('change', () => {
                if (box.checked) {
                    done[h.id] = 1;
                } else {
                    delete done[h.id];
                }
                h.classList.toggle('step-done', box.checked);
                try {
                    localStorage.setItem(key, JSON.stringify(done));
                } catch (err) {
                    // Navigation privée : la case fonctionne, mais n'est pas mémorisée.
                }
                update();
            });
            label.append(box, document.createTextNode('fait'));
            h.appendChild(label);
        });
        update();
    }

    // Compétence choisie depuis l'accueil (#b1-2…) : elle est placée au centre de l'écran.
    // Le navigateur saute d'abord en haut de l'encadré, puis la page bouge encore (polices,
    // animations) : on recentre plusieurs fois, sauf si le visiteur a commencé à défiler.
    function targetCompetence() {
        const id = decodeURIComponent(location.hash.slice(1));
        const target = id ? document.getElementById(id) : null;
        return target && target.classList.contains('competence') ? target : null;
    }
    function centerTarget() {
        const target = targetCompetence();
        if (!target) {
            return;
        }
        // Position réelle dans la page, sans les transformations d'animation.
        let top = 0;
        for (let el = target; el; el = el.offsetParent) {
            top += el.offsetTop;
        }
        const header = document.querySelector('.site-header');
        const pinned = header && ['sticky', 'fixed'].includes(getComputedStyle(header).position);
        const headerHeight = pinned ? header.offsetHeight : 0;
        const room = window.innerHeight - headerHeight;
        const offset = target.offsetHeight < room ? (room - target.offsetHeight) / 2 : 16;
        window.scrollTo(0, Math.max(0, top - headerHeight - offset));
    }
    if (targetCompetence()) {
        if ('scrollRestoration' in history) {
            history.scrollRestoration = 'manual';
        }
        let userScrolled = false;
        const stop = () => { userScrolled = true; };
        ['wheel', 'touchstart', 'keydown'].forEach((type) => window.addEventListener(type, stop, { once: true, passive: true }));
        const again = () => { if (!userScrolled) { centerTarget(); } };
        again();
        document.addEventListener('DOMContentLoaded', again);
        window.addEventListener('load', () => { again(); [150, 500, 1200, 2500, 3200].forEach((ms) => setTimeout(again, ms)); });
        if (document.fonts && document.fonts.ready) {
            document.fonts.ready.then(again);
        }
    }
    window.addEventListener('hashchange', centerTarget);
    // Réalisations : onglets « Présentation », « Contexte » et « Reproduire ce projet ».
    // Sans JavaScript, les onglets restent des liens vers des sections toutes visibles.
    // Ici, on les transforme en vrais onglets accessibles (rôles ARIA, flèches du clavier),
    // et l'adresse (#onglet-contexte, #reproduire…) ouvre directement le bon onglet.
    const tabNav = document.querySelector('[data-tabs]');
    if (tabNav) {
        const list = tabNav.querySelector('ul');
        const tabs = [...tabNav.querySelectorAll('[data-tab]')];
        const panels = tabs.map((tab) => document.getElementById(tab.dataset.tab));
        if (panels.every(Boolean)) {
            list.setAttribute('role', 'tablist');
            list.querySelectorAll('li').forEach((li) => li.setAttribute('role', 'presentation'));
            tabs.forEach((tab, i) => {
                tab.setAttribute('role', 'tab');
                tab.setAttribute('aria-controls', panels[i].id);
                panels[i].setAttribute('role', 'tabpanel');
                panels[i].setAttribute('aria-labelledby', tab.id);
            });
            tabNav.classList.add('is-ready');

            const select = (index, focus) => {
                tabs.forEach((tab, i) => {
                    const active = i === index;
                    tab.setAttribute('aria-selected', active ? 'true' : 'false');
                    tab.tabIndex = active ? 0 : -1;
                    panels[i].hidden = !active;
                });
                if (focus) {
                    tabs[index].focus();
                }
                document.dispatchEvent(new CustomEvent('tab-change', { detail: { panel: panels[index] } }));
            };
            // Onglet qui contient l'élément visé par l'adresse (l'onglet lui-même ou un titre à l'intérieur).
            const fromHash = () => {
                const id = decodeURIComponent(location.hash.slice(1));
                const target = id ? document.getElementById(id) : null;
                const index = target ? panels.findIndex((panel) => panel.contains(target)) : -1;
                return { index, target };
            };
            const show = (index, scrollTo) => {
                select(index, false);
                if (scrollTo) {
                    (scrollTo === panels[index] ? tabNav : scrollTo).scrollIntoView({ block: 'start' });
                }
            };

            const initial = fromHash();
            show(initial.index >= 0 ? initial.index : 0, initial.index >= 0 ? initial.target : null);

            tabs.forEach((tab, i) => {
                tab.addEventListener('click', (event) => {
                    event.preventDefault();
                    select(i, false);
                    history.replaceState(null, '', '#' + panels[i].id);
                });
                tab.addEventListener('keydown', (event) => {
                    const keys = { ArrowRight: i + 1, ArrowLeft: i - 1, Home: 0, End: tabs.length - 1 };
                    if (event.key in keys) {
                        event.preventDefault();
                        const next = (keys[event.key] + tabs.length) % tabs.length;
                        select(next, true);
                        history.replaceState(null, '', '#' + panels[next].id);
                    }
                });
            });
            // Liens internes vers un onglet (ex. « Pourquoi ces outils ? Voir le contexte »).
            document.querySelectorAll('[data-tab-link]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    const index = panels.findIndex((panel) => panel.id === link.dataset.tabLink);
                    if (index >= 0) {
                        event.preventDefault();
                        select(index, true);
                        history.replaceState(null, '', '#' + panels[index].id);
                        tabNav.scrollIntoView({ block: 'start', behavior: root.classList.contains('a11y-motion') ? 'auto' : 'smooth' });
                    }
                });
            });
            window.addEventListener('hashchange', () => {
                const { index, target } = fromHash();
                if (index >= 0) {
                    show(index, target);
                }
            });
        }
    }
})();
