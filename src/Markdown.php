<?php
// Convertisseur Markdown minimal pour les fiches de la base de connaissances.
// Gère : titres (##, ###, ####) avec ancre, paragraphes, listes, blocs de code (```bash),
// `code`, **gras**, *italique*, [liens](url), images ![texte](/assets/...), tableaux | a | b |
// et encadrés : une ligne qui commence par « > Attention : », « > Astuce : », « > Remarque : »
// ou « > Résultat attendu : » devient un encadré coloré (« > texte » seul : citation simple).
// Le texte est échappé avant toute mise en forme, donc aucun HTML saisi n'est exécuté.

declare(strict_types=1);

final class Markdown
{
    // Mot de début d'encadré => classe CSS.
    private const CALLOUTS = [
        'attention' => 'warning',
        'important' => 'warning',
        'astuce' => 'tip',
        'conseil' => 'tip',
        'remarque' => 'note',
        'note' => 'note',
        'résultat attendu' => 'result',
        'resultat attendu' => 'result',
    ];

    public static function toHtml(string $text): string
    {
        $lines = preg_split('/\R/', $text);
        $html = '';
        $paragraph = [];
        $list = null;
        $quote = [];
        $table = [];
        $inCode = false;
        $codeLang = '';
        $code = [];
        $ids = [];

        $flushParagraph = function () use (&$paragraph, &$html) {
            if ($paragraph) {
                $html .= '<p>' . self::inline(implode(' ', $paragraph)) . "</p>\n";
                $paragraph = [];
            }
        };
        $closeList = function () use (&$list, &$html) {
            if ($list) {
                $html .= "</$list>\n";
                $list = null;
            }
        };
        $flushQuote = function () use (&$quote, &$html) {
            if ($quote) {
                $html .= self::callout($quote);
                $quote = [];
            }
        };
        $flushTable = function () use (&$table, &$html) {
            if ($table) {
                $html .= self::table($table);
                $table = [];
            }
        };
        $flushAll = function () use ($flushParagraph, $closeList, $flushQuote, $flushTable) {
            $flushParagraph();
            $closeList();
            $flushQuote();
            $flushTable();
        };
        $flushCode = function () use (&$code, &$codeLang, &$html) {
            $class = $codeLang !== '' ? ' class="language-' . e($codeLang) . '"' : '';
            $html .= '<pre><code' . $class . '>' . e(implode("\n", $code)) . "</code></pre>\n";
            $code = [];
            $codeLang = '';
        };

        foreach ($lines as $line) {
            if (preg_match('/^\s*```\s*([a-z0-9_-]*)\s*$/i', $line, $fence)) {
                if ($inCode) {
                    $flushCode();
                    $inCode = false;
                } else {
                    $flushAll();
                    $inCode = true;
                    $codeLang = strtolower($fence[1]);
                }
                continue;
            }
            if ($inCode) {
                $code[] = $line;
                continue;
            }

            $trim = trim($line);
            if ($trim !== '' && $trim[0] === '>') {
                $flushParagraph();
                $closeList();
                $flushTable();
                $quote[] = ltrim(substr($trim, 1));
                continue;
            }
            if ($trim !== '' && $trim[0] === '|') {
                $flushParagraph();
                $closeList();
                $flushQuote();
                $table[] = $trim;
                continue;
            }
            $flushQuote();
            $flushTable();

            if ($trim === '') {
                $flushParagraph();
                $closeList();
            } elseif (preg_match('/^(#{2,4})\s+(.*)$/', $trim, $m)) {
                $flushParagraph();
                $closeList();
                $level = strlen($m[1]);
                $id = self::uniqueId($m[2], $ids);
                $html .= "<h$level id=\"$id\">" . self::inline($m[2]) . "</h$level>\n";
            } elseif (preg_match('/^!\[([^\]]*)\]\(([^)\s]+)\)$/', $trim, $m)) {
                $flushParagraph();
                $closeList();
                $html .= self::image($m[1], $m[2]);
            } elseif (preg_match('/^([-*]|\d+\.)\s+(.*)$/', $trim, $m)) {
                $flushParagraph();
                $type = ctype_digit($m[1][0]) ? 'ol' : 'ul';
                if ($list !== $type) {
                    $closeList();
                    $html .= "<$type>\n";
                    $list = $type;
                }
                $html .= '<li>' . self::inline($m[2]) . "</li>\n";
            } else {
                $closeList();
                $paragraph[] = $trim;
            }
        }
        if ($inCode) {
            $flushCode();
        }
        $flushAll();
        return $html;
    }

