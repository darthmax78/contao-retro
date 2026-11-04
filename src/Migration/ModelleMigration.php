<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;

/**
 * Überträgt tl_retro_geraet.modellnummer in die Zeilenliste "modelle" (rg-11) und
 * entfernt danach die alte Spalte. Läuft erst, wenn die neue Spalte angelegt ist.
 */
class ModelleMigration extends AbstractMigration
{
    public function __construct(private readonly Connection $db)
    {
    }

    public function shouldRun(): bool
    {
        $schema = $this->db->createSchemaManager();

        if (!$schema->tablesExist(['tl_retro_geraet'])) {
            return false;
        }

        $spalten = array_change_key_case($schema->listTableColumns('tl_retro_geraet'));

        return isset($spalten['modellnummer'], $spalten['modelle']);
    }

    public function run(): MigrationResult
    {
        $zeilen = $this->db->fetchAllAssociative("SELECT id, modellnummer FROM tl_retro_geraet WHERE modellnummer != ''");

        foreach ($zeilen as $zeile) {
            $this->db->update(
                'tl_retro_geraet',
                ['modelle' => serialize([['nummer' => $zeile['modellnummer'], 'variante' => '', 'region' => '']])],
                ['id' => $zeile['id']],
            );
        }

        $this->db->executeStatement('ALTER TABLE tl_retro_geraet DROP modellnummer');

        return $this->createResult(true, \count($zeilen).' Modellnummern übertragen.');
    }
}
