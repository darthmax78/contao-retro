<?php

use Contao\DataContainer;
use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_retro_firma'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'name' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['name'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'search,limit',
            'defaultSearchField' => 'name',
        ],
        'label' => ['fields' => ['name', 'land'], 'showColumns' => true],
    ],
    'palettes' => ['default' => '{firma_legend},name,land;{notiz_legend},notiz'],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'name' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
        ],
        'land' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'notiz' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => ['type' => 'text', 'notnull' => false]],
    ],
];
