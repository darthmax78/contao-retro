<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Steckbrief;

/**
 * Baut aus den Gerätedaten die Gruppen und Zeilen eines Steckbriefs.
 *
 * Reine Logik ohne Datenbank und ohne Contao. Für jede Angabe gilt:
 * Anzeigetext, sonst aus der Zahl erzeugt, sonst vom Basismodell geerbt.
 * Gruppen und Zeilen ohne Wert entfallen.
 */
class SteckbriefBauer
{
    /** Zusammengehörige Felder erben nur gemeinsam: Eine neue Zahl darf nicht den alten Text behalten. */
    private const PAARE = [
        ['ram_kb', 'ram_text'],
        ['vram_kb', 'vram_text'],
        ['rom_kb', 'rom_text'],
        ['aufloesung_x', 'aufloesung_y', 'bildrate_hz', 'aufloesung_text'],
        ['farben_anzeige', 'farben_palette', 'farben_text'],
        ['audio_kanaele', 'audio_text'],
        ['medium', 'medium_max_kb', 'medium_text'],
        ['breite_mm', 'hoehe_mm', 'tiefe_mm', 'abmessungen_text'],
        ['gewicht_g', 'gewicht_text'],
        ['laufzeit_std', 'laufzeit_text'],
        ['verkauft_stueck', 'verkauft_text'],
    ];

    /** Felder, die immer nur für das Gerät selbst gelten */
    private const NICHT_GEERBT = [
        'id', 'tstamp', 'name', 'alias', 'typ', 'basis', 'modelle', 'neu_gegenueber_basis',
        'produktionsende_jahr', 'produktionsende_monat', 'artikel', 'pruefstatus', 'geprueft_am', 'pruefnotiz', 'published',
    ];

    private const TYPEN = ['konsole' => 'Konsole', 'handheld' => 'Handheld', 'homecomputer' => 'Homecomputer', 'pda' => 'PDA'];

    private const ZUBEHOER = [
        'netzteil' => 'Netzteil', 'kabel' => 'Kabel', 'adapter' => 'Adapter', 'controller' => 'Controller',
        'speicher' => 'Speicher', 'tasche' => 'Tasche', 'schutz' => 'Schutz', 'sonstiges' => 'Sonstiges',
    ];

    private const ROLLEN = [
        'cpu' => 'CPU', 'zusatz_cpu' => 'Zusatzprozessor', 'coprozessor' => 'Grafik-Coprozessor',
        'grafik' => 'Grafik', 'sound' => 'Sound', 'soc' => 'SoC',
    ];

    private const MEDIEN = [
        'modul' => 'Modul', 'karte' => 'Speicherkarte', 'kassette' => 'Kassette', 'diskette' => 'Diskette',
        'cd' => 'CD', 'dvd' => 'DVD', 'umd' => 'UMD', 'bluray' => 'Blu-ray', 'festspeicher' => 'Festspeicher',
    ];

    private const MONATE = [
        1 => 'Jänner', 2 => 'Februar', 3 => 'März', 4 => 'April', 5 => 'Mai', 6 => 'Juni',
        7 => 'Juli', 8 => 'August', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Dezember',
    ];

    private const WAEHRUNGEN = [
        'USD' => 'US$', 'JPY' => '¥', 'DEM' => 'DM', 'ATS' => 'öS', 'EUR' => '€',
        'GBP' => '£', 'FRF' => 'FF', 'AUD' => 'A$', 'CHF' => 'CHF',
    ];

    private const REGIONEN = [
        'welt' => '', 'jp' => 'JP', 'us' => 'USA', 'eu' => 'EU', 'de' => 'DE',
        'at' => 'AT', 'uk' => 'UK', 'fr' => 'FR', 'au' => 'AU',
    ];

