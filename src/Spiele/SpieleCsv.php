<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

/**
 * Liest die CSV für den Spiele-Import (rg-25). Reine Logik ohne Datenbank.
 *
 * - Kopfzeile Pflicht, Spalten in beliebiger Reihenfolge, deutsche oder englische Namen.
 * - Trennzeichen Semikolon, Komma oder Tabulator (aus der Kopfzeile erkannt).
 * - UTF-8 (mit oder ohne BOM) oder Windows-1252 (Excel), Zeilenumbrüche in Anführungszeichen erlaubt.
 * - „datum“ (1989, 09.1989, 1.9.1989, 1989-09-01) füllt jahr/monat/tag, sofern diese leer sind.
 */
final class SpieleCsv
{
    /** Normalisierter Spaltenname => Feld */
    private const SPALTEN = [
        'name' => 'name', 'titel' => 'name', 'title' => 'name', 'spiel' => 'name',
        'geraet' => 'geraet', 'plattform' => 'geraet', 'platform' => 'geraet', 'system' => 'geraet', 'konsole' => 'geraet',
        'entwickler' => 'entwickler', 'developer' => 'entwickler',
        'publisher' => 'publisher', 'vertrieb' => 'publisher', 'herausgeber' => 'publisher',
        'jahr' => 'jahr', 'year' => 'jahr',
        'monat' => 'monat', 'month' => 'monat',
        'tag' => 'tag', 'day' => 'tag',
        'datum' => 'datum', 'date' => 'datum', 'erscheinungsdatum' => 'datum', 'release' => 'datum',
        'region' => 'region',
        'genre' => 'genre',
        'spieler' => 'spieler', 'players' => 'spieler',
        'modellnummer' => 'modellnummer', 'modell' => 'modellnummer', 'model' => 'modellnummer', 'katalognummer' => 'modellnummer',
        'modulkb' => 'modul_kb', 'modulgroesse' => 'modul_kb', 'modul' => 'modul_kb', 'romkb' => 'modul_kb',
        'beschreibung' => 'beschreibung', 'description' => 'beschreibung', 'notiz' => 'beschreibung',
        'screenshots' => 'screenshots', 'screenshot' => 'screenshots', 'bilder' => 'screenshots',
        'galerie' => 'galerie', 'gallery' => 'galerie',
        'sortierung' => 'sortierung', 'sort' => 'sortierung', 'reihenfolge' => 'sortierung',
        'quelle' => 'quelle_text', 'source' => 'quelle_text',
        'veroeffentlicht' => 'published', 'published' => 'published',
    ];

