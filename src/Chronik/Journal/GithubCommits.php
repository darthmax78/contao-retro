<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Chronik\Journal;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Commits eines öffentlichen GitHub-Repositorys für einen Zeitraum (Chronik-Paket).
 *
 * Repository in .env.local: CHRONIK_GITHUB_REPO=besitzer/repo
 * Optional für private Repos oder viele Abfragen: CHRONIK_GITHUB_TOKEN=…
 * Ohne Repository oder ohne Netz: leere Liste, das Journal läuft trotzdem.
 */
final class GithubCommits
{
    /** @var array<string, list<array{zeit: int, hash: string, nachricht: string}>> */
    private array $cache = [];

    /** @var array{von: int, bis: int, commits: list<array{zeit: int, hash: string, nachricht: string}>}|null */
    private ?array $vorrat = null;

    public function __construct(private readonly ?HttpClientInterface $http = null)
    {
    }

    public function repo(): string
    {
        $repo = trim((string) ($_SERVER['CHRONIK_GITHUB_REPO'] ?? $_ENV['CHRONIK_GITHUB_REPO'] ?? getenv('CHRONIK_GITHUB_REPO') ?: ''));

        return preg_match('~^[\w.-]+/[\w.-]+$~', $repo) ? $repo : '';
    }

    /**
     * Lädt alle Commits eines längeren Zeitraums mit wenigen Abfragen vor. Danach beantwortet
     * commits() jeden Tag darin aus dem Speicher – wichtig beim rückwirkenden Erfassen, weil
     * GitHub ohne Token nur 60 Abfragen pro Stunde erlaubt.
     */
    public function vorladen(int $von, int $bis): void
    {
        $this->vorrat = null;
        $this->vorrat = ['von' => $von, 'bis' => $bis, 'commits' => $this->commits($von, $bis)];
    }

    /** @return list<array{zeit: int, hash: string, nachricht: string}> älteste zuerst */
    public function commits(int $von, int $bis): array
    {
        $repo = $this->repo();

        if ('' === $repo || null === $this->http) {
            return [];
        }

        if (null !== $this->vorrat && $von >= $this->vorrat['von'] && $bis <= $this->vorrat['bis']) {
            return array_values(array_filter(
                $this->vorrat['commits'],
                static fn (array $c): bool => $c['zeit'] >= $von && $c['zeit'] <= $bis,
            ));
        }

        $schluessel = $von.'-'.$bis;

        if (isset($this->cache[$schluessel])) {
            return $this->cache[$schluessel];
        }

        $kopf = ['Accept' => 'application/vnd.github+json', 'User-Agent' => 'contao-chronik'];
        $token = (string) ($_SERVER['CHRONIK_GITHUB_TOKEN'] ?? $_ENV['CHRONIK_GITHUB_TOKEN'] ?? '');

        if ('' !== $token) {
            $kopf['Authorization'] = 'Bearer '.$token;
        }

        $commits = [];

        try {
            for ($seite = 1; $seite <= 30; ++$seite) {
                $antwort = $this->http->request('GET', "https://api.github.com/repos/$repo/commits", [
                    'headers' => $kopf,
                    'timeout' => 15,
                    'query' => [
                        'since' => gmdate('Y-m-d\TH:i:s\Z', $von),
                        'until' => gmdate('Y-m-d\TH:i:s\Z', $bis),
                        'per_page' => 100,
                        'page' => $seite,
                    ],
                ]);

                $liste = $antwort->toArray();

                foreach ($liste as $c) {
                    $commits[] = [
                        'zeit' => (int) strtotime((string) ($c['commit']['committer']['date'] ?? $c['commit']['author']['date'] ?? '')),
                        'hash' => substr((string) ($c['sha'] ?? ''), 0, 7),
                        'nachricht' => strtok((string) ($c['commit']['message'] ?? ''), "\n") ?: '',
                    ];
                }

                if (\count($liste) < 100) {
                    break;
                }
            }
        } catch (\Throwable) {
            return $this->cache[$schluessel] = [];
        }

        usort($commits, static fn (array $a, array $b): int => $a['zeit'] <=> $b['zeit']);

        return $this->cache[$schluessel] = $commits;
    }
}