    /**
     * @param array{geraet: array<string, mixed>, basis: array<string, mixed>|null, firmen: list<array<string, mixed>>, komponenten: list<array<string, mixed>>, releases: list<array<string, mixed>>} $daten
     *
     * @return list<array{titel: string, zeilen: list<array{label: string, wert: string}>}>
     */
    public function baue(array $daten): array
    {
        $g = $this->mitBasis($daten['geraet'], $daten['basis']);

        $gruppen = [
            'Allgemein' => [
                'Hersteller' => $this->hersteller($daten['firmen']),
                'Erscheinungsjahr' => $this->erscheinungsjahr($daten['releases']),
                'Preis' => $this->preis($daten['releases']),
            ],
            'Modelle' => $this->modellZeilen($daten['geraet']['modelle'] ?? null),
            'Technik' => [
                ...$this->komponenten($daten['komponenten']),
                'RAM' => $this->speicher($g, 'ram'),
                'Video-RAM' => $this->speicher($g, 'vram'),
                'ROM' => $this->speicher($g, 'rom'),
                'Audio' => $this->audio($g),
            ],
            'konsole' === $daten['geraet']['typ'] || 'homecomputer' === $daten['geraet']['typ'] ? 'Bild' : 'Display' => [
                'Typ' => $this->text($g['display_text'] ?? ''),
                'Diagonale' => $this->leerOder($g['display_zoll'] ?? null, fn ($z) => $this->zahl($z).'"'),
                'Auflösung' => $this->aufloesung($g),
                'Farben' => $this->farben($g),
                'Sprites' => $this->text($g['sprites_text'] ?? ''),
            ],
            'Anschlüsse' => [
                'Medium' => $this->medium($g),
                'Mehrspieler' => $this->text($g['mehrspieler_text'] ?? ''),
                'Kopfhörer' => $this->text($g['kopfhoerer_text'] ?? ''),
                'Weitere' => $this->text($g['anschluesse_text'] ?? ''),
            ],
            'Bedienung und System' => [
                'Eingabe' => $this->text($g['eingabe_text'] ?? ''),
                'System' => $this->text($g['system_text'] ?? ''),
            ],
            'Gehäuse' => [
                'Abmessungen' => $this->abmessungen($g),
                'Gewicht' => $this->mitEinheit($g, 'gewicht_text', 'gewicht_g', ' g'),
                'Stromversorgung' => $this->text($g['strom_text'] ?? ''),
                'Laufzeit' => $this->mitEinheit($g, 'laufzeit_text', 'laufzeit_std', ' Stunden'),
            ],
            'Markt' => [
                'Verkaufte Einheiten' => $this->verkauft($g),
                'Produktionsende' => $this->produktionsende($g),
            ],
            'Besonderheiten' => [
                ...$this->weitere($g['weitere'] ?? null),
                'Merkmale' => $this->text($g['besonderheiten'] ?? ''),
                'Neu gegenüber '.($daten['basis']['name'] ?? 'dem Basismodell') => $this->text($g['neu_gegenueber_basis'] ?? ''),
            ],
        ];

        $ergebnis = [];

        foreach ($gruppen as $titel => $zeilen) {
            $zeilen = array_filter($zeilen, static fn (string $wert): bool => '' !== $wert);

            if ($zeilen) {
                $ergebnis[] = [
                    'titel' => (string) $titel,
                    'zeilen' => array_map(
                        static fn ($label, string $wert): array => ['label' => (string) $label, 'wert' => $wert],
                        array_keys($zeilen),
                        $zeilen,
                    ),
                ];
            }
        }

        return $ergebnis;
    }

    /**
     * Titelzeile über der Tabelle.
     *
     * @param array{geraet: array<string, mixed>, firmen: list<array<string, mixed>>, releases: list<array<string, mixed>>} $daten
     *
     * @return array{kicker: string, titel: string, nummern: list<string>}
     */
    public function kopf(array $daten): array
    {
        $name = (string) $daten['geraet']['name'];
        $firmen = array_filter($daten['firmen'], static fn (array $f): bool => 'hersteller' === $f['rolle']);
        $hersteller = (string) (reset($firmen)['name'] ?? '');

        // Heißt ein Gerät schon „Atari Lynx“, soll der Hersteller nicht doppelt erscheinen.
        $titel = '' === $hersteller || str_starts_with($name, $hersteller) ? $name : $hersteller.' '.$name;

        return [
            'kicker' => implode(' · ', array_filter([
                self::TYPEN[$daten['geraet']['typ']] ?? '',
                $this->erscheinungsjahr($daten['releases']),
            ])),
            'titel' => $titel,
            'nummern' => array_values(array_filter(array_map(
                static fn (array $m): string => trim((string) ($m['nummer'] ?? '')),
                $this->liste($daten['geraet']['modelle'] ?? null),
            ))),
        ];
    }

