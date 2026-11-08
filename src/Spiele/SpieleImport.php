<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

use Darthmax\RetroBundle\EventListener\DataContainer\SpielListener;
use Doctrine\DBAL\Connection;

/**
 * Schreibt die Zeilen aus SpieleCsv in tl_retro_spiel (rg-25).
 *
 * - Schlüssel: Name + Gerät. Vorhandene Spiele werden aktualisiert, sonst neu angelegt.
 * - Leere Zellen ändern nichts an vorhandenen Spielen.
 * - Gerät nach Name, Alias oder ID; unbekannt = Zeile wird übersprungen.
 * - Entwickler/Publisher nach Name im Firmen-Katalog, fehlende Firmen werden angelegt.
 * - Screenshots: Pfade durch | getrennt, ohne „files/“ relativ zum Bildordner; höchstens 6;
 *   die Dateien müssen in der Dateiverwaltung (tl_files) bekannt sein.
 * - Probelauf: liest alles, schreibt nichts.
 */
final class SpieleImport
{
    /** @var array<string, int> */
    private array $geraete = [];

    /** @var array<string, int> */
    private array $firmen = [];

    /** @var list<string> */
    private array $neueFirmen = [];

    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param list<array{zeile: int, werte: array<string, string>}> $zeilen
     *
     * @return array{zeilen: list<array{zeile: int, name: string, geraet: string, aktion: string, hinweise: list<string>}>, neu: int, aktualisiert: int, fehler: int, neue_firmen: list<string>}
     */
    public function ausfuehren(array $zeilen, string $bildordner, bool $neueVeroeffentlichen, bool $probelauf): array
    {
        $this->laden();
        $bericht = ['zeilen' => [], 'neu' => 0, 'aktualisiert' => 0, 'fehler' => 0, 'neue_firmen' => []];
        $bildordner = trim(trim($bildordner), '/');
        $gesehen = [];

        if (!$probelauf) {
            $this->db->beginTransaction();
        }

        try {
            foreach ($zeilen as $z) {
                $w = $z['werte'];
                $eintrag = ['zeile' => $z['zeile'], 'name' => $w['name'] ?? '', 'geraet' => $w['geraet'] ?? '', 'aktion' => 'fehler', 'hinweise' => []];

                $geraetId = $this->geraete[$this->schluessel($w['geraet'] ?? '')] ?? 0;

                if ('' === ($w['name'] ?? '')) {
                    $eintrag['hinweise'][] = 'Kein Name – übersprungen.';
                } elseif (0 === $geraetId) {
                    $eintrag['hinweise'][] = sprintf('Gerät „%s“ nicht in der Geräte-Datenbank – übersprungen.', $w['geraet'] ?? '');
                } else {
                    $daten = $this->daten($w, $bildordner, $probelauf, $eintrag['hinweise']);
                    $key = $geraetId.'|'.mb_strtolower($w['name']);
                    $id = $gesehen[$key] ?? (int) $this->db->fetchOne(
                        'SELECT id FROM tl_retro_spiel WHERE geraet = ? AND LOWER(name) = LOWER(?) ORDER BY id LIMIT 1',
                        [$geraetId, $w['name']],
                    );

                    if (isset($gesehen[$key])) {
                        $eintrag['hinweise'][] = 'Steht mehrfach in der CSV – diese Zeile ergänzt die frühere.';
                    }

                    $daten['tstamp'] = time();

                    if (0 !== $id) {
                        $eintrag['aktion'] = 'aktualisiert';

                        if (!$probelauf && $id > 0) {
                            $this->db->update('tl_retro_spiel', $daten, ['id' => $id]);
                        }
                    } else {
                        $eintrag['aktion'] = 'neu';
                        $daten += ['name' => $w['name'], 'geraet' => $geraetId, 'published' => $neueVeroeffentlichen ? 1 : 0];

                        if (!$probelauf) {
                            $this->db->insert('tl_retro_spiel', $daten);
                            $id = (int) $this->db->lastInsertId();
                        } else {
                            $id = -\count($gesehen) - 1;
                        }
                    }

                    $gesehen[$key] = $id;
                }

                ++$bericht['fehler' === $eintrag['aktion'] ? 'fehler' : $eintrag['aktion']];
                $bericht['zeilen'][] = $eintrag;
            }

            if (!$probelauf) {
                $this->db->commit();
            }
        } catch (\Throwable $e) {
            if (!$probelauf) {
                $this->db->rollBack();
            }

            throw $e;
        }

        $bericht['neue_firmen'] = $this->neueFirmen;

        return $bericht;
    }

