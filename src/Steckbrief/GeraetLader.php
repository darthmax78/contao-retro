<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Steckbrief;

use Doctrine\DBAL\Connection;

/**
 * Lädt ein Gerät samt Basismodell und Kinddaten aus der Geräte-Datenbank.
 */
class GeraetLader
{
    public function __construct(private readonly Connection $db)
    {
    }

    /**
     * @return array{geraet: array<string, mixed>, basis: array<string, mixed>|null, firmen: list<array<string, mixed>>, komponenten: list<array<string, mixed>>, releases: list<array<string, mixed>>}|null
     */
    public function lade(int $id): array|null
    {
        $geraet = $this->db->fetchAssociative('SELECT * FROM tl_retro_geraet WHERE id = ?', [$id]);

        if (false === $geraet) {
            return null;
        }

        // Verweist "basis" auf ein gelöschtes Gerät, wird nicht geerbt.
        $basis = (int) $geraet['basis'] > 0
            ? ($this->db->fetchAssociative('SELECT * FROM tl_retro_geraet WHERE id = ?', [(int) $geraet['basis']]) ?: null)
            : null;

        // Firmen und Komponenten erbt ein Modell, wenn es selbst keine hat.
        $firmen = $this->firmen($id) ?: ($basis ? $this->firmen((int) $basis['id']) : []);
        $komponenten = $this->komponenten($id) ?: ($basis ? $this->komponenten((int) $basis['id']) : []);

        return [
            'geraet' => $geraet,
            'basis' => $basis,
            'firmen' => $firmen,
            'komponenten' => $komponenten,
            'releases' => $this->db->fetchAllAssociative(
                'SELECT * FROM tl_retro_release WHERE pid = ? ORDER BY jahr, monat, tag, id',
                [$id],
            ),
        ];
    }

    /**
     * Die Geräte stehen serialisiert am Zubehör, daher die Auswahl in PHP.
     *
     * @return list<array<string, mixed>>
     */
    public function zubehoer(int $geraetId): array
    {
        $teile = $this->db->fetchAllAssociative('SELECT * FROM tl_retro_zubehoer WHERE published = 1 ORDER BY name');

        return array_values(array_filter($teile, static function (array $teil) use ($geraetId): bool {
            $geraete = @unserialize((string) $teil['geraete'], ['allowed_classes' => false]);

            return \is_array($geraete) && \in_array($geraetId, array_map('intval', $geraete), true);
        }));
    }

    /**
     * @return array<int, string> ID => Name, nur für die Auswahl im Backend
     */
    public function auswahl(): array
    {
        return array_map('strval', $this->db->fetchAllKeyValue('SELECT id, name FROM tl_retro_geraet ORDER BY name'));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function firmen(int $id): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT z.firma AS firma_id, z.rolle, z.anmerkung, f.name
             FROM tl_retro_geraet_firma z
             INNER JOIN tl_retro_firma f ON f.id = z.firma
             WHERE z.pid = ? ORDER BY z.sorting',
            [$id],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function komponenten(int $id): array
    {
        return $this->db->fetchAllAssociative(
            'SELECT z.komponente AS komponente_id, z.rolle, z.takt_mhz, z.anzeige_text, k.name, k.bits
             FROM tl_retro_geraet_komponente z
             INNER JOIN tl_retro_komponente k ON k.id = z.komponente
             WHERE z.pid = ? ORDER BY z.sorting',
            [$id],
        );
    }
}