    /**
     * Verteilt die Gruppen auf zwei Spalten, ohne eine Gruppe zu trennen und ohne die
     * Reihenfolge zu ändern. Jede Gruppe zählt mit ihren Zeilen plus Gruppenzeile.
     *
     * @param list<array{titel: string, zeilen: list<array{label: string, wert: string}>}> $gruppen
     *
     * @return list<list<array{titel: string, zeilen: list<array{label: string, wert: string}>}>>
     */
    public function spalten(array $gruppen): array
    {
        if (\count($gruppen) < 2) {
            return [$gruppen];
        }

        $gewichte = array_map(static fn (array $g): int => \count($g['zeilen']) + 1, $gruppen);
        $gesamt = array_sum($gewichte);
        $links = 0;
        $schnitt = 1;
        $bester = PHP_INT_MAX;

        for ($i = 0; $i < \count($gruppen) - 1; ++$i) {
            $links += $gewichte[$i];
            $abstand = abs($gesamt - 2 * $links);

            if ($abstand < $bester) {
                $bester = $abstand;
                $schnitt = $i + 1;
            }
        }

        return [\array_slice($gruppen, 0, $schnitt), \array_slice($gruppen, $schnitt)];
    }

    /**
     * Unterschiede zwischen zwei fertig gebauten Steckbriefen (rg-22), z. B. Revision gegen Basismodell.
     *
     * Verglichen wird Zeile für Zeile über Gruppe + Bezeichnung, mit genau der Formatierung des
     * Steckbriefs. Ausgegeben werden nur Zeilen mit unterschiedlichem Wert, in der Reihenfolge des
     * neuen Geräts; Zeilen, die nur das alte Gerät hat, folgen am Ende ihrer Gruppe. Die Modellliste
     * und der Freitext „Neu gegenüber …“ werden nicht verglichen.
     *
     * @param list<array{titel: string, zeilen: list<array{label: string, wert: string}>}> $neu
     * @param list<array{titel: string, zeilen: list<array{label: string, wert: string}>}> $alt
     *
     * @return list<array{gruppe: string, label: string, alt: string, neu: string}>
     */
    public function unterschiede(array $neu, array $alt): array
    {
        $index = static function (array $gruppen): array {
            $werte = [];

            foreach ($gruppen as $gruppe) {
                if ('Modelle' === $gruppe['titel']) {
                    continue;
                }

                foreach ($gruppe['zeilen'] as $zeile) {
                    if (str_starts_with($zeile['label'], 'Neu gegenüber')) {
                        continue;
                    }

                    $werte[$gruppe['titel']][$zeile['label']] = $zeile['wert'];
                }
            }

            return $werte;
        };

        $a = $index($alt);
        $n = $index($neu);
        $ergebnis = [];

        foreach (array_unique([...array_keys($n), ...array_keys($a)]) as $gruppe) {
            foreach (array_unique([...array_keys($n[$gruppe] ?? []), ...array_keys($a[$gruppe] ?? [])]) as $label) {
                $wertAlt = $a[$gruppe][$label] ?? '';
                $wertNeu = $n[$gruppe][$label] ?? '';

                if ($this->vergleichbar($wertAlt) !== $this->vergleichbar($wertNeu)) {
                    $ergebnis[] = ['gruppe' => (string) $gruppe, 'label' => (string) $label, 'alt' => $wertAlt, 'neu' => $wertNeu];
                }
            }
        }

        return $ergebnis;
    }

    /**
     * @param array<string, mixed> $teil
     * @param array<int, string>   $geraete
     */
    private function passt(array $teil, array $geraete): string
    {
        if (\count($geraete) < 2) {
            return '';
        }

        $ids = @unserialize((string) ($teil['geraete'] ?? ''), ['allowed_classes' => false]);
        $ids = \is_array($ids) ? array_map('intval', $ids) : [];
        $namen = array_values(array_filter($geraete, static fn (int $id): bool => \in_array($id, $ids, true), ARRAY_FILTER_USE_KEY));

        if (\count($namen) === \count($geraete)) {
            return 2 === \count($geraete) ? 'beide' : 'alle';
        }

        return implode(', ', $namen);
    }

