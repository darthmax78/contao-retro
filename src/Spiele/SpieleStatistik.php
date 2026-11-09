<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Kennzahlen zu den Spielen einer Plattform (rg-30) für das Element „Spielestatistik“.
 *
 * Gezählt werden veröffentlichte Spiele der gewählten Geräte samt Basismodell (Revisionen haben
 * keine eigenen Spiele, rg-28) mit einem der gewählten Status. Die Galerie-Markierung spielt keine Rolle.
 * Modulgrößen: Durchschnitt und Maximum nur über Spiele mit Angabe; wie viele das sind, steht in „modul_erfasst“.
 */
final class SpieleStatistik
{
    /** Schlüssel der Sammelzeile für alle Genres jenseits der Rangliste (Anzeige „Weitere“) */
    public const REST = '_weitere';

    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param list<int>    $geraetIds
     * @param list<string> $status    Schlüssel aus Status::LISTE, leer = alle
     *
     * @return array{
     *   anzahl: int, jahr_von: ?int, jahr_bis: ?int, publisher: int, entwickler: int,
     *   modul_max: ?int, modul_schnitt: ?float, modul_erfasst: int,
     *   jahre: list<array{jahr: int, anzahl: int}>, ohne_jahr: int,
     *   genres: list<array{genre: string, anzahl: int}>,
     *   firmen: list<array{name: string, anzahl: int}>
     * }
     */
    public function berechnen(array $geraetIds, array $status, int $genresMax = 5, int $firmenMax = 3): array
    {
        $zeilen = $this->zeilen($geraetIds, $status);

        $jahre = [];
        $ohneJahr = 0;
        $genres = [];
        $publisher = [];
        $entwickler = [];
        $module = [];

        foreach ($zeilen as $z) {
            $jahr = (int) $z['jahr'];

            if ($jahr > 0) {
                $jahre[$jahr] = ($jahre[$jahr] ?? 0) + 1;
            } else {
                ++$ohneJahr;
            }

            $genre = (string) $z['genre'];
            $genre = '' === $genre ? 'sonstiges' : $genre;
            $genres[$genre] = ($genres[$genre] ?? 0) + 1;

            if ((int) $z['publisher'] > 0 && '' !== trim((string) $z['publisher_name'])) {
                $name = trim((string) $z['publisher_name']);
                $publisher[$name] = ($publisher[$name] ?? 0) + 1;
            }

            if ((int) $z['entwickler'] > 0) {
                $entwickler[(int) $z['entwickler']] = true;
            }

            if ((int) $z['modul_kb'] > 0) {
                $module[] = (int) $z['modul_kb'];
            }
        }

        // Jahre lückenlos, damit leere Jahre als Lücke im Verlauf sichtbar bleiben
        $verlauf = [];

        if ($jahre) {
            for ($j = min(array_keys($jahre)); $j <= max(array_keys($jahre)); ++$j) {
                $verlauf[] = ['jahr' => $j, 'anzahl' => $jahre[$j] ?? 0];
            }
        }

        return [
            'anzahl' => \count($zeilen),
            'jahr_von' => $jahre ? min(array_keys($jahre)) : null,
            'jahr_bis' => $jahre ? max(array_keys($jahre)) : null,
            'publisher' => \count($publisher),
            'entwickler' => \count($entwickler),
            'modul_max' => $module ? max($module) : null,
            'modul_schnitt' => $module ? array_sum($module) / \count($module) : null,
            'modul_erfasst' => \count($module),
            'jahre' => $verlauf,
            'ohne_jahr' => $ohneJahr,
            'genres' => $this->rangliste($genres, max(1, $genresMax), 'genre', self::REST),
            'firmen' => $this->rangliste($publisher, max(1, $firmenMax), 'name', null),
        ];
    }

    /** 512 → „512 KB“, 1024 → „1 MB“, 213,4 → „213 KB“ */
    public static function groesse(float|int $kb): string
    {
        if ($kb >= 1024) {
            $mb = $kb / 1024;

            return str_replace('.', ',', (string) round($mb, fmod($mb, 1.0) < 0.05 ? 0 : 1)).' MB';
        }

        return (string) (int) round($kb).' KB';
    }

    /**
     * @param list<int>    $geraetIds
     * @param list<string> $status
     *
     * @return list<array<string, mixed>>
     */
    private function zeilen(array $geraetIds, array $status): array
    {
        if (!$geraetIds) {
            return [];
        }

        $basen = $this->db->fetchFirstColumn(
            'SELECT basis FROM tl_retro_geraet WHERE id IN (?) AND basis > 0',
            [$geraetIds],
            [ArrayParameterType::INTEGER],
        );
        $geraetIds = array_values(array_unique([...$geraetIds, ...array_map('intval', $basen)]));

        $sql = 'SELECT s.jahr, s.genre, s.modul_kb, s.publisher, s.entwickler, p.name AS publisher_name
                FROM tl_retro_spiel s
                LEFT JOIN tl_retro_firma p ON p.id = s.publisher
                WHERE s.published = 1 AND s.geraet IN (?)';
        $werte = [$geraetIds];
        $typen = [ArrayParameterType::INTEGER];

        $status = array_values(array_intersect(array_keys(Status::LISTE), $status));

        if ($status && \count($status) < \count(Status::LISTE)) {
            $sql .= ' AND s.status IN (?)';
            $werte[] = $status;
            $typen[] = ArrayParameterType::STRING;
        }

        return $this->db->fetchAllAssociative($sql, $werte, $typen);
    }

    /**
     * Die häufigsten Einträge, absteigend (bei Gleichstand alphabetisch); der Rest als eine Zeile mit „arten“ = Anzahl zusammengefasster Einträge.
     *
     * @param array<string, int> $zaehler
     *
     * @return list<array<string, int|string>>
     */
    private function rangliste(array $zaehler, int $max, string $schluessel, ?string $rest): array
    {
        uksort($zaehler, static fn (string $a, string $b): int => [$zaehler[$b], $a] <=> [$zaehler[$a], $b]);

        $oben = \array_slice($zaehler, 0, $max, true);
        $liste = [];

        foreach ($oben as $wert => $anzahl) {
            $liste[] = [$schluessel => (string) $wert, 'anzahl' => $anzahl];
        }

        if (null !== $rest && \count($zaehler) > $max) {
            $liste[] = [$schluessel => $rest, 'anzahl' => array_sum(\array_slice($zaehler, $max, null, true)), 'arten' => \count($zaehler) - $max];
        }

        return $liste;
    }
}