    /**
     * Spalten der Tabelle aus einer CSV-Zeile; leere Zellen fehlen.
     *
     * @param array<string, string> $w
     * @param list<string>          $hinweise
     *
     * @return array<string, mixed>
     */
    private function daten(array $w, string $bildordner, bool $probelauf, array &$hinweise): array
    {
        $d = [];

        foreach (['region' => 32, 'spieler' => 64, 'modellnummer' => 64, 'quelle_text' => 255] as $feld => $laenge) {
            if ('' !== ($w[$feld] ?? '')) {
                $d[$feld] = mb_substr($w[$feld], 0, $laenge);
            }
        }

        if ('' !== ($w['beschreibung'] ?? '')) {
            $d['beschreibung'] = $w['beschreibung'];
        }

        foreach (['jahr' => [1950, 2100], 'monat' => [1, 12], 'tag' => [1, 31], 'modul_kb' => [1, 1048576], 'sortierung' => [0, 999999]] as $feld => [$min, $max]) {
            $wert = $w[$feld] ?? '';

            if ('' === $wert) {
                continue;
            }

            if (!ctype_digit($wert) || (int) $wert < $min || (int) $wert > $max) {
                $hinweise[] = sprintf('%s „%s“ ungültig – nicht übernommen.', ucfirst(str_replace('_', ' ', $feld)), $wert);
                continue;
            }

            $d[$feld] = (int) $wert;
        }

        if ('' !== ($w['genre'] ?? '')) {
            $genre = Genres::erkennen($w['genre']);

            if (null === $genre) {
                $hinweise[] = sprintf('Genre „%s“ unbekannt – nicht übernommen.', $w['genre']);
            } else {
                $d['genre'] = $genre;
            }
        }

        foreach (['entwickler', 'publisher'] as $feld) {
            if ('' !== ($w[$feld] ?? '')) {
                $d[$feld] = $this->firma($w[$feld], $probelauf, $hinweise);
            }
        }

        foreach (['galerie', 'published'] as $feld) {
            $wert = SpieleCsv::janein($w[$feld] ?? '');

            if (null !== $wert) {
                $d[$feld] = $wert ? 1 : 0;
            }
        }

        if ('' !== ($w['screenshots'] ?? '')) {
            $uuids = [];

            foreach (preg_split('/\s*[|\n]\s*/', $w['screenshots']) ?: [] as $pfad) {
                if ('' === $pfad) {
                    continue;
                }

                $pfad = ltrim(str_replace('\\', '/', $pfad), '/');
                $voll = str_starts_with($pfad, 'files/') || '' === $bildordner ? $pfad : $bildordner.'/'.$pfad;
                $uuid = $this->db->fetchOne("SELECT uuid FROM tl_files WHERE path = ? AND type = 'file'", [$voll]);

                if (false === $uuid) {
                    $hinweise[] = sprintf('Screenshot „%s“ nicht in der Dateiverwaltung – hochgeladen und synchronisiert?', $voll);
                    continue;
                }

                if (\count($uuids) === SpielListener::MAX_SCREENSHOTS) {
                    $hinweise[] = sprintf('Mehr als %d Screenshots – „%s“ und folgende ignoriert.', SpielListener::MAX_SCREENSHOTS, $voll);
                    break;
                }

                $uuids[] = $uuid;
            }

            if ($uuids) {
                $d['screenshots'] = serialize($uuids);
            }
        }

        return $d;
    }

    /** @param list<string> $hinweise */
    private function firma(string $name, bool $probelauf, array &$hinweise): int
    {
        $schluessel = mb_strtolower(trim($name));

        if (!isset($this->firmen[$schluessel])) {
            if ($probelauf) {
                $this->firmen[$schluessel] = 0;
            } else {
                $this->db->insert('tl_retro_firma', ['tstamp' => time(), 'name' => mb_substr(trim($name), 0, 128)]);
                $this->firmen[$schluessel] = (int) $this->db->lastInsertId();
            }

            $this->neueFirmen[] = trim($name);
            $hinweise[] = sprintf('Firma „%s“ wird neu angelegt.', trim($name));
        }

        return $this->firmen[$schluessel];
    }

    private function laden(): void
    {
        $this->geraete = [];
        $this->firmen = [];
        $this->neueFirmen = [];

        foreach ($this->db->fetchAllAssociative('SELECT id, name, alias FROM tl_retro_geraet') as $g) {
            foreach ([(string) $g['id'], (string) $g['name'], (string) $g['alias']] as $k) {
                if ('' !== $k) {
                    $this->geraete[$this->schluessel($k)] ??= (int) $g['id'];
                }
            }
        }

        foreach ($this->db->fetchAllAssociative('SELECT id, name FROM tl_retro_firma ORDER BY id') as $f) {
            $this->firmen[mb_strtolower(trim((string) $f['name']))] ??= (int) $f['id'];
        }
    }

    private function schluessel(string $s): string
    {
        return mb_strtolower(trim($s));
    }
}
