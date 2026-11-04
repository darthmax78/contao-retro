<?php

use Contao\DataContainer;
use Contao\DC_Table;

// Beteiligte Firmen je Gerät mit Rolle. Lynx: Epyx = Entwicklung, Atari = Hersteller.
$GLOBALS['TL_DCA']['tl_retro_geraet_firma'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_retro_geraet',
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'pid,sorting' => 'index', 'firma' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting'],
            'panelLayout' => 'limit',
            'headerFields' => ['name', 'typ'],
        ],
        'label' => ['fields' => ['rolle', 'firma'], 'format' => '%s: %s'],
    ],
    'palettes' => ['default' => '{firma_legend},firma,rolle,anmerkung'],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'pid' => [
            'foreignKey' => 'tl_retro_geraet.name',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'sorting' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'firma' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_retro_firma.name',
            'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'rolle' => [
            'inputType' => 'select',
            'options' => ['hersteller', 'entwicklung', 'vertrieb'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_geraet_firma']['rolle_opt'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'hersteller'],
        ],
        'anmerkung' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
    ],
];
