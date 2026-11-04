<?php

use Contao\DataContainer;
use Contao\DC_Table;

// Quellen je Gerät, optional einer Steckbrief-Gruppe zugeordnet.
$GLOBALS['TL_DCA']['tl_retro_quelle'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_retro_geraet',
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'pid,sorting' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['sorting'],
            'panelLayout' => 'search,limit',
            'defaultSearchField' => 'titel',
            'headerFields' => ['name', 'typ'],
        ],
        'label' => ['fields' => ['titel'], 'format' => '%s'],
    ],
    'palettes' => ['default' => '{quelle_legend},titel,url,abgerufen,bezug,notiz'],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'pid' => [
            'foreignKey' => 'tl_retro_geraet.name',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'sorting' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'titel' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'long'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'url' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 2048, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 2048, 'default' => ''],
        ],
        'abgerufen' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard clr'],
            'sql' => ['type' => 'integer', 'notnull' => false],
        ],
        'bezug' => [
            'inputType' => 'select',
            'options' => ['allgemein', 'technik', 'bild', 'audio', 'medien', 'anschluesse', 'gehaeuse', 'markt'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_quelle']['bezug_opt'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
        ],
        'notiz' => [
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
    ],
];
