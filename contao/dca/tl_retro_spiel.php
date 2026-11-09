<?php

use Contao\DataContainer;
use Contao\DC_Table;
use Darthmax\RetroBundle\Spiele\Genres;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

/*
 * Spiele-Datenbank (rg-25): ein Datensatz je Spiel und Plattform – „California Games“ für den
 * Lynx ist ein eigenes Spiel mit eigenen Screenshots. Revisionen (Lynx II, Game Boy Pocket …) bekommen
 * keine eigenen Einträge: Das Spiel hängt am Basismodell und gilt für alle Revisionen (rg-28). Entwickler und Publisher kommen aus dem
 * Firmen-Katalog. Screenshots: höchstens 6, das erste der Sortierung steht in der Galerie.
 * „In der Galerie zeigen“ + Sortierung bestimmen, was das Element „Spielegalerie“ ausgibt.
 * Daten kommen per Hand oder per CSV-Import (Knopf „CSV-Import“ in der Liste).
 */
$GLOBALS['TL_DCA']['tl_retro_spiel'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'markAsCopy' => 'name',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'name' => 'index',
                'geraet,galerie,published' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTABLE,
            'fields' => ['name'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;sort,search,limit',
            'defaultSearchField' => 'name',
        ],
        'label' => ['fields' => ['name', 'geraet', 'jahr', 'genre', 'galerie'], 'showColumns' => true],
        'global_operations' => [
            'csv_import' => [
                'href' => 'key=csv_import',
                'class' => 'header_css_import',
                'icon' => 'theme_import.svg',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{spiel_legend},name,geraet,genre,spieler;'
            .'{firmen_legend},entwickler,publisher;'
            .'{team_legend},programmierer,grafiker,musiker;'
            .'{markt_legend},jahr,monat,tag,region,modellnummer,modul_kb;'
            .'{bilder_legend},screenshots;'
            .'{galerie_legend},galerie,sortierung;'
            .'{text_legend},teaser,beschreibung,quelle_text;'
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
        'geraet' => [
            'inputType' => 'select',
            'filter' => true,
            'sorting' => true,
            'foreignKey' => 'tl_retro_geraet.name',
            'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'genre' => [
            'inputType' => 'select',
            'filter' => true,
            'sorting' => true,
            'options' => array_keys(Genres::LISTE),
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_spiel']['genre_opt'],
            'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
        ],
        'spieler' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'entwickler' => [
            'inputType' => 'select',
            'filter' => true,
            'foreignKey' => 'tl_retro_firma.name',
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'publisher' => [
            'inputType' => 'select',
            'filter' => true,
            'foreignKey' => 'tl_retro_firma.name',
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'programmierer' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'grafiker' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'musiker' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => ''],
        ],
        'jahr' => [
            'inputType' => 'text',
            'filter' => true,
            'sorting' => true,
            'eval' => ['rgxp' => 'natural', 'minval' => 1950, 'maxval' => 2100, 'nospace' => true, 'tl_class' => 'w25 clr'],
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
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
        'region' => [
            'inputType' => 'text',
            'eval' => ['maxlength' => 32, 'tl_class' => 'w25'],
            'sql' => ['type' => 'string', 'length' => 32, 'default' => ''],
        ],
        'modellnummer' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'string', 'length' => 64, 'default' => ''],
        ],
        'modul_kb' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false],
        ],
        'screenshots' => [
            'inputType' => 'fileTree',
            'eval' => [
                'multiple' => true,
                'fieldType' => 'checkbox',
                'filesOnly' => true,
                'isGallery' => true,
                'isSortable' => true,
                'extensions' => '%contao.image.valid_extensions%',
                'tl_class' => 'clr',
            ],
            'sql' => ['type' => 'blob', 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false],
        ],
        'galerie' => [
            'inputType' => 'checkbox',
            'filter' => true,
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'sortierung' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
        ],
        'teaser' => [
            'inputType' => 'textarea',
            'search' => true,
            'eval' => ['maxlength' => 400, 'rows' => 3, 'tl_class' => 'clr'],
            'sql' => ['type' => 'text', 'notnull' => false],
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
