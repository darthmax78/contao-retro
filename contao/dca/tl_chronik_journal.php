<?php

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Chronik-Paket: Tagesprotokolle (vom Cronjob bzw. „chronik:journal erfassen“ geschrieben).
 * Nur lesen; neu anlegen und kopieren ist abgeschaltet.
 */
$GLOBALS['TL_DCA']['tl_chronik_journal'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'closed' => true,
        'notCopyable' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tag' => 'unique', 'leer' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['tag'],
            'flag' => DataContainer::SORT_DESC,
            'panelLayout' => 'filter;search,limit',
            'filter' => [['leer = ?', '0']],
            'defaultSearchField' => 'inhalt',
        ],
        'label' => ['fields' => ['tag', 'zusammenfassung'], 'showColumns' => true],
        'operations' => ['edit', 'delete', 'show'],
    ],
    'palettes' => [
        'default' => '{journal_legend},tag,zusammenfassung,inhalt',
    ],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'tag' => [
            'inputType' => 'text',
            'sorting' => true,
            'flag' => DataContainer::SORT_DESC,
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 10, 'default' => ''],
        ],
        'leer' => [
            'filter' => true,
            'inputType' => 'checkbox',
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'zusammenfassung' => [
            'inputType' => 'text',
            'eval' => ['readonly' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'zahlen' => [
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'inhalt' => [
            'inputType' => 'textarea',
            'search' => true,
            'eval' => ['readonly' => true, 'rte' => false, 'style' => 'height: 40em; font-family: monospace', 'tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'length' => 16777215, 'notnull' => false],
        ],
    ],
];
