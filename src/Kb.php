<?php
// Base de connaissances : filtres, parcours « Reproduire ce projet » et données structurées.

declare(strict_types=1);

final class Kb
{
    // Filtres possibles dans l'adresse : ?q=…&domaine=…&categorie=…&niveau=…
    public const FILTERS = ['domaine', 'categorie', 'niveau'];

    public static function filter(array $items, string $q, array $filters): array
    {
        return array_values(array_filter($items, function ($k) use ($q, $filters) {
            foreach ($filters as $name => $value) {
                if ($value !== '' && ($k[$name] ?? '') !== $value) {
                    return false;
                }
            }
            if ($q === '') {
                return true;
            }
            $haystack = implode(' ', [$k['titre'] ?? '', $k['outil'] ?? '', $k['resume'] ?? '', $k['domaine'] ?? '', $k['categorie'] ?? '', $k['contenu'] ?? '']);
            return mb_stripos($haystack, $q) !== false;
        }));
    }

    // Valeurs présentes dans les fiches pour un champ (pour proposer seulement des filtres utiles).
    public static function values(array $items, string $field): array
    {
        $values = array_unique(array_filter(array_map(fn ($k) => (string) ($k[$field] ?? ''), $items), 'strlen'));
        sort($values);
        return $values;
    }

    // Fiches d'une réalisation, dans l'ordre du parcours (les slugs inconnus sont ignorés).
    public static function path(array $realisation, array $kb): array
    {
        $bySlug = array_column($kb, null, 'slug');
        $list = [];
        foreach ($realisation['kb'] ?? [] as $slug) {
            if (isset($bySlug[$slug])) {
                $list[] = $bySlug[$slug];
            }
        }
        return $list;
    }

    // Réalisations qui utilisent une fiche, avec sa position dans chaque parcours.
    public static function usedIn(string $slug, array $realisations, array $kb): array
    {
        $out = [];
        foreach ($realisations as $r) {
            $steps = self::path($r, $kb);
            foreach ($steps as $i => $step) {
                if ($step['slug'] === $slug) {
                    $out[] = [
                        'realisation' => $r,
                        'position' => $i + 1,
                        'total' => count($steps),
                        'previous' => $steps[$i - 1] ?? null,
                        'next' => $steps[$i + 1] ?? null,
                    ];
                }
            }
        }
        return $out;
    }

    // Additionne les durées « 20 min », « 1 h », « 1 h 30 » d'un parcours. Renvoie '' si aucune durée.
    public static function totalDuration(array $steps): string
    {
        $minutes = 0;
        foreach ($steps as $s) {
            $d = mb_strtolower((string) ($s['duree'] ?? ''));
            if (preg_match('/(\d+)\s*h/', $d, $h)) {
                $minutes += 60 * (int) $h[1];
                $d = preg_replace('/(\d+)\s*h/', '', $d, 1);
            }
            if (preg_match('/(\d+)/', $d, $m)) {
                $minutes += (int) $m[1];
            }
        }
        if ($minutes === 0) {
            return '';
        }
        $h = intdiv($minutes, 60);
        $m = $minutes % 60;
        return $h ? $h . ' h' . ($m ? ' ' . str_pad((string) $m, 2, '0', STR_PAD_LEFT) : '') : $m . ' min';
    }

    // Données structurées (schema.org) lues par les moteurs de recherche.
    public static function jsonLd(array $config, array $item, array $profile): array
    {
        $url = Seo::url($config, '/base-de-connaissances/' . $item['slug']);
        $author = ['@type' => 'Person', 'name' => $profile['nom'] ?? $config['site_name']];
        $article = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'TechArticle',
            'headline' => $item['titre'],
            'description' => $item['resume'] ?? '',
            'inLanguage' => $config['lang'],
            'url' => $url,
            'author' => $author,
            'dateModified' => $item['verifie_le'] ?? '',
            'proficiencyLevel' => in_array($item['niveau'] ?? '', ['Avancé'], true) ? 'Expert' : (($item['niveau'] ?? '') !== '' ? 'Beginner' : ''),
            'about' => $item['outil'] ?? '',
        ], fn ($v) => $v !== '' && $v !== []);

        $crumbs = [['Base de connaissances', '/base-de-connaissances']];
        if (!empty($item['domaine'])) {
            $crumbs[] = [$item['domaine'], '/base-de-connaissances?domaine=' . rawurlencode($item['domaine'])];
        }
        $crumbs[] = [$item['titre'], '/base-de-connaissances/' . $item['slug']];
        $breadcrumb = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn ($c, $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $c[0],
                'item' => Seo::url($config, $c[1]),
            ], $crumbs, array_keys($crumbs)),
        ];

        $data = [$article, $breadcrumb];
        $steps = Markdown::steps($item['contenu'] ?? '');
        if (($item['categorie'] ?? '') === 'Procédure' && $steps) {
            $data[] = [
                '@context' => 'https://schema.org',
                '@type' => 'HowTo',
                'name' => $item['titre'],
                'description' => $item['resume'] ?? '',
                'step' => array_map(fn ($s, $i) => [
                    '@type' => 'HowToStep',
                    'position' => $i + 1,
                    'name' => $s['titre'],
                    'url' => $url . '#' . $s['id'],
                ], $steps, array_keys($steps)),
            ];
        }
        return $data;
    }
}
