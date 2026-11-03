<?php
// Petits graphiques SVG dessinés côté serveur pour le tableau de bord.
// Aucun JavaScript ni bibliothèque externe : le graphique est du HTML comme le reste de la page.
// Les couleurs viennent de admin.css (classes chart-*), elles suivent donc le mode clair ou sombre.

declare(strict_types=1);

final class Chart
{
    // Diagramme en barres. $values : [étiquette => nombre ou null].
    public static function bars(array $values, string $title, string $unit = '', int $height = 160): string
    {
        $width = 640;
        $padTop = 14;
        $padBottom = 22;
        $plot = $height - $padTop - $padBottom;
        $max = max(1, ...array_map(fn ($v) => (int) $v, array_values($values) ?: [0]));
        $max = self::niceMax($max);
        $n = max(1, count($values));
        $left = 40;
        $slot = ($width - $left) / $n;
        $bar = max(2, $slot * 0.7);

        $svg = self::open($width, $height, $title);
        foreach ([0, 0.5, 1] as $f) {
            $y = $padTop + $plot * (1 - $f);
            $svg .= sprintf('<line class="chart-grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', $left, $width, $y, $y);
            $svg .= sprintf('<text class="chart-axis" x="%d" y="%.1f" text-anchor="end">%s</text>', $left - 6, $y + 4, e(self::fmt($max * $f)));
        }
        $i = 0;
        $every = (int) ceil($n / 10);
        foreach ($values as $label => $v) {
            $x = $left + $i * $slot + ($slot - $bar) / 2;
            $h = $v ? max(2, $plot * $v / $max) : 0;
            $svg .= sprintf(
                '<rect class="chart-bar" x="%.1f" y="%.1f" width="%.1f" height="%.1f" rx="2"><title>%s : %s%s</title></rect>',
                $x, $padTop + $plot - $h, $bar, $h, e((string) $label), e(self::fmt((float) $v)), e($unit)
            );
            if ($i % $every === 0) {
                $svg .= sprintf('<text class="chart-axis" x="%.1f" y="%d" text-anchor="middle">%s</text>', $x + $bar / 2, $height - 6, e(self::shortLabel((string) $label)));
            }
            $i++;
        }
        return $svg . '</svg>';
    }

    // Courbe. Les valeurs null (pas de mesure) coupent la ligne.
    public static function line(array $values, string $title, string $unit = '', int $height = 160): string
    {
        $width = 640;
        $padTop = 14;
        $padBottom = 22;
        $plot = $height - $padTop - $padBottom;
        $numbers = array_filter($values, fn ($v) => $v !== null);
        $max = self::niceMax(max(1, ...array_values($numbers ?: [1])));
        $n = max(2, count($values));
        $left = 40;
        $step = ($width - $left - 8) / ($n - 1);

        $svg = self::open($width, $height, $title);
        foreach ([0, 0.5, 1] as $f) {
            $y = $padTop + $plot * (1 - $f);
            $svg .= sprintf('<line class="chart-grid" x1="%d" x2="%d" y1="%.1f" y2="%.1f"/>', $left, $width, $y, $y);
            $svg .= sprintf('<text class="chart-axis" x="%d" y="%.1f" text-anchor="end">%s</text>', $left - 6, $y + 4, e(self::fmt($max * $f)));
        }
        $segments = [];
        $current = [];
        $dots = '';
        $i = 0;
        $every = (int) ceil($n / 8);
        foreach ($values as $label => $v) {
            $x = $left + 4 + $i * $step;
            if ($v === null) {
                $segments[] = $current;
                $current = [];
            } else {
                $y = $padTop + $plot * (1 - $v / $max);
                $current[] = sprintf('%.1f,%.1f', $x, $y);
                $dots .= sprintf('<circle class="chart-dot" cx="%.1f" cy="%.1f" r="3"><title>%s : %s %s</title></circle>', $x, $y, e((string) $label), e(self::fmt((float) $v)), e($unit));
            }
            if ($i % $every === 0) {
                $svg .= sprintf('<text class="chart-axis" x="%.1f" y="%d" text-anchor="middle">%s</text>', $x, $height - 6, e(self::shortLabel((string) $label)));
            }
            $i++;
        }
        $segments[] = $current;
        foreach ($segments as $points) {
            if (count($points) > 1) {
                $svg .= '<polyline class="chart-line" points="' . implode(' ', $points) . '"/>';
            }
        }
        return $svg . $dots . '</svg>';
    }

    private static function open(int $width, int $height, string $title): string
    {
        return sprintf('<svg class="chart" viewBox="0 0 %d %d" role="img" aria-label="%s">', $width, $height, e($title));
    }

    // Arrondit le haut de l'échelle à une valeur ronde (5, 10, 20, 50, 100…).
    private static function niceMax(float $max): float
    {
        $pow = 10 ** floor(log10($max));
        foreach ([1, 2, 5, 10] as $m) {
            if ($m * $pow >= $max) {
                return $m * $pow;
            }
        }
        return 10 * $pow;
    }

    private static function fmt(float $n): string
    {
        return number_format($n, $n == floor($n) ? 0 : 1, ',', ' ');
    }

    // « 2026-11-03 » devient « 03/11 », « 2026-11-03 14:00 » devient « 14 h ».
    private static function shortLabel(string $label): string
    {
        if (preg_match('/^\d{4}-(\d{2})-(\d{2}) (\d{2}):00$/', $label, $m)) {
            return $m[3] . ' h';
        }
        if (preg_match('/^\d{4}-(\d{2})-(\d{2})$/', $label, $m)) {
            return $m[2] . '/' . $m[1];
        }
        return $label;
    }
}
