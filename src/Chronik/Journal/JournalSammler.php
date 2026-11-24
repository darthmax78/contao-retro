<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Chronik\Journal;

use Contao\StringUtil;
use Doctrine\DBAL\Connection;

/**
 * Sammelt die Spuren eines Tages aus der Contao-Datenbank (Chronik-Paket).
 *
 * Quellen:
 * - tl_version: jede Speicherung im Backend (verfällt nach „Speicherzeit für Versionen“)
 * - tl_log:     Löschungen und Fehler (verfällt nach „Speicherzeit für Log-Einträge“)
 * - tl_files:   hochgeladene/geänderte Dateien
 * - tstamp von Seiten, Artikeln, Inhaltselementen, News, Events: letzte Änderung („Spur“),
 *   bleibt auch nach Ablauf der Fristen erhalten
 * - tl_news.date: veröffentlichte News
 *
 * Optionale Tabellen (News, Kalender) werden nur abgefragt, wenn es sie gibt.
 */
final class JournalSammler
{
    /** @var array<string, bool> */
    private array $tabellen = [];

    /** @var array<string, string> */
    private array $ortCache = [];

    public function __construct(
        private readonly Connection $db,
        private readonly GithubCommits $github,
    ) {
    }

    /**
     * Ergebnis für einen Tag: Markdown, Kennzahlen und ob überhaupt etwas passiert ist.
     *
     * @return array{markdown: string, zahlen: array<string, int>, leer: bool}
     */
    public function tag(\DateTimeImmutable $tag): array
    {
        $von = $tag->setTime(0, 0)->getTimestamp();
        $bis = $tag->setTime(23, 59, 59)->getTimestamp();

        $zahlen = ['datensaetze' => 0, 'neu' => 0, 'geloescht' => 0, 'dateien' => 0, 'news' => 0, 'fehler' => 0, 'spur' => 0, 'commits' => 0];
        $abschnitte = [];

        // Veröffentlichte News
        if ($this->gibt('tl_news')) {
            $zeilen = $this->db->fetchAllAssociative('SELECT date, headline, published FROM tl_news WHERE date BETWEEN ? AND ? ORDER BY date', [$von, $bis]);
            $zahlen['news'] = \count($zeilen);
            $abschnitte['Veröffentlichte News'] = array_map(
                static fn (array $z): string => sprintf('- %s „%s“%s', date('H:i', (int) $z['date']), self::t($z['headline']), $z['published'] ? '' : ' (unveröffentlicht)'),
                $zeilen,
            );
        }

        // Speicherungen im Backend
        $zeilen = $this->db->fetchAllAssociative(
            'SELECT fromTable, pid, MIN(tstamp) AS zeit, MIN(version) AS erste, MAX(description) AS beschreibung, COUNT(*) AS anzahl
             FROM tl_version WHERE tstamp BETWEEN ? AND ? GROUP BY fromTable, pid ORDER BY MIN(tstamp)',
            [$von, $bis],
        );
        $zahlen['datensaetze'] = \count($zeilen);
        $liste = [];

        foreach ($zeilen as $z) {
            $neu = 1 === (int) $z['erste'];
            $zahlen['neu'] += $neu ? 1 : 0;
            $name = self::t($z['beschreibung']);
            $ort = '';

            if ('tl_content' === $z['fromTable']) {
                [$name2, $ort] = $this->inhaltselement((int) $z['pid']);
                $name = $name ?: $name2;
            }

            $liste[] = sprintf(
                '- %s %s %s #%d %s%s%s',
                date('H:i', (int) $z['zeit']),
                $neu ? '**neu**' : 'geändert',
                preg_replace('/^tl_/', '', (string) $z['fromTable']),
                (int) $z['pid'],
                $name,
                $ort ? ' – '.$ort : '',
                (int) $z['anzahl'] > 1 ? ' ('.$z['anzahl'].'×)' : '',
            );
        }

        $abschnitte['Gespeichert im Backend (aus den Versionen)'] = $liste;

        // Spuren: letzte Änderung fällt auf diesen Tag (gezählt wird je Datensatz)
        $seiten = $this->db->fetchAllAssociative('SELECT title, type, alias FROM tl_page WHERE tstamp BETWEEN ? AND ? ORDER BY sorting', [$von, $bis]);
        $artikel = $this->db->fetchAllAssociative('SELECT a.title, p.title AS seite FROM tl_article a LEFT JOIN tl_page p ON p.id = a.pid WHERE a.tstamp BETWEEN ? AND ? ORDER BY p.title, a.sorting', [$von, $bis]);
        $elemente = $this->db->fetchFirstColumn('SELECT id FROM tl_content WHERE tstamp BETWEEN ? AND ?', [$von, $bis]);
        $zahlen['spur'] = \count($seiten) + \count($artikel) + \count($elemente);

        $abschnitte['Zuletzt an diesem Tag bearbeitet: Seiten'] = array_map(
            static fn (array $z): string => sprintf('- „%s“ (%s, /%s)', self::t($z['title']), $z['type'], $z['alias']),
            $seiten,
        );
        $abschnitte['Zuletzt an diesem Tag bearbeitet: Artikel'] = array_map(
            static fn (array $z): string => sprintf('- „%s“ auf Seite „%s“', self::t($z['title']), self::t($z['seite'] ?? '?')),
            $artikel,
        );

        $nachOrt = [];

        foreach ($elemente as $id) {
            [$ueberschrift, $ort] = $this->inhaltselement((int) $id);
            $nachOrt[$ort ?: '(ohne Ort)'][] = $ueberschrift;
        }

        ksort($nachOrt);
        $abschnitte['Zuletzt an diesem Tag bearbeitet: Inhaltselemente (nach Ort)'] = array_map(
            static function (string $ort, array $titel): string {
                $anzahl = \count($titel);
                $titel = array_values(array_unique(array_filter($titel, static fn (string $t): bool => '' !== $t && !str_starts_with($t, '('))));
                sort($titel);

                return sprintf('- %s: %d Element%s%s', $ort, $anzahl, 1 === $anzahl ? '' : 'e', $titel ? ' – '.implode('; ', $titel) : '');
            },
            array_keys($nachOrt),
            $nachOrt,
        );

        if ($this->gibt('tl_news')) {
            $zeilen = $this->db->fetchAllAssociative('SELECT headline, date FROM tl_news WHERE tstamp BETWEEN ? AND ? ORDER BY date', [$von, $bis]);
            $zahlen['spur'] += \count($zeilen);
            $abschnitte['Zuletzt an diesem Tag bearbeitet: News'] = array_map(
                static fn (array $z): string => sprintf('- „%s“ (veröffentlicht %s)', self::t($z['headline']), date('d.m.Y', (int) $z['date'])),
                $zeilen,
            );
        }

        if ($this->gibt('tl_calendar_events')) {
            $zeilen = $this->db->fetchAllAssociative('SELECT title, startDate FROM tl_calendar_events WHERE tstamp BETWEEN ? AND ? ORDER BY startDate', [$von, $bis]);
            $zahlen['spur'] += \count($zeilen);
            $abschnitte['Zuletzt an diesem Tag bearbeitet: Kalender'] = array_map(
                static fn (array $z): string => sprintf('- „%s“ (%s)', self::t($z['title']), date('d.m.Y', (int) $z['startDate'])),
                $zeilen,
            );
        }

        // Löschungen, Dateien, Fehler
        $geloescht = [];

        foreach ($this->db->fetchAllAssociative("SELECT tstamp, text FROM tl_log WHERE tstamp BETWEEN ? AND ? AND text LIKE 'DELETE FROM %' ORDER BY tstamp", [$von, $bis]) as $z) {
            $tabelle = preg_match('/^DELETE FROM `?(\w+)`?/', (string) $z['text'], $m) ? $m[1] : '?';
            $geloescht[$tabelle][] = (int) $z['tstamp'];
            ++$zahlen['geloescht'];
        }

        $abschnitte['Gelöscht'] = array_map(
            static fn (string $t, array $zeiten): string => sprintf('- %s: %d Einträge (%s–%s)', $t, \count($zeiten), date('H:i', min($zeiten)), date('H:i', max($zeiten))),
            array_keys($geloescht),
            $geloescht,
        );

        $ordner = [];

        foreach ($this->db->fetchFirstColumn("SELECT path FROM tl_files WHERE type = 'file' AND tstamp BETWEEN ? AND ?", [$von, $bis]) as $pfad) {
            $o = \dirname((string) $pfad);
            $ordner[$o] = ($ordner[$o] ?? 0) + 1;
            ++$zahlen['dateien'];
        }

        ksort($ordner);
        $abschnitte['Dateien (nach Ordner)'] = array_map(
            static fn (string $o, int $n): string => sprintf('- %s: %d Datei%s', $o, $n, 1 === $n ? '' : 'en'),
            array_keys($ordner),
            $ordner,
        );

        $fehler = $this->db->fetchAllAssociative("SELECT tstamp, text FROM tl_log WHERE tstamp BETWEEN ? AND ? AND action = 'ERROR' ORDER BY tstamp LIMIT 20", [$von, $bis]);
        $zahlen['fehler'] = \count($fehler);
        $abschnitte['Fehler im Systemlog'] = array_map(
            static fn (array $z): string => sprintf('- %s %s', date('H:i', (int) $z['tstamp']), mb_substr(self::t($z['text']), 0, 200)),
            $fehler,
        );

        // Commits (optional, CHRONIK_GITHUB_REPO)
        $commits = $this->github->commits($von, $bis);
        $zahlen['commits'] = \count($commits);
        $abschnitte['Commits ('.$this->github->repo().')'] = array_map(
            static fn (array $c): string => sprintf('- %s `%s` %s', date('H:i', $c['zeit']), $c['hash'], $c['nachricht']),
            $commits,
        );

        $leer = 0 === $zahlen['datensaetze'] + $zahlen['geloescht'] + $zahlen['dateien'] + $zahlen['news'] + $zahlen['fehler'] + $zahlen['spur'] + $zahlen['commits'];

        $md = '## '.$tag->format('Y-m-d').' – '.self::datumLang($tag)."\n";

        foreach ($abschnitte as $titel => $zeilenListe) {
            if ($zeilenListe) {
                $md .= "\n### $titel\n\n".implode("\n", $zeilenListe)."\n";
            }
        }

        return ['markdown' => $md, 'zahlen' => $zahlen, 'leer' => $leer];
    }