    /**
     * @return array{zeilen: list<array{zeile: int, werte: array<string, string>}>, spalten: list<string>, fehler: list<string>, hinweise: list<string>}
     */
    public function lesen(string $inhalt): array
    {
        $ergebnis = ['zeilen' => [], 'spalten' => [], 'fehler' => [], 'hinweise' => []];

        if (str_starts_with($inhalt, "\xEF\xBB\xBF")) {
            $inhalt = substr($inhalt, 3);
        } elseif (!mb_check_encoding($inhalt, 'UTF-8')) {
            $inhalt = mb_convert_encoding($inhalt, 'UTF-8', 'Windows-1252');
            $ergebnis['hinweise'][] = 'Datei war nicht UTF-8, als Windows-1252 (Excel) gelesen.';
        }

        $inhalt = str_replace(["\r\n", "\r"], "\n", $inhalt);

        if ('' === trim($inhalt)) {
            $ergebnis['fehler'][] = 'Die Datei ist leer.';

            return $ergebnis;
        }

        $kopfzeile = strtok($inhalt, "\n");
        $trenner = $this->trenner((string) $kopfzeile);

        $strom = fopen('php://temp', 'r+');
        fwrite($strom, $inhalt);
        rewind($strom);

        $kopf = fgetcsv($strom, null, $trenner, '"', '');
        $felder = [];

        foreach ($kopf ?: [] as $i => $spalte) {
            $norm = $this->normal((string) $spalte);
            $feld = self::SPALTEN[$norm] ?? null;

            if (null === $feld) {
                if ('' !== $norm) {
                    $ergebnis['hinweise'][] = sprintf('Spalte „%s“ ist unbekannt und wird ignoriert.', trim((string) $spalte));
                }
                continue;
            }

            if (\in_array($feld, $felder, true)) {
                $ergebnis['hinweise'][] = sprintf('Spalte „%s“ doppelt – nur die erste zählt.', trim((string) $spalte));
                continue;
            }

            $felder[$i] = $feld;
        }

        foreach (['name', 'geraet'] as $pflicht) {
            if (!\in_array($pflicht, $felder, true)) {
                $ergebnis['fehler'][] = sprintf('Pflichtspalte „%s“ fehlt in der Kopfzeile.', $pflicht);
            }
        }

        $ergebnis['spalten'] = array_values($felder);

        if ($ergebnis['fehler']) {
            fclose($strom);

            return $ergebnis;
        }

        $zeile = 1;

        while (false !== ($daten = fgetcsv($strom, null, $trenner, '"', ''))) {
            ++$zeile;

            if ([null] === $daten || '' === trim(implode('', array_map('strval', $daten)))) {
                continue;
            }

            $werte = [];

            foreach ($felder as $i => $feld) {
                $werte[$feld] = trim((string) ($daten[$i] ?? ''));
            }

            if (isset($werte['datum'])) {
                foreach ($this->datum($werte['datum']) as $teil => $wert) {
                    if ('' === ($werte[$teil] ?? '')) {
                        $werte[$teil] = $wert;
                    }
                }
                unset($werte['datum']);
            }

            $ergebnis['zeilen'][] = ['zeile' => $zeile, 'werte' => $werte];
            // Zeilenumbrüche in Anführungszeichen: Zeilennummer bleibt die der Startzeile, reicht als Hinweis
        }

        fclose($strom);

        if (!$ergebnis['zeilen']) {
            $ergebnis['fehler'][] = 'Unter der Kopfzeile stehen keine Spiele.';
        }

        return $ergebnis;
    }

    /** ja/nein-Spalten: true, false oder null (leer = nicht ändern). */
    public static function janein(string $wert): ?bool
    {
        $w = mb_strtolower(trim($wert));

        return match (true) {
            '' === $w => null,
            \in_array($w, ['1', 'ja', 'j', 'yes', 'y', 'x', 'true', 'wahr'], true) => true,
            default => false,
        };
    }

    /**
     * @return array<string, string> jahr/monat/tag, soweit erkennbar
     */
    public function datum(string $eingabe): array
    {
        $e = trim($eingabe);

        return match (true) {
            (bool) preg_match('/^(\d{4})$/', $e, $m) => ['jahr' => $m[1]],
            (bool) preg_match('/^(\d{4})-(\d{1,2})$/', $e, $m) => ['jahr' => $m[1], 'monat' => (string) (int) $m[2]],
            (bool) preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $e, $m) => ['jahr' => $m[1], 'monat' => (string) (int) $m[2], 'tag' => (string) (int) $m[3]],
            (bool) preg_match('/^(\d{1,2})[.\/](\d{4})$/', $e, $m) => ['jahr' => $m[2], 'monat' => (string) (int) $m[1]],
            (bool) preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/', $e, $m) => ['jahr' => $m[3], 'monat' => (string) (int) $m[2], 'tag' => (string) (int) $m[1]],
            default => [],
        };
    }

    private function trenner(string $kopfzeile): string
    {
        $anzahl = ['tab' => substr_count($kopfzeile, "\t"), ';' => substr_count($kopfzeile, ';'), ',' => substr_count($kopfzeile, ',')];
        arsort($anzahl);
        $trenner = (string) array_key_first($anzahl);

        return 'tab' === $trenner ? "\t" : $trenner;
    }

    private function normal(string $s): string
    {
        $s = mb_strtolower(strtr(trim($s), ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue']));

        return (string) preg_replace('/[^a-z0-9]/', '', $s);
    }
}
