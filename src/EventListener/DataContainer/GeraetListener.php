<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\CoreBundle\Slug\Slug;
use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Alias und Basismodell der Geräte-Datenbank.
 *
 * Das Basismodell hat genau eine Ebene: Zur Auswahl stehen nur Geräte ohne eigenes
 * Basismodell, und ein Gerät, von dem andere erben, kann selbst nicht erben.
 */
class GeraetListener
{
    public function __construct(
        private readonly Connection $db,
        #[Autowire(service: 'contao.slug')]
        private readonly Slug $slug,
    ) {
    }

    #[AsCallback('tl_retro_geraet', 'fields.alias.save')]
    public function erzeugeAlias(mixed $wert, DataContainer $dc): string
    {
        $vergeben = fn (string $alias): bool => false !== $this->db->fetchOne(
            'SELECT id FROM tl_retro_geraet WHERE alias = ? AND id != ?',
            [$alias, $dc->id],
        );

        if ('' === (string) $wert) {
            // getActiveRecord(): Enthält den im selben Speichervorgang eingegebenen Namen,
            // getCurrentRecord() nur den Stand aus der Datenbank.
            $datensatz = $dc instanceof DC_Table ? $dc->getActiveRecord() : $dc->getCurrentRecord();

            return $this->slug->generate((string) ($datensatz['name'] ?? ''), [], $vergeben);
        }

        if ($vergeben((string) $wert)) {
            throw new \RuntimeException(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $wert));
        }

        return (string) $wert;
    }

    /**
     * @return array<int, string>
     */
    #[AsCallback('tl_retro_geraet', 'fields.basis.options')]
    public function basisOptionen(DataContainer $dc): array
    {
        $optionen = $this->db->fetchAllKeyValue(
            'SELECT id, name FROM tl_retro_geraet WHERE basis = 0 AND id != ? ORDER BY name',
            [(int) $dc->id],
        );

        return array_map('strval', $optionen);
    }

    #[AsCallback('tl_retro_geraet', 'fields.basis.save')]
    public function pruefeBasis(mixed $wert, DataContainer $dc): mixed
    {
        if (!$wert) {
            return $wert;
        }

        if ((int) $wert === (int) $dc->id) {
            throw new \RuntimeException($GLOBALS['TL_LANG']['tl_retro_geraet']['basis_selbst']);
        }

        $erben = $this->db->fetchOne('SELECT COUNT(*) FROM tl_retro_geraet WHERE basis = ?', [(int) $dc->id]);

        if ((int) $erben > 0) {
            throw new \RuntimeException($GLOBALS['TL_LANG']['tl_retro_geraet']['basis_kette']);
        }

        return $wert;
    }
}
