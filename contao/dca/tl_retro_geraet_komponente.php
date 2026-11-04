<?php

use Contao\DataContainer;
use Contao\DC_Table;

// Zuordnung Gerät ↔ Komponente. Der Takt hängt am Gerät, nicht am Chip:
// Der SM83 läuft im Game Boy mit 4,19 MHz, im Game Boy Advance mit 8,4 oder 4,2 MHz.
$GLOBALS['TL_DCA']['tl_retro_geraet_komponente'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_retro_geraet',
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'pid,sorting' => 'index', 'komponente' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting'],
            'panelLayout' => 'limit',
            'headerFields' => ['name', 'typ'],
        ],
        'label' => ['fields' => ['rolle', 'komponente'], 'format' => '%s: %s'],
    ],
    'palettes' => ['default' => '{komponente_legend},komponente,rolle,takt_mhz,anzeige_text'],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'pid' => [
            'foreignKey' => 'tl_retro_geraet.name',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'sorting' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'komponente' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_retro_komponente.name',
            'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'rolle' => [
            'inputType' => 'select',
            'options' => ['cpu', 'zusatz_cpu', 'coprozessor', 'grafik', 'sound', 'soc'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_geraet_komponente']['rolle_opt'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'cpu'],
        ],
        'takt_mhz' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'nospace' => true, 'tl_class' => 'w25 clr'],
            'sql' => ['type' => 'decimal', 'precision' => 10, 'scale' => 4, 'notnull' => false],
        ],
        'anzeige_text' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w75'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
    ],
];
