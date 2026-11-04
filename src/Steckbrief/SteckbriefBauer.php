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
        'id', 'tstamp', 'name', 'alias', 'typ', 'basis', 'modellnummer', 'neu_gegenueber_basis',
        'artikel', 'pruefstatus', 'geprueft_am', 'pruefnotiz', 'published',
    ];

    private const ROLLEN = [
        'cpu' => 'CPU', 'zusatz_cpu' => 'Zusatzprozessor', 'coprozessor' => 'Grafik-Coprozessor',
        'grafik' => 'Grafik', 'sound' => 'Sound', 'soc' => 'SoC',
    ];

    private const MEDIEN = [
        'modul' => 'Modul', 'karte' => 'Speicherkarte', 'kassette' => 'Kassette', 'diskette' => 'Diskette',
        'cd' => 'CD', 'dvd' => 'DVD', 'umd' => 'UMD', 'bluray' => 'Blu-ray', 'festspeicher' => 'Festspeicher',
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
            'Technik' => [
                ...$this->komponenten($daten['komponenten']),
                'RAM' => $this->speicher($g, 'ram'),
                'Video RAM' => $this->speicher($g, 'vram'),
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
                'Produktionsende' => $this->leerOder($g['produktionsende_jahr'] ?? null, 'strval'),
            ],
            'Besonderheiten' => [
                ...$this->weitere($g['weitere'] ?? null),
                'Besonderheiten' => $this->text($g['besonderheiten'] ?? ''),
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
