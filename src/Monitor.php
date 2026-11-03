<?php
// Supervision du site : disponibilité, temps de réponse, certificat HTTPS et dernière publication.
// Les vérifications sont lancées par bin/monitor.php (tâche planifiée) ou par le bouton
// « Vérifier maintenant » du tableau de bord. L'historique est gardé dans data/monitoring.json.

declare(strict_types=1);

final class Monitor
{
    private const GITHUB_CACHE = 3600;   // l'API GitHub est interrogée au plus une fois par heure
    private const CERT_CACHE = 6 * 3600; // le certificat change rarement : vérifié toutes les 6 heures

    private array $cfg;

    public function __construct(private JsonStore $store, array $config)
    {
        $this->cfg = $config['monitor'];
    }

    // Lance une vérification complète et l'ajoute à l'historique.
    public function run(bool $force = false): array
    {
        $check = $this->checkHttp($this->cfg['url']);
        $data = $this->store->read('monitoring');
        $now = time();

        if ($force || $now - ($data['certificat']['verifie'] ?? 0) > self::CERT_CACHE) {
            $data['certificat'] = $this->checkCertificate($this->cfg['url']) + ['verifie' => $now];
        }
        if ($force || $now - ($data['publication']['verifie'] ?? 0) > self::GITHUB_CACHE) {
            $data['publication'] = $this->lastPublication() + ['verifie' => $now];
        }

        $this->store->update('monitoring', function (array $current) use ($check, $data): array {
            $current['certificat'] = $data['certificat'];
            $current['publication'] = $data['publication'];
            $current['controles'][] = $check;
            $limit = time() - $this->cfg['keep_days'] * 86400;
            $current['controles'] = array_values(array_filter($current['controles'], fn ($c) => $c['t'] >= $limit));
            return $current;
        });
        return $check;
    }

