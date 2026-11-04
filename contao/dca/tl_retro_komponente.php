<?php

use Contao\DataContainer;
use Contao\DC_Table;

// Chip-Katalog. "familie" macht Abfragen wie „alle Geräte mit 6502-Familie“ möglich.
$GLOBALS['TL_DCA']['tl_retro_komponente'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'name' => 'index', 'familie' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['name'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
            'defaultSearchField' => 'name',
        ],
        'label' => ['fields' => ['name', 'familie', 'art'], 'showColumns' => true],
    ],
    'palettes' => ['default' => '{komponente_legend},name,familie,art,bits,firma;{notiz_legend},notiz'],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'name' => [
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
        ],
        'familie' => [
            'inputType' => 'text',
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'art' => [
            'inputType' => 'select',
            'filter' => true,
            'options' => ['cpu', 'grafik', 'sound', 'soc', 'sonstige'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_komponente']['art_opt'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'cpu'],
        ],
        'bits' => [
            'inputType' => 'text',
            'filter' => true,
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w25'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
        ],
        'firma' => [
            'inputType' => 'select',
            'foreignKey' => 'tl_retro_firma.name',
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'notiz' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => ['type' => 'text', 'notnull' => false]],
    ],
];
