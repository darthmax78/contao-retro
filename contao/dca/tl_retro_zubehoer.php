<?php

use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

/*
 * Original-Zubehör (rg-12). Ein Teil gehört zu mehreren Geräten, daher die Auswahl
 * direkt am Zubehör. Es wird bewusst nicht vom Basismodell geerbt: Das Netzteil PAG1200
 * passt zu beiden Lynx, der Sonnenschutz gibt es je Modell mit eigener Nummer.
 */
$GLOBALS['TL_DCA']['tl_retro_zubehoer'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'markAsCopy' => 'name',
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'published' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['name'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
            'defaultSearchField' => 'name',
        ],
        'label' => ['fields' => ['name', 'art', 'modellnummer'], 'showColumns' => true],
    ],
    'palettes' => [
        'default' => '{zubehoer_legend},name,art,modellnummer,geraete;'
            .'{daten_legend},daten_text,marktstart_jahr,produktionsende_jahr,beschreibung;'
            .'{quelle_legend},quelle_text;'
            .'{publish_legend},published',
    ],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'name' => [
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => ''],
        ],
        'art' => [
            'inputType' => 'select',
            'filter' => true,
            'sorting' => true,
            'options' => array_keys(\Darthmax\RetroBundle\Steckbrief\GeraetLader::ZUBEHOER_ARTEN),
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_zubehoer']['art_opt'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'sonstiges'],
        ],
        'modellnummer' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'geraete' => [
            'inputType' => 'select',
            'filter' => true,
            'foreignKey' => 'tl_retro_geraet.name',
            'eval' => ['mandatory' => true, 'multiple' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'blob', 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false],
        ],
        'daten_text' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'marktstart_jahr' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w25 clr'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
        ],
        'produktionsende_jahr' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w25'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
        ],
        'beschreibung' => [
            'inputType' => 'textarea',
            'search' => true,
            'eval' => ['tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
        ],
        'quelle_text' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'long'],
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