    // Disponibilité et temps de réponse : une vraie requête HTTP, comme un visiteur.
    public function checkHttp(string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => $this->cfg['timeout'],
            CURLOPT_USERAGENT => 'Supervision-Portfolio/1.0 (+bin/monitor.php)',
        ]);
        curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $ms = (int) round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000);
        $error = curl_errno($ch) ? curl_error($ch) : null;
        curl_close($ch);

        $ok = $error === null && $code >= 200 && $code < 400;
        return [
            't' => time(),
            'ok' => $ok,
            'code' => $code,
            'ms' => $ok ? $ms : null,
            'erreur' => $ok ? null : ($error ?? "Réponse HTTP $code"),
        ];
    }

    // Lit le certificat HTTPS présenté par le serveur : émetteur et date d'expiration.
    public function checkCertificate(string $url): array
    {
        $host = parse_url($url, PHP_URL_HOST);
        if (parse_url($url, PHP_URL_SCHEME) !== 'https' || !$host) {
            return ['ok' => false, 'erreur' => "Le site n'utilise pas HTTPS."];
        }
        $context = stream_context_create(['ssl' => ['capture_peer_cert' => true, 'SNI_enabled' => true, 'peer_name' => $host]]);
        $socket = @stream_socket_client("ssl://$host:443", $errno, $errstr, $this->cfg['timeout'], STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            return ['ok' => false, 'erreur' => "Connexion TLS impossible : $errstr"];
        }
        $cert = openssl_x509_parse(stream_context_get_params($socket)['options']['ssl']['peer_certificate']);
        fclose($socket);
        return [
            'ok' => true,
            'emetteur' => $cert['issuer']['O'] ?? $cert['issuer']['CN'] ?? 'inconnu',
            'domaine' => $cert['subject']['CN'] ?? $host,
            'expire' => $cert['validTo_time_t'],
        ];
    }

    // Dernière publication réussie (workflow GitHub Actions) et dernier changement du code.
    public function lastPublication(): array
    {
        $repo = $this->cfg['github_repo'] ?? null;
        if (!$repo) {
            return ['ok' => false, 'erreur' => 'Aucun dépôt GitHub configuré.'];
        }
        $result = ['ok' => true, 'depot' => $repo];

        $commit = $this->github("repos/$repo/commits?per_page=1");
        if (isset($commit[0])) {
            $result['commit'] = [
                'date' => strtotime($commit[0]['commit']['committer']['date']),
                'message' => strtok($commit[0]['commit']['message'], "\n"),
                'lien' => $commit[0]['html_url'],
            ];
        }
        if (!empty($this->cfg['github_workflow'])) {
            $runs = $this->github("repos/$repo/actions/workflows/{$this->cfg['github_workflow']}/runs?status=success&per_page=1");
            if (isset($runs['workflow_runs'][0])) {
                $run = $runs['workflow_runs'][0];
                $result['publication'] = ['date' => strtotime($run['updated_at']), 'lien' => $run['html_url']];
            }
        }
        if (!isset($result['commit']) && !isset($result['publication'])) {
            return ['ok' => false, 'erreur' => "GitHub n'a pas répondu (dépôt privé ou limite de requêtes atteinte)."];
        }
        return $result;
    }

    private function github(string $endpoint): ?array
    {
        $ch = curl_init('https://api.github.com/' . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->cfg['timeout'],
            CURLOPT_USERAGENT => 'Supervision-Portfolio/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github+json'],
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        return $code === 200 && is_string($body) ? json_decode($body, true) : null;
    }

    // Résumé pour le tableau de bord.
    public function report(): array
    {
        $data = $this->store->read('monitoring');
        $checks = $data['controles'] ?? [];
        $last = $checks ? $checks[count($checks) - 1] : null;
        $now = time();

        $uptime = function (int $seconds) use ($checks, $now): ?float {
            $window = array_filter($checks, fn ($c) => $c['t'] >= $now - $seconds);
            if (!$window) {
                return null;
            }
            $ok = count(array_filter($window, fn ($c) => $c['ok']));
            return round(100 * $ok / count($window), 2);
        };

        // Temps de réponse moyen par heure sur les dernières 24 heures.
        $hours = [];
        for ($i = 23; $i >= 0; $i--) {
            $hours[date('Y-m-d H:00', $now - $i * 3600)] = [];
        }
        foreach ($checks as $c) {
            $key = date('Y-m-d H:00', $c['t']);
            if (isset($hours[$key]) && $c['ms'] !== null) {
                $hours[$key][] = $c['ms'];
            }
        }
        $latency = array_map(fn ($v) => $v ? (int) round(array_sum($v) / count($v)) : null, $hours);
        $day = array_filter($checks, fn ($c) => $c['t'] >= $now - 86400 && $c['ms'] !== null);

        // Incidents : suites de vérifications en échec.
        $incidents = [];
        $open = null;
        foreach ($checks as $c) {
            if (!$c['ok'] && $open === null) {
                $open = ['debut' => $c['t'], 'fin' => null, 'erreur' => $c['erreur']];
            } elseif ($c['ok'] && $open !== null) {
                $open['fin'] = $c['t'];
                $incidents[] = $open;
                $open = null;
            }
        }
        if ($open !== null) {
            $incidents[] = $open;
        }

        $cert = $data['certificat'] ?? null;
        if (!empty($cert['ok'])) {
            $cert['jours_restants'] = (int) floor(($cert['expire'] - $now) / 86400);
        }

        return [
            'url' => $this->cfg['url'],
            'dernier' => $last,
            'etat' => $last === null ? 'inconnu' : (!$last['ok'] ? 'panne' : ($last['ms'] > $this->cfg['slow_ms'] ? 'lent' : 'ok')),
            'dispo' => ['24 h' => $uptime(86400), '7 jours' => $uptime(7 * 86400), '30 jours' => $uptime(30 * 86400)],
            'latence_moyenne' => $day ? (int) round(array_sum(array_column($day, 'ms')) / count($day)) : null,
            'latence' => $latency,
            'incidents' => array_reverse(array_slice($incidents, -10)),
            'certificat' => $cert,
            'publication' => $data['publication'] ?? null,
            'nb_controles' => count($checks),
        ];
    }
}
