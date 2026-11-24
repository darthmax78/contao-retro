<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Chronik\Journal;

use Doctrine\DBAL\Connection;

/**
 * Rückwirkende Übersicht: an welchen Tagen hat sich etwas getan? (Chronik-Paket)
 *
 * Eine Zeile je Tag mit Aktivität. Für die Zeit vor Ablauf der Fristen bleiben nur
 * veröffentlichte News, die „Spur“ (letzte Änderung je Datensatz) und Commits übrig –
 * dort ist die Liste eine Untergrenze.
 */
final class JournalUebersicht
{
    private const SPALTEN = [
        'datensaetze' => 'Datensätze',
        'neu' => 'davon neu',
        'geloescht' => 'Löschungen',
        'dateien' => 'Dateien',
        'news' => 'News veröff.',
        'commits' => 'Commits',
        'fehler' => 'Fehler',
        'spur' => 'Spur*',
    ];

    public function __construct(
        private readonly Connection $db,
        private readonly GithubCommits $github,
    ) {
    }

    /** @return array<string, array<string, int>> Tag (Y-m-d) => Kennzahlen, aufsteigend */
    public function tage(\DateTimeImmutable $von, \DateTimeImmutable $bis): array
    {
        $a = $von->setTime(0, 0)->getTimestamp();
        $b = $bis->setTime(23, 59, 59)->getTimestamp();
        $tage = [];
        $schema = $this->db->createSchemaManager();

        $zaehle = static function (array &$tage, iterable $zeiten, string $spalte, bool $eindeutig = false): void {
            $gesehen = [];

            foreach ($zeiten as $z) {
                $tag = date('Y-m-d', (int) $z['t']);

                if ($eindeutig) {
                    $k = $tag.'|'.$z['k'];

                    if (isset($gesehen[$k])) {
                        continue;
                    }

                    $gesehen[$k] = true;
                }

                $tage[$tag][$spalte] = ($tage[$tag][$spalte] ?? 0) + 1;
            }
        };

        $zaehle($tage, $this->db->iterateAssociative("SELECT tstamp AS t, CONCAT(fromTable, '#', pid) AS k FROM tl_version WHERE tstamp BETWEEN ? AND ?", [$a, $b]), 'datensaetze', true);
        $zaehle($tage, $this->db->iterateAssociative("SELECT tstamp AS t, CONCAT(fromTable, '#', pid) AS k FROM tl_version WHERE version = 1 AND tstamp BETWEEN ? AND ?", [$a, $b]), 'neu', true);
        $zaehle($tage, $this->db->iterateAssociative("SELECT tstamp AS t FROM tl_log WHERE text LIKE 'DELETE FROM %' AND tstamp BETWEEN ? AND ?", [$a, $b]), 'geloescht');
        $zaehle($tage, $this->db->iterateAssociative("SELECT tstamp AS t FROM tl_log WHERE action = 'ERROR' AND tstamp BETWEEN ? AND ?", [$a, $b]), 'fehler');
        $zaehle($tage, $this->db->iterateAssociative("SELECT tstamp AS t FROM tl_files WHERE type = 'file' AND tstamp BETWEEN ? AND ?", [$a, $b]), 'dateien');

        foreach (['tl_page', 'tl_article', 'tl_content', 'tl_news', 'tl_calendar_events'] as $tabelle) {
            if ($schema->tablesExist([$tabelle])) {
                $zaehle($tage, $this->db->iterateAssociative("SELECT tstamp AS t FROM $tabelle WHERE tstamp BETWEEN ? AND ?", [$a, $b]), 'spur');
            }
        }

        if ($schema->tablesExist(['tl_news'])) {
            $zaehle($tage, $this->db->iterateAssociative('SELECT date AS t FROM tl_news WHERE published = 1 AND date BETWEEN ? AND ?', [$a, $b]), 'news');
        }

        $zaehle($tage, array_map(static fn (array $c): array => ['t' => $c['zeit']], $this->github->commits($a, $b)), 'commits');

        ksort($tage);

        return $tage;
    }

    public function markdown(\DateTimeImmutable $von, \DateTimeImmutable $bis): string
    {
        $tage = $this->tage($von, $bis);
        $wt = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];

        $md = sprintf("# Änderungstage (%s bis %s)\n\n", $von->format('Y-m-d'), $bis->format('Y-m-d'));
        $md .= '| Datum | Tag | '.implode(' | ', self::SPALTEN)." |\n";
        $md .= '|---|---|'.str_repeat('---:|', \count(self::SPALTEN))."\n";

        foreach ($tage as $tag => $zahlen) {
            $zellen = array_map(static fn (string $k): string => (string) ($zahlen[$k] ?? '·'), array_keys(self::SPALTEN));
            $md .= sprintf("| %s | %s | %s |\n", $tag, $wt[(int) date('w', strtotime($tag))], implode(' | ', $zellen));
        }

        $md .= sprintf("\n%d Tage mit Aktivität.\n\n", \count($tage));
        $md .= "\\* Spur = Datensätze, deren *letzte* Änderung auf diesen Tag fällt. Bleibt erhalten, wenn Versionen\n";
        $md .= "und Log schon gelöscht sind – ältere Arbeitstage tauchen dann nur noch hier auf (Untergrenze).\n";

        return $md;
    }
}
