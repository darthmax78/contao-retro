<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * rg-23: Zubehör-Elemente bekommen statt eines Geräts eine Liste (retro_geraete).
 * Überträgt das bisherige Gerät aus retro_geraet, solange die Liste leer ist.
 * Löscht nichts – retro_geraet bleibt für Steckbrief und Unterschiede in Gebrauch.
 */
class ZubehoerGeraeteMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        $schema = $this->db->createSchemaManager();

        if (!$schema->tablesExist(['tl_content'])) {
            return false;
        }

        $spalten = array_change_key_case($schema->listTableColumns('tl_content'));

        if (!isset($spalten['retro_geraet'], $spalten['retro_geraete'])) {
            return false;
        }

        return (int) $this->db->fetchOne($this->abfrage('COUNT(*)')) > 0;
    }

    public function run(): MigrationResult
    {
        $zeilen = $this->db->fetchAllAssociative($this->abfrage('id, retro_geraet'));

        foreach ($zeilen as $zeile) {
            $this->db->update('tl_content', ['retro_geraete' => serialize([(string) $zeile['retro_geraet']])], ['id' => $zeile['id']]);
        }

        return $this->createResult(true, \count($zeilen).' Zubehör-Elemente auf Geräteliste umgestellt.');
    }

    private function abfrage(string $spalten): string
    {
        return "SELECT $spalten FROM tl_content WHERE type = 'retro_zubehoer' AND retro_geraet > 0 AND (retro_geraete IS NULL OR retro_geraete = '')";
    }
}
