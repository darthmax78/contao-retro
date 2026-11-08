<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Timeline;

/**
 * Baut aus News- und manuellen Einträgen die Folge der Timeline (rg-27).
 *
 * Eingabe: Einträge mit typ (eintrag, meilenstein, pause), zeit (Unix-Zeit), datum (Anzeige),
 * titel, text, tags, url. Ausgabe: dieselben Einträge plus Jahreszahlen (art = jahr) und
 * automatisch erkannte Pausen, in der gewünschten Reihenfolge und auf die Anzahl gekürzt.
 *
 * Bewusst ohne Contao-Abhängigkeiten, damit die Logik ohne Framework prüfbar bleibt.
 */
final class TimelineBauer
{
    public const TYPEN = ['eintrag', 'meilenstein', 'pause'];

    /**
     * @param list<array<string, mixed>> $eintraege
     * @param int                        $max         0 = alle (zählt nur Einträge und Meilensteine)
     * @param int                        $pauseMonate 0 = keine automatischen Pausen
     *
     * @return list<array<string, mixed>>
     */
    public static function baue(array $eintraege, bool $neuesteZuerst = false, int $max = 0, int $pauseMonate = 0): array
    {
        $eintraege = array_values(array_filter(
            $eintraege,
            static fn (array $e): bool => \in_array($e['typ'] ?? '', self::TYPEN, true) && (int) ($e['zeit'] ?? 0) > 0,
        ));

        // Stabil nach Zeit sortieren (bei gleicher Zeit bleibt die Eingabereihenfolge)
        $nummeriert = [];

        foreach ($eintraege as $i => $e) {
            $nummeriert[] = [$i, $e];
        }

        usort($nummeriert, static fn (array $a, array $b): int => [(int) $a[1]['zeit'], $a[0]] <=> [(int) $b[1]['zeit'], $b[0]]);
        $folge = array_map(static fn (array $p): array => $p[1], $nummeriert);

        if ($pauseMonate > 0) {
            $folge = self::pausenEinfuegen($folge, $pauseMonate);
        }

        if ($neuesteZuerst) {
            $folge = array_reverse($folge);
        }

        // Pausen am Rand ergeben keinen Sinn (z. B. nach dem Kürzen)
        $folge = self::kuerzen($folge, $max);

        return self::jahreEinfuegen($folge);
    }

    /** Monate zwischen zwei Zeitpunkten (nur Jahr und Monat zählen). */
    public static function monate(int $von, int $bis): int
    {
        [$j1, $m1] = array_map('intval', explode('-', date('Y-n', $von)));
        [$j2, $m2] = array_map('intval', explode('-', date('Y-n', $bis)));

        return ($j2 - $j1) * 12 + ($m2 - $m1);
    }

    public static function pausenText(int $monate): string
    {
        if ($monate >= 18) {
            // auf halbe Jahre runden: 19 Monate = anderthalb Jahre, 24 = zwei Jahre
            $halbe = (int) round($monate / 6);

            if (3 === $halbe) {
                return 'Anderthalb Jahre lang blieb der Bildschirm dunkel.';
            }

            $jahre = intdiv($halbe, 2);

            return 0 === $halbe % 2
                ? sprintf('Rund %d Jahre lang blieb der Bildschirm dunkel.', $jahre)
                : sprintf('Rund %d½ Jahre lang blieb der Bildschirm dunkel.', $jahre);
        }

        if ($monate >= 11) {
            return 'Ein Jahr lang blieb der Bildschirm dunkel.';
        }

        return sprintf('%d Monate lang blieb der Bildschirm dunkel.', $monate);
    }

    /**
     * @param list<array<string, mixed>> $folge aufsteigend sortiert
     *
     * @return list<array<string, mixed>>
     */
    private static function pausenEinfuegen(array $folge, int $pauseMonate): array
    {
        $ergebnis = [];
        $vorher = null;

        foreach ($folge as $e) {
            if ('pause' === $e['typ']) {
                // Eine eingetragene Pause ersetzt die automatische an dieser Stelle
                $ergebnis[] = $e;
                $vorher = null;

                continue;
            }

            if (null !== $vorher) {
                $monate = self::monate((int) $vorher['zeit'], (int) $e['zeit']);

                if ($monate >= $pauseMonate) {
                    $ergebnis[] = [
                        'typ' => 'pause',
                        'zeit' => (int) $vorher['zeit'] + 1,
                        'datum' => date('m/Y', (int) $vorher['zeit']).' – '.date('m/Y', (int) $e['zeit']),
                        'datum_iso' => '',
                        'titel' => '',
                        'text' => self::pausenText($monate),
                        'tags' => [],
                        'url' => '',
                        'automatisch' => true,
                    ];
                }
            }

            $ergebnis[] = $e;
            $vorher = $e;
        }

        return $ergebnis;
    }

    /**
     * @param list<array<string, mixed>> $folge
     *
     * @return list<array<string, mixed>>
     */
    private static function kuerzen(array $folge, int $max): array
    {
        if ($max > 0) {
            $gezaehlt = 0;
            $gekuerzt = [];

            foreach ($folge as $e) {
                if ('pause' !== $e['typ'] && ++$gezaehlt > $max) {
                    break;
                }

                $gekuerzt[] = $e;
            }

            $folge = $gekuerzt;
        }

        while ($folge && 'pause' === $folge[0]['typ']) {
            array_shift($folge);
        }

        while ($folge && 'pause' === $folge[\count($folge) - 1]['typ']) {
            array_pop($folge);
        }

        return $folge;
    }

    /**
     * @param list<array<string, mixed>> $folge
     *
     * @return list<array<string, mixed>>
     */
    private static function jahreEinfuegen(array $folge): array
    {
        $ergebnis = [];
        $jahr = null;

        foreach ($folge as $e) {
            if ('pause' !== $e['typ']) {
                $j = date('Y', (int) $e['zeit']);

                if ($j !== $jahr) {
                    $ergebnis[] = ['art' => 'jahr', 'jahr' => $j];
                    $jahr = $j;
                }
            }

            $e['art'] = 'pause' === $e['typ'] ? 'pause' : 'eintrag';
            $e['meilenstein'] = 'meilenstein' === $e['typ'];
            $ergebnis[] = $e;
        }

        return $ergebnis;
    }
}
