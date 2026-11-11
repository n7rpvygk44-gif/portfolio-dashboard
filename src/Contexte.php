<?php
// Fonctionnalité « Contexte » : les logiciels utilisés dans les réalisations, pourquoi ils ont été choisis,
// et les étapes pour construire un environnement informatique fondamental, autonome et personnel.
// Données : data/contexte.json (étapes) et data/logiciels.json (logiciels), modifiables dans le tableau de bord.

declare(strict_types=1);

final class Contexte
{
    public const URL = '/contexte';

    // Étapes triées par leur numéro d'ordre (champ « ordre »).
    public static function steps(array $steps): array
    {
        usort($steps, fn ($a, $b) => ((int) ($a['ordre'] ?? 0) <=> (int) ($b['ordre'] ?? 0)) ?: strcmp($a['titre'] ?? '', $b['titre'] ?? ''));
        return $steps;
    }

    // Retrouve des éléments à partir d'une liste de slugs, dans l'ordre de la liste (les slugs inconnus sont ignorés).
    public static function pick(array $slugs, array $items): array
    {
        $bySlug = array_column($items, null, 'slug');
        return array_values(array_filter(array_map(fn ($s) => $bySlug[$s] ?? null, $slugs)));
    }

    // Étapes où un logiciel intervient.
    public static function stepsOf(string $slug, array $steps): array
    {
        return array_values(array_filter($steps, fn ($s) => in_array($slug, $s['logiciels'] ?? [], true)));
    }

    // Réalisations qui utilisent un logiciel.
    public static function usedIn(string $slug, array $realisations): array
    {
        return array_values(array_filter($realisations, fn ($r) => in_array($slug, $r['logiciels'] ?? [], true)));
    }

    // Réalisations qui utilisent au moins un logiciel d'une étape.
    public static function realisationsOfStep(array $step, array $realisations): array
    {
        return array_values(array_filter($realisations, fn ($r) => array_intersect($step['logiciels'] ?? [], $r['logiciels'] ?? [])));
    }

    // Logiciels d'une réalisation, regroupés par étape (dans l'ordre des étapes).
    // Un logiciel présent dans plusieurs étapes est rangé dans la première ; ceux sans étape vont dans un groupe final.
    public static function groupForRealisation(array $realisation, array $logiciels, array $steps): array
    {
        $used = self::pick($realisation['logiciels'] ?? [], $logiciels);
        $groups = [];
        $placed = [];
        foreach (self::steps($steps) as $step) {
            $items = array_values(array_filter($used, fn ($l) => !isset($placed[$l['slug']]) && in_array($l['slug'], $step['logiciels'] ?? [], true)));
            if ($items) {
                foreach ($items as $l) {
                    $placed[$l['slug']] = true;
                }
                $groups[] = ['step' => $step, 'items' => $items];
            }
        }
        $others = array_values(array_filter($used, fn ($l) => !isset($placed[$l['slug']])));
        if ($others) {
            $groups[] = ['step' => null, 'items' => $others];
        }
        return $groups;
    }

    // Position d'une étape (1, 2, 3…) dans le parcours trié.
    public static function position(string $slug, array $sortedSteps): int
    {
        foreach ($sortedSteps as $i => $s) {
            if ($s['slug'] === $slug) {
                return $i + 1;
            }
        }
        return 0;
    }

    // Données structurées (schema.org HowTo) pour la page Contexte : les moteurs de recherche y lisent les étapes.
    public static function jsonLd(array $config, array $sortedSteps, string $title, string $description): array
    {
        return [[
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $title,
            'description' => $description,
            'inLanguage' => $config['lang'],
            'url' => Seo::url($config, self::URL),
            'step' => array_map(fn ($s, $i) => [
                '@type' => 'HowToStep',
                'position' => $i + 1,
                'name' => $s['titre'],
                'text' => $s['objectif'] ?? '',
                'url' => Seo::url($config, self::URL . '/' . $s['slug']),
            ], $sortedSteps, array_keys($sortedSteps)),
        ]];
    }
}
