<?php
// Mesure d'audience maison, respectueuse de la vie privée.
// - aucun cookie, aucun identifiant, l'adresse IP n'est jamais enregistrée
// - seuls des compteurs par jour sont gardés : pages vues, visites, pages, provenance, type d'écran
// - les navigateurs qui demandent à ne pas être suivis (Do Not Track, GPC) ne sont pas comptés
// Les données sont rangées par mois dans data/audience-AAAA-MM.json.

declare(strict_types=1);

final class Audience
{
    private const BOTS = '/bot|crawl|spider|slurp|preview|monitor|curl|wget|python|headless|lighthouse/i';
    private const MAX_PAGES_PER_DAY = 200;

    // Sources connues regroupées sous un nom lisible.
    private const SOURCES = [
        'google.' => 'Google',
        'bing.' => 'Bing',
        'duckduckgo.' => 'DuckDuckGo',
        'qwant.' => 'Qwant',
        'ecosia.' => 'Ecosia',
        'linkedin.' => 'LinkedIn',
        'lnkd.in' => 'LinkedIn',
        'github.' => 'GitHub',
        'facebook.' => 'Facebook',
        'instagram.' => 'Instagram',
        't.co' => 'X (Twitter)',
        'x.com' => 'X (Twitter)',
    ];

    public function __construct(private JsonStore $store, private array $config)
    {
    }

    // Enregistre une page vue envoyée par assets/js/audience.js. Renvoie false si elle est ignorée.
    public function record(array $hit, string $userAgent, string $ownHost): bool
    {
        if ($userAgent === '' || preg_match(self::BOTS, $userAgent)) {
            return false;
        }
        $page = '/' . trim((string) parse_url((string) ($hit['p'] ?? ''), PHP_URL_PATH), '/');
        $page = $this->stripBasePath($page);
        if (strlen($page) > 120 || !preg_match('#^/[a-z0-9/_-]*$#', $page) || str_starts_with($page, '/admin')) {
            return false;
        }
        $newVisit = !empty($hit['v']);
        $source = $this->source((string) ($hit['r'] ?? ''), $ownHost);
        $width = (int) ($hit['w'] ?? 0);
        $device = $width === 0 ? 'Inconnu' : ($width < 768 ? 'Mobile' : ($width < 1100 ? 'Tablette' : 'Ordinateur'));

        $day = date('Y-m-d');
        $this->store->update('audience-' . date('Y-m'), function (array $data) use ($day, $page, $newVisit, $source, $device): array {
            $d = $data['jours'][$day] ?? ['vues' => 0, 'visites' => 0, 'pages' => [], 'sources' => [], 'appareils' => []];
            $d['vues']++;
            if (isset($d['pages'][$page]) || count($d['pages']) < self::MAX_PAGES_PER_DAY) {
                $d['pages'][$page] = ($d['pages'][$page] ?? 0) + 1;
            }
            if ($newVisit) {
                $d['visites']++;
                $d['sources'][$source] = ($d['sources'][$source] ?? 0) + 1;
                $d['appareils'][$device] = ($d['appareils'][$device] ?? 0) + 1;
            }
            $data['jours'][$day] = $d;
            ksort($data['jours']);
            return $data;
        });
        $this->purge();
        return true;
    }

    // Résumé des $days derniers jours pour le tableau de bord.
    public function report(int $days): array
    {
        $series = [];
        $totals = ['vues' => 0, 'visites' => 0];
        $pages = $sources = $devices = [];
        $months = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $month = substr($date, 0, 7);
            $months[$month] ??= $this->store->read('audience-' . $month);
            $d = $months[$month]['jours'][$date] ?? null;
            $series[$date] = ['vues' => $d['vues'] ?? 0, 'visites' => $d['visites'] ?? 0];
            if (!$d) {
                continue;
            }
            $totals['vues'] += $d['vues'];
            $totals['visites'] += $d['visites'];
            foreach (['pages' => &$pages, 'sources' => &$sources, 'appareils' => &$devices] as $key => &$sum) {
                foreach ($d[$key] as $name => $n) {
                    $sum[$name] = ($sum[$name] ?? 0) + $n;
                }
            }
            unset($sum);
        }
        arsort($pages);
        arsort($sources);
        arsort($devices);
        return [
            'jours' => $days,
            'series' => $series,
            'vues' => $totals['vues'],
            'visites' => $totals['visites'],
            'pages_par_visite' => $totals['visites'] > 0 ? round($totals['vues'] / $totals['visites'], 1) : 0,
            'pages' => $pages,
            'sources' => $sources,
            'appareils' => $devices,
        ];
    }

    private function source(string $referrer, string $ownHost): string
    {
        $host = strtolower((string) parse_url($referrer, PHP_URL_HOST));
        $host = preg_replace('/^www\./', '', $host);
        if ($host === '' || $host === preg_replace('/^www\./', '', strtolower($ownHost))) {
            return 'Accès direct';
        }
        foreach (self::SOURCES as $pattern => $name) {
            if (str_starts_with($host, $pattern) || str_contains($host, '.' . $pattern) || $host === $pattern) {
                return $name;
            }
        }
        return substr($host, 0, 60);
    }

    private function stripBasePath(string $page): string
    {
        $base = rtrim($this->config['base_path'] ?? '', '/');
        if ($base !== '' && str_starts_with($page, $base)) {
            $page = '/' . ltrim(substr($page, strlen($base)), '/');
        }
        return $page;
    }

    // Supprime les mois plus anciens que la durée de conservation .
    private function purge(): void
    {
        $limit = date('Y-m', strtotime('-' . (int) $this->config['audience_keep_months'] . ' months'));
        foreach (glob($this->config['data_dir'] . '/audience-*.json') ?: [] as $file) {
            if (preg_match('/audience-(\d{4}-\d{2})\.json$/', $file, $m) && $m[1] < $limit) {
                @unlink($file);
                @unlink($file . '.lock');
            }
        }
    }
}
