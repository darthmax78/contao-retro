<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Lädt Spiele für die Spielegalerie (rg-25): veröffentlicht, „In der Galerie zeigen“,
 * Geräte aus dem Element samt ihren Basismodellen (rg-28). Reihenfolge: Sortierzahl (0 zuletzt), dann Name.
 * Für die Spieleliste (rg-31) alle Spiele der Geräte mit gewähltem Status, nach Name.
 */
final class SpieleLader
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @param list<int> $geraetIds
     *
     * @return list<array<string, mixed>> Spiele mit entwickler_name, publisher_name
     */
    public function galerie(array $geraetIds, int $max, bool $auchUnveroeffentlicht = false): array
    {
        if (!$geraetIds) {
            return [];
        }

        $geraetIds = $this->mitBasis($geraetIds);

        return $this->db->fetchAllAssociative(
            'SELECT s.*, e.name AS entwickler_name, p.name AS publisher_name
             FROM tl_retro_spiel s
             LEFT JOIN tl_retro_firma e ON e.id = s.entwickler
             LEFT JOIN tl_retro_firma p ON p.id = s.publisher
             WHERE s.galerie = 1 AND s.geraet IN (?)'.($auchUnveroeffentlicht ? '' : ' AND s.published = 1').'
             ORDER BY (s.sortierung = 0), s.sortierung, s.name
             LIMIT '.max(1, min(48, $max)),
            [$geraetIds],
            [ArrayParameterType::INTEGER],
        );
    }

    /**
     * Alle Spiele der Geräte (samt Basismodell) für die Spieleliste (rg-31), nach Name.
     *
     * @param list<int>    $geraetIds
     * @param list<string> $status    Schlüssel aus Status::LISTE, leer = alle
     *
     * @return list<array<string, mixed>> Spiele mit entwickler_name, publisher_name
     */
    public function liste(array $geraetIds, array $status, bool $auchUnveroeffentlicht = false): array
    {
        if (!$geraetIds) {
            return [];
        }

        $sql = 'SELECT s.*, e.name AS entwickler_name, p.name AS publisher_name
                FROM tl_retro_spiel s
                LEFT JOIN tl_retro_firma e ON e.id = s.entwickler
                LEFT JOIN tl_retro_firma p ON p.id = s.publisher
                WHERE s.geraet IN (?)'.($auchUnveroeffentlicht ? '' : ' AND s.published = 1');
        $werte = [$this->mitBasis($geraetIds)];
        $typen = [ArrayParameterType::INTEGER];

        $status = array_values(array_intersect(array_keys(Status::LISTE), $status));

        if ($status && \count($status) < \count(Status::LISTE)) {
            $sql .= ' AND s.status IN (?)';
            $werte[] = $status;
            $typen[] = ArrayParameterType::STRING;
        }

        $spiele = $this->db->fetchAllAssociative($sql.' ORDER BY s.name', $werte, $typen);

        // Natürliche Reihenfolge ohne Groß/Klein („Ninja Gaiden“ vor „Ninja Gaiden III“, „The …“ unter T)
        usort($spiele, static fn (array $a, array $b): int => strnatcasecmp((string) $a['name'], (string) $b['name']));

        return $spiele;
    }

    /**
     * Revisionen (rg-28): Spiele hängen am Basismodell – für Lynx II also auch die Spiele des Lynx.
     *
     * @param list<int> $geraetIds
     *
     * @return list<int>
     */
    private function mitBasis(array $geraetIds): array
    {
        $basen = $this->db->fetchFirstColumn(
            'SELECT basis FROM tl_retro_geraet WHERE id IN (?) AND basis > 0',
            [$geraetIds],
            [ArrayParameterType::INTEGER],
        );

        return array_values(array_unique([...$geraetIds, ...array_map('intval', $basen)]));
    }
}
