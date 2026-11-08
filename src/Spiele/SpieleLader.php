<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Spiele;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

/**
 * Lädt Spiele für die Spielegalerie (rg-25): veröffentlicht, „In der Galerie zeigen“,
 * Geräte aus dem Element. Reihenfolge: Sortierzahl (0 zuletzt), dann Name.
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
}