    /** Kurzfassung für die Liste im Backend, z. B. „12 Datensätze (3 neu), 2 Commits“. */
    public static function zusammenfassung(array $zahlen): string
    {
        $teile = [];

        if ($zahlen['datensaetze'] ?? 0) {
            $teile[] = $zahlen['datensaetze'].' Datensätze'.(($zahlen['neu'] ?? 0) ? ' ('.$zahlen['neu'].' neu)' : '');
        }

        foreach (['news' => 'News', 'geloescht' => 'gelöscht', 'dateien' => 'Dateien', 'commits' => 'Commits', 'fehler' => 'Fehler', 'spur' => 'Spuren'] as $k => $wort) {
            if ($zahlen[$k] ?? 0) {
                $teile[] = $zahlen[$k].' '.$wort;
            }
        }

        return $teile ? implode(', ', $teile) : 'keine Änderungen';
    }

    public static function datumLang(\DateTimeInterface $d): string
    {
        $tage = ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'];
        $monate = ['Jänner', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];

        return sprintf('%s, %d. %s %s', $tage[(int) $d->format('w')], (int) $d->format('j'), $monate[(int) $d->format('n') - 1], $d->format('Y'));
    }

    /** @return array{0: string, 1: string} Überschrift (oder „(typ)“) und Ort */
    private function inhaltselement(int $id): array
    {
        $z = $this->db->fetchAssociative('SELECT type, headline, ptable, pid FROM tl_content WHERE id = ?', [$id]);

        if (!$z) {
            return ['', ''];
        }

        $kopf = StringUtil::deserialize($z['headline']);
        $ueberschrift = self::t(\is_array($kopf) ? ($kopf['value'] ?? '') : $kopf);

        return [$ueberschrift ?: '('.$z['type'].')', $this->ort((string) $z['ptable'], (int) $z['pid'])];
    }

