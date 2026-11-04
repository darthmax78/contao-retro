<?php

use Contao\DataContainer;
use Contao\DC_Table;

/*
 * Marktstarts und Preisänderungen je Gerät und Region.
 * Datum als Jahr/Monat/Tag statt Zeitstempel: So bleibt die Genauigkeit erhalten
 * (nur das Jahr bekannt = Monat und Tag leer), und Daten vor 1970 sind unproblematisch.
 * "datum_text" überschreibt die Anzeige, etwa für „Herbst 1989“.
 * Preise bleiben in der Originalwährung.
 */
$GLOBALS['TL_DCA']['tl_retro_release'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_retro_geraet',
        'enableVersioning' => true,
        'sql' => ['keys' => ['id' => 'primary', 'tstamp' => 'index', 'pid,jahr' => 'index']],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['jahr', 'monat', 'tag'],
            'panelLayout' => 'filter;limit',
            'headerFields' => ['name', 'typ'],
        ],
        'label' => ['fields' => ['jahr', 'region', 'art'], 'format' => '%s · %s · %s'],
    ],
    'palettes' => [
        'default' => '{release_legend},art,region,jahr,monat,tag,datum_text;{preis_legend},preis,waehrung,preis_text;{anmerkung_legend},anmerkung',
    ],
    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'pid' => [
            'foreignKey' => 'tl_retro_geraet.name',
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],
        'art' => [
            'inputType' => 'select',
            'filter' => true,
            'options' => ['marktstart', 'preisaenderung'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_release']['art_opt'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'marktstart'],
        ],
        'region' => [
            'inputType' => 'select',
            'filter' => true,
            'options' => ['welt', 'jp', 'us', 'eu', 'de', 'at', 'uk', 'fr', 'au'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_release']['region_opt'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 8, 'default' => 'welt'],
        ],
        'jahr' => [
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'rgxp' => 'natural', 'maxlength' => 4, 'nospace' => true, 'tl_class' => 'w25 clr'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 0],
        ],
        'monat' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'minval' => 1, 'maxval' => 12, 'nospace' => true, 'tl_class' => 'w25'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
        ],
        'tag' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'minval' => 1, 'maxval' => 31, 'nospace' => true, 'tl_class' => 'w25'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
        ],
        'datum_text' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w25'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'preis' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'digit', 'nospace' => true, 'tl_class' => 'w25 clr'],
            'sql' => ['type' => 'decimal', 'precision' => 12, 'scale' => 2, 'notnull' => false],
        ],
        'waehrung' => [
            'inputType' => 'select',
            'options' => ['USD', 'JPY', 'DEM', 'ATS', 'EUR', 'GBP', 'FRF', 'AUD', 'CHF'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_release']['waehrung_opt'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w25'],
            'sql' => ['type' => 'string', 'length' => 3, 'default' => ''],
        ],
        'preis_text' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'anmerkung' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
    ],
];