    /** Leerraum und Zeilenumbrüche zählen beim Vergleich nicht. */
    private function vergleichbar(string $wert): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $wert));
    }

    /**
     * Zeilen der Zubehör-Tabelle, nach Art und Name sortiert.
     *
     * Mit $geraete (ID => Name, mehr als ein Gerät) bekommt jede Zeile unter „passt“ die Namen der
     * gewählten Geräte, zu denen das Teil gehört – „alle“ bzw. „beide“, wenn es zu allen passt.
     *
     * @param list<array<string, mixed>> $teile
     * @param array<int, string>         $geraete
     *
     * @return list<array{name: string, modell: string, art: string, daten: list<string>, passt: string}>
     */
    public function zubehoer(array $teile, array $geraete = []): array
    {
        $reihenfolge = array_flip(array_keys(self::ZUBEHOER));

        usort($teile, static fn (array $a, array $b): int => [$reihenfolge[$a['art']] ?? 99, $a['name']] <=> [$reihenfolge[$b['art']] ?? 99, $b['name']]);

        return array_map(function (array $t) use ($geraete): array {
            $von = (int) ($t['marktstart_jahr'] ?? 0);
            $bis = (int) ($t['produktionsende_jahr'] ?? 0);
            $zeitraum = match (true) {
                $von > 0 && $bis > 0 => $von.'–'.$bis,
                $von > 0 => 'ab '.$von,
                $bis > 0 => 'bis '.$bis,
                default => '',
            };

            return [
                'name' => (string) $t['name'],
                'modell' => $this->text($t['modellnummer'] ?? ''),
                'art' => self::ZUBEHOER[$t['art']] ?? (string) $t['art'],
                'daten' => array_values(array_filter([
                    $this->text($t['daten_text'] ?? ''),
                    $zeitraum,
                    ...explode("\n", $this->text($t['beschreibung'] ?? '')),
                ], static fn (string $zeile): bool => '' !== trim($zeile))),
                'passt' => $this->passt($t, $geraete),
            ];
        }, $teile);
    }

    /**
     * @param array<string, mixed>      $geraet
     * @param array<string, mixed>|null $basis
     *
     * @return array<string, mixed>
     */
    public function mitBasis(array $geraet, array|null $basis): array
    {
        if (null === $basis) {
            return $geraet;
        }

        $inPaaren = array_merge(...self::PAARE);

        foreach ($basis as $feld => $wert) {
            if (!\in_array($feld, self::NICHT_GEERBT, true) && !\in_array($feld, $inPaaren, true) && $this->leer($geraet[$feld] ?? null)) {
                $geraet[$feld] = $wert;
            }
        }

        foreach (self::PAARE as $paar) {
            $eigene = array_filter($paar, fn (string $feld): bool => !$this->leer($geraet[$feld] ?? null));

            if (!$eigene) {
                foreach ($paar as $feld) {
                    $geraet[$feld] = $basis[$feld] ?? null;
                }
            }
        }

        return $geraet;
    }

    /** 0 ist ein echter Wert, nur NULL, '' und leere Listen gelten als leer. */
    private function leer(mixed $wert): bool
    {
        if (null === $wert || '' === $wert) {
            return true;
        }

        // Eine leere Wizard-Zeile wird als Liste mit leeren Texten gespeichert.
        if (!\is_string($wert) || !str_starts_with($wert, 'a:')) {
            return false;
        }

        $inhalt = '';
        $liste = $this->liste($wert);

        array_walk_recursive($liste, static function ($teil) use (&$inhalt): void {
            $inhalt .= trim((string) $teil);
        });

        return '' === $inhalt;
    }

    /**
     * @return array<mixed>
     */
    private function liste(mixed $wert): array
    {
        if (!\is_string($wert) || !str_starts_with($wert, 'a:')) {
            return [];
        }

        $liste = @unserialize($wert, ['allowed_classes' => false]);

        return \is_array($liste) ? $liste : [];
    }

    private function text(mixed $wert): string
    {
        return trim((string) $wert);
    }

    private function leerOder(mixed $wert, callable $format): string
    {
        return null === $wert || '' === $wert ? '' : (string) $format($wert);
    }

    /** Deutsche Zahl: Tausenderpunkt, Komma, ohne überflüssige Nullen. */
    public function zahl(mixed $wert): string
    {
        $zahl = (float) $wert;
        $stellen = 0;

        while ($stellen < 4 && round($zahl, $stellen) !== $zahl) {
            ++$stellen;
        }

        return number_format($zahl, $stellen, ',', '.');
    }

    private function kilobyte(mixed $kb): string
    {
        $kb = (float) $kb;

        return match (true) {
            $kb >= 1048576 => $this->zahl($kb / 1048576).' GB',
            $kb >= 1024 => $this->zahl($kb / 1024).' MB',
            $kb < 1 && $kb > 0 => $this->zahl($kb * 1024).' Byte',
            default => $this->zahl($kb).' KB',
        };
    }

    /**
     * @param array<string, mixed> $g
     */
    private function speicher(array $g, string $feld): string
    {
        return $this->text($g[$feld.'_text'] ?? '') ?: $this->leerOder($g[$feld.'_kb'] ?? null, $this->kilobyte(...));
    }

    /**
     * @param array<string, mixed> $g
     */
    private function audio(array $g): string
    {
        return $this->text($g['audio_text'] ?? '') ?: $this->leerOder(
            $g['audio_kanaele'] ?? null,
            static fn ($k): string => $k.(1 === (int) $k ? ' Kanal' : ' Kanäle'),
        );
    }

    /**
     * @param array<string, mixed> $g
     */
    private function aufloesung(array $g): string
    {
        if ('' !== $text = $this->text($g['aufloesung_text'] ?? '')) {
            return $text;
        }

        if (empty($g['aufloesung_x']) || empty($g['aufloesung_y'])) {
            return '';
        }

        $wert = $g['aufloesung_x'].' × '.$g['aufloesung_y'].' Pixel';

        return empty($g['bildrate_hz']) ? $wert : $wert.', bis '.$this->zahl($g['bildrate_hz']).' Hz';
    }

    /**
     * @param array<string, mixed> $g
     */
    private function farben(array $g): string
    {
        if ('' !== $text = $this->text($g['farben_text'] ?? '')) {
            return $text;
        }

        $anzeige = $g['farben_anzeige'] ?? null;
        $palette = $g['farben_palette'] ?? null;

        return match (true) {
            null !== $anzeige && null !== $palette => $this->zahl($anzeige).' aus '.$this->zahl($palette),
            null !== $anzeige => $this->zahl($anzeige),
            null !== $palette => 'Palette mit '.$this->zahl($palette),
            default => '',
        };
    }

    /**
     * @param array<string, mixed> $g
     */
    private function medium(array $g): string
    {
        if ('' !== $text = $this->text($g['medium_text'] ?? '')) {
            return $text;
        }

        $namen = array_map(static fn ($m): string => self::MEDIEN[$m] ?? (string) $m, $this->liste($g['medium'] ?? null));
        $wert = implode(', ', $namen);

        if ('' !== $wert && !empty($g['medium_max_kb'])) {
            $wert .= ', max. '.$this->kilobyte($g['medium_max_kb']);
        }

        return $wert;
    }

    /**
     * @param array<string, mixed> $g
     */
    private function abmessungen(array $g): string
    {
        if ('' !== $text = $this->text($g['abmessungen_text'] ?? '')) {
            return $text;
        }

        $masse = array_filter([$g['breite_mm'] ?? null, $g['hoehe_mm'] ?? null, $g['tiefe_mm'] ?? null], static fn ($m): bool => null !== $m && '' !== $m);

        return 3 === \count($masse) ? implode(' × ', array_map($this->zahl(...), $masse)).' mm' : '';
    }

    /**
     * @param array<string, mixed> $g
     */
    private function mitEinheit(array $g, string $textFeld, string $zahlFeld, string $einheit): string
    {
        return $this->text($g[$textFeld] ?? '') ?: $this->leerOder($g[$zahlFeld] ?? null, fn ($z): string => $this->zahl($z).$einheit);
    }

    /**
     * @param array<string, mixed> $g
     */
    private function verkauft(array $g): string
    {
        return $this->text($g['verkauft_text'] ?? '') ?: $this->leerOder(
            $g['verkauft_stueck'] ?? null,
            fn ($s): string => (float) $s >= 1000000 ? $this->zahl((float) $s / 1000000).' Mio.' : $this->zahl($s),
        );
    }

    /**
     * @param array<string, mixed> $g
     */
    private function produktionsende(array $g): string
    {
        $jahr = (int) ($g['produktionsende_jahr'] ?? 0);
        $monat = self::MONATE[(int) ($g['produktionsende_monat'] ?? 0)] ?? '';

        return $jahr > 0 ? trim($monat.' '.$jahr) : '';
    }

    /**
     * @return array<string, string>
     */
    private function weitere(mixed $wert): array
    {
        $zeilen = [];

        foreach ($this->liste($wert) as $zeile) {
            $label = trim((string) ($zeile['bezeichnung'] ?? ''));
            $text = trim((string) ($zeile['wert'] ?? ''));

            if ('' !== $label && '' !== $text) {
                $zeilen[$label] = $text;
            }
        }

        return $zeilen;
    }

    /**
     * Nummern ohne Variante und Region stehen nur in der Titelzeile.
     *
     * @return array<string, string>
     */
    private function modellZeilen(mixed $wert): array
    {
        $zeilen = [];

        foreach ($this->liste($wert) as $modell) {
            $nummer = trim((string) ($modell['nummer'] ?? ''));
            $variante = trim((string) ($modell['variante'] ?? ''));
            $region = trim((string) ($modell['region'] ?? ''));

            if ('' === $nummer || ('' === $variante && '' === $region)) {
                continue;
            }

            $zeilen[$nummer] = '' === $variante ? $region : ('' === $region ? $variante : $variante.' ('.$region.')');
        }

        return $zeilen;
    }

    /**
     * @param list<array<string, mixed>> $firmen
     */
    private function hersteller(array $firmen): string
    {
        $nach = static fn (string $rolle): string => implode(', ', array_column(
            array_filter($firmen, static fn (array $f): bool => $rolle === $f['rolle']),
            'name',
        ));

        $teile = array_filter([
            $nach('hersteller'),
            '' !== $nach('entwicklung') ? 'Entwicklung: '.$nach('entwicklung') : '',
            '' !== $nach('vertrieb') ? 'Vertrieb: '.$nach('vertrieb') : '',
        ]);

        $erster = array_shift($teile) ?? '';

        return $teile ? $erster.' ('.implode(', ', $teile).')' : $erster;
    }

    /**
     * @param list<array<string, mixed>> $releases
     */
    private function erscheinungsjahr(array $releases): string
    {
        $jahre = array_column(array_filter($releases, static fn (array $r): bool => 'marktstart' === $r['art']), 'jahr');

        return $jahre ? (string) min($jahre) : '';
    }

    /**
     * @param list<array<string, mixed>> $releases
     */
    private function preis(array $releases): string
    {
        $teile = [];

        foreach ($releases as $r) {
            $betrag = $this->text($r['preis_text'] ?? '');

            if ('' === $betrag && null !== ($r['preis'] ?? null)) {
                $betrag = $this->zahl($r['preis']).' '.(self::WAEHRUNGEN[$r['waehrung']] ?? $r['waehrung']);
            }

            if ('' === trim($betrag)) {
                continue;
            }

            // Eine Preisänderung trägt ihr Jahr, damit sie nicht als Startpreis gelesen wird.
            $zusatz = array_filter([
                self::REGIONEN[$r['region']] ?? '',
                'marktstart' === $r['art'] ? '' : (string) $r['jahr'],
            ]);

            $teile[] = trim($betrag).($zusatz ? ' ('.implode(', ', $zusatz).')' : '');
        }

        return implode('; ', $teile);
    }

    /**
     * @param list<array<string, mixed>> $komponenten
     *
     * @return array<string, string>
     */
    private function komponenten(array $komponenten): array
    {
        $zeilen = [];

        foreach ($komponenten as $k) {
            $label = self::ROLLEN[$k['rolle']] ?? (string) $k['rolle'];
            $wert = $this->text($k['anzeige_text'] ?? '');

            if ('' === $wert) {
                $wert = (string) $k['name'];

                if (null !== ($k['takt_mhz'] ?? null)) {
                    $wert .= ', '.$this->zahl($k['takt_mhz']).' MHz';
                }
            }

            // Mehrere Chips mit derselben Rolle teilen sich eine Zeile.
            $zeilen[$label] = isset($zeilen[$label]) ? $zeilen[$label].'; '.$wert : $wert;
        }

        return $zeilen;
    }
}
