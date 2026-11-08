<?php

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Timeline (rg-27): Stationen ohne eigene News und Pausen mit eigenem Text.
 * News erscheinen über „In der Timeline zeigen“ direkt in der News, nicht hier.
 */
$GLOBALS['TL_DCA']['tl_retro_timeline'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'markAsCopy' => 'titel',
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'published,datum' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['datum'],
            'flag' => DataContainer::SORT_DAY_DESC,
            'panelLayout' => 'filter;sort,search,limit',
            'defaultSearchField' => 'titel',
        ],
        'label' => ['fields' => ['datum', 'typ', 'titel'], 'showColumns' => true],
    ],
    'palettes' => [
        'default' => '{eintrag_legend},typ,datum,datum_text,titel;{inhalt_legend},text,tags,url;{publish_legend},published',
    ],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'typ' => [
            'inputType' => 'select',
            'filter' => true,
            'options' => ['eintrag', 'meilenstein', 'pause'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_timeline']['typ_opt'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'eintrag'],
        ],
        'datum' => [
            'inputType' => 'text',
            'sorting' => true,
            'flag' => DataContainer::SORT_YEAR_DESC,
            'eval' => ['mandatory' => true, 'rgxp' => 'date', 'datepicker' => true, 'tl_class' => 'w50 wizard clr'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
        ],
        'datum_text' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'titel' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 128, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
        ],
        'text' => [
            'inputType' => 'textarea',
            'search' => true,
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'tags' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'url' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