    private function ort(string $ptable, int $pid): string
    {
        $schluessel = $ptable.'#'.$pid;

        if (isset($this->ortCache[$schluessel])) {
            return $this->ortCache[$schluessel];
        }

        $ort = match (true) {
            '' === $ptable, 'tl_article' === $ptable => ($a = $this->db->fetchAssociative('SELECT a.title, p.title AS seite FROM tl_article a LEFT JOIN tl_page p ON p.id = a.pid WHERE a.id = ?', [$pid]))
                ? sprintf('Seite „%s“ › %s', self::t($a['seite'] ?? '?'), self::t($a['title'])) : '',
            'tl_news' === $ptable && $this->gibt('tl_news') => ($t = $this->db->fetchOne('SELECT headline FROM tl_news WHERE id = ?', [$pid])) ? 'News „'.self::t($t).'“' : '',
            'tl_calendar_events' === $ptable && $this->gibt('tl_calendar_events') => ($t = $this->db->fetchOne('SELECT title FROM tl_calendar_events WHERE id = ?', [$pid])) ? 'Event „'.self::t($t).'“' : '',
            'tl_content' === $ptable => 'in Element #'.$pid,
            default => $ptable.' #'.$pid,
        };

        return $this->ortCache[$schluessel] = $ort;
    }

    private function gibt(string $tabelle): bool
    {
        return $this->tabellen[$tabelle] ??= $this->db->createSchemaManager()->tablesExist([$tabelle]);
    }

    private static function t(mixed $wert): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', StringUtil::decodeEntities((string) $wert)));
    }
}
