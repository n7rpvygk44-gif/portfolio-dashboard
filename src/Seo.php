<?php
// Référencement : URL canonique, sitemap.xml et robots.txt.

declare(strict_types=1);

final class Seo
{
    public static function url(array $config, string $path): string
    {
        return rtrim($config['base_url'], '/') . page_path($path);
    }

    // Liste de toutes les pages publiques, utilisée pour le sitemap.
    public static function pages(JsonStore $store): array
    {
        $paths = ['/', '/parcours', '/competences', '/realisations', '/synthese', '/veille', '/base-de-connaissances', '/contexte', '/contact', '/supervision', '/mentions-legales'];
        foreach ($store->read('realisations') as $item) {
            $paths[] = '/realisations/' . $item['slug'];
        }
        foreach ($store->read('veille') as $item) {
            $paths[] = '/veille/' . $item['slug'];
        }
        foreach ($store->read('kb') as $item) {
            $paths[] = '/base-de-connaissances/' . $item['slug'];
        }
        foreach (Contexte::steps($store->read('contexte')) as $item) {
            $paths[] = '/contexte/' . $item['slug'];
        }
        foreach ($store->read('logiciels') as $item) {
            $paths[] = '/contexte/logiciels/' . $item['slug'];
        }
        return $paths;
    }

    // Date de dernière mise à jour connue d'une page (pour le sitemap), ou '' si inconnue.
    private static function lastmod(JsonStore $store, string $path): string
    {
        $map = ['/realisations/' => ['realisations', 'date'], '/veille/' => ['veille', 'date'], '/base-de-connaissances/' => ['kb', 'verifie_le']];
        foreach ($map as $prefix => [$collection, $field]) {
            if (str_starts_with($path, $prefix)) {
                $item = $store->find($collection, substr($path, strlen($prefix)));
                $date = (string) ($item[$field] ?? '');
                return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : '';
            }
        }
        return '';
    }

    public static function sitemap(array $config, JsonStore $store): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach (self::pages($store) as $path) {
            $lastmod = self::lastmod($store, $path);
            $xml .= '  <url><loc>' . e(self::url($config, $path)) . '</loc>' . ($lastmod !== '' ? "<lastmod>$lastmod</lastmod>" : '') . "</url>\n";
        }
        return $xml . "</urlset>\n";
    }

    public static function robots(array $config): string
    {
        $disallow = $config['admin_enabled'] ? url('/admin') : '';
        return "User-agent: *\nDisallow: $disallow\nSitemap: " . self::url($config, '/sitemap.xml') . "\n";
    }
}