    // Titres de niveau 2 (## …) avec leur ancre, pour construire le sommaire d'une fiche.
    public static function headings(string $text, int $level = 2): array
    {
        $out = [];
        $ids = [];
        $inCode = false;
        foreach (preg_split('/\R/', $text) as $line) {
            if (preg_match('/^\s*```/', $line)) {
                $inCode = !$inCode;
                continue;
            }
            if (!$inCode && preg_match('/^(#{2,4})\s+(.*)$/', trim($line), $m)) {
                $id = self::uniqueId($m[2], $ids);
                if (strlen($m[1]) === $level) {
                    $out[] = ['id' => $id, 'titre' => self::plain($m[2])];
                }
            }
        }
        return $out;
    }

    // Étapes d'une procédure : les titres « ### Étape … », utilisés pour le référencement (HowTo).
    public static function steps(string $text): array
    {
        return array_values(array_filter(
            self::headings($text, 3),
            fn ($h) => preg_match('/^[ée]tape\b/iu', $h['titre'])
        ));
    }

    // Texte brut sans mise en forme (pour les balises meta et les données structurées).
    public static function plain(string $text): string
    {
        $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $text);
        return trim(str_replace(['**', '`', '*'], '', $text));
    }

    private static function uniqueId(string $title, array &$ids): string
    {
        $base = Admin::slugify(self::plain($title));
        $id = $base;
        $n = 2;
        while (isset($ids[$id])) {
            $id = $base . '-' . $n++;
        }
        $ids[$id] = true;
        return $id;
    }

    private static function callout(array $lines): string
    {
        $text = implode(' ', array_filter($lines, fn ($l) => $l !== ''));
        foreach (self::CALLOUTS as $word => $class) {
            if (preg_match('/^' . preg_quote($word, '/') . '\s*:\s*(.*)$/iu', $text, $m)) {
                $label = mb_strtoupper(mb_substr($word, 0, 1)) . mb_substr($word, 1);
                if ($class === 'result') {
                    $label = 'Résultat attendu';
                }
                $body = mb_strtoupper(mb_substr($m[1], 0, 1)) . mb_substr($m[1], 1);
                return '<div class="callout callout-' . $class . '"><p><strong class="callout-label">' . e($label) . '</strong> '
                    . self::inline($body) . "</p></div>\n";
            }
        }
        return '<blockquote><p>' . self::inline($text) . "</p></blockquote>\n";
    }

    private static function table(array $rows): string
    {
        $cells = fn (string $row) => array_map('trim', explode('|', trim(trim($row), '|')));
        $head = $cells($rows[0]);
        $body = array_slice($rows, 1);
        if ($body && preg_match('/^\|?[\s:|-]+\|?$/', $body[0])) {
            array_shift($body);
        } else {
            // Pas de ligne de séparation : toutes les lignes sont des données.
            $body = $rows;
            $head = [];
        }
        $html = '<div class="table-wrap"><table>';
        if ($head) {
            $html .= '<thead><tr>' . implode('', array_map(fn ($c) => '<th scope="col">' . self::inline($c) . '</th>', $head)) . '</tr></thead>';
        }
        $html .= '<tbody>';
        foreach ($body as $row) {
            $html .= '<tr>' . implode('', array_map(fn ($c) => '<td>' . self::inline($c) . '</td>', $cells($row))) . '</tr>';
        }
        return $html . "</tbody></table></div>\n";
    }

    private static function image(string $alt, string $src): string
    {
        $src = html_entity_decode($src, ENT_QUOTES);
        // Images du site seulement (la politique de sécurité bloque les images externes).
        if (!preg_match('#^/[A-Za-z0-9/_.-]+$#', $src) || str_contains($src, '..')) {
            return '';
        }
        return '<figure><img src="' . e(url($src)) . '" alt="' . e($alt) . '" loading="lazy">'
            . ($alt !== '' ? '<figcaption>' . e($alt) . '</figcaption>' : '') . "</figure>\n";
    }

    private static function inline(string $text): string
    {
        $text = e($text);
        // Le code est mis de côté pour que * ou ** à l'intérieur d'une commande ne soient pas interprétés.
        $codes = [];
        $text = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codes) {
            $codes[] = '<code>' . $m[1] . '</code>';
            return "\x01" . (count($codes) - 1) . "\x01";
        }, $text);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])/', '<em>$1</em>', $text);
        // Liens : seulement http(s), mailto ou liens internes, pour éviter javascript:...
        $text = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $href = html_entity_decode($m[2], ENT_QUOTES);
            if (!preg_match('#^(https?://|mailto:|/|\#)#', $href)) {
                return $m[1];
            }
            if ($href[0] === '/') {
                $href = url($href);
            }
            $external = str_starts_with($href, 'http') ? ' rel="noopener"' : '';
            return '<a href="' . e($href) . '"' . $external . '>' . $m[1] . '</a>';
        }, $text);
        return preg_replace_callback("/\x01(\d+)\x01/", fn ($m) => $codes[(int) $m[1]], $text);
    }
}
