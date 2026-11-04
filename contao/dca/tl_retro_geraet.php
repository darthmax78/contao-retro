<?php

use Contao\DataContainer;
use Contao\DC_Table;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;

/*
 * Geräte-Datenbank (rg-09): je Konsole, Handheld, Homecomputer oder PDA ein Datensatz.
 *
 * Regeln, an die sich die Ausgabe (rg-10) hält:
 * - Revisionen sind eigene Geräte und verweisen über "basis" auf das Basismodell.
 *   Leere Felder (NULL bzw. '') erben vom Basismodell; 0 ist ein echter Wert.
 *   Es gibt nur eine Ebene: Ein Basismodell hat selbst kein Basismodell.
 * - Zahl und Anzeigetext sind getrennt. Die Zahl dient zum Sortieren und Vergleichen,
 *   der Text überschreibt die Anzeige. Speicher in KB (1 KB = 1024 Byte), daher
 *   Dezimalfeld: 128 Byte RAM = 0,125 KB.
 * - Neue Spalten sind eine Migration wert; Exoten gehören nach "weitere".
 */

$decimal = static fn (int $precision, int $scale): array => [
    'type' => 'decimal', 'precision' => $precision, 'scale' => $scale, 'notnull' => false,
];
$kb = ['type' => 'decimal', 'precision' => 14, 'scale' => 3, 'notnull' => false];
$zahl = static fn (string $class = 'w25'): array => ['rgxp' => 'digit', 'nospace' => true, 'tl_class' => $class];
$ganz = static fn (string $class = 'w25'): array => ['rgxp' => 'natural', 'nospace' => true, 'tl_class' => $class];
$text = static fn (int $length = 255): array => ['type' => 'string', 'length' => $length, 'default' => ''];
$anzeige = ['maxlength' => 255, 'tl_class' => 'w50'];
$lang = ['type' => 'text', 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_TEXT, 'notnull' => false];

$kopf = '{titel_legend},name,alias,typ,generation,basis,modellnummer,bits;';
$speicher = '{speicher_legend},ram_kb,ram_text,vram_kb,vram_text,rom_kb,rom_text;';
$bildTv = '{bild_legend},display_text,aufloesung_x,aufloesung_y,bildrate_hz,aufloesung_text,farben_anzeige,farben_palette,farben_text,sprites_text;';
$bildGeraet = '{bild_legend},display_text,display_zoll,aufloesung_x,aufloesung_y,bildrate_hz,aufloesung_text,farben_anzeige,farben_palette,farben_text,sprites_text;';
$rest = '{audio_legend},audio_kanaele,audio_text;'
    .'{medien_legend},medium,medium_max_kb,medium_text;'
    .'{anschluss_legend},mehrspieler_text,kopfhoerer_text,anschluesse_text;';
$gehaeuse = '{gehaeuse_legend},breite_mm,hoehe_mm,tiefe_mm,abmessungen_text,gewicht_g,gewicht_text,strom_text,laufzeit_std,laufzeit_text;';
$fuss = '{markt_legend},verkauft_stueck,verkauft_text,produktionsende_jahr;'
    .'{extras_legend},besonderheiten,neu_gegenueber_basis,weitere;'
    .'{artikel_legend},artikel;'
    .'{pruefung_legend},pruefstatus,geprueft_am,pruefnotiz;'
    .'{publish_legend},published';
$system = '{system_legend},eingabe_text,system_text;';

$GLOBALS['TL_DCA']['tl_retro_geraet'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_retro_geraet_firma', 'tl_retro_geraet_komponente', 'tl_retro_release', 'tl_retro_quelle'],
        'enableVersioning' => true,
        'markAsCopy' => 'name',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'tstamp' => 'index',
                'alias' => 'index',
                'basis' => 'index',
                'typ,published' => 'index',
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
        'label' => [
            'fields' => ['name', 'typ', 'pruefstatus'],
            'showColumns' => true,
        ],
        'operations' => [
            'edit',
            'copy',
            'delete',
            'toggle',
            'show',
            'versions',
            '-',
            'firmen' => ['href' => 'table=tl_retro_geraet_firma', 'icon' => 'mgroup.svg', 'prefetch' => true, 'primary' => true],
            'komponenten' => ['href' => 'table=tl_retro_geraet_komponente', 'icon' => 'modules.svg', 'prefetch' => true, 'primary' => true],
            'releases' => ['href' => 'table=tl_retro_release', 'icon' => 'children.svg', 'prefetch' => true, 'primary' => true],
            'quellen' => ['href' => 'table=tl_retro_quelle', 'icon' => 'article.svg', 'prefetch' => true, 'primary' => true],
        ],
    ],

    'palettes' => [
        '__selector__' => ['typ'],
        'default' => $kopf.$speicher.$bildTv.$rest.$gehaeuse.$fuss,
        'konsole' => $kopf.$speicher.$bildTv.$rest.$gehaeuse.$fuss,
        'handheld' => $kopf.$speicher.$bildGeraet.$rest.$gehaeuse.$fuss,
        'homecomputer' => $kopf.$speicher.$bildTv.$rest.$system.$gehaeuse.$fuss,
        'pda' => $kopf.$speicher.$bildGeraet.$rest.$system.$gehaeuse.$fuss,
    ],

    'fields' => [
        'id' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true]],
        'tstamp' => ['sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]],

        // Allgemein
        'name' => [
            'inputType' => 'text',
            'search' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'eval' => ['mandatory' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => $text(128),
        ],
        'alias' => [
            'inputType' => 'text',
            'search' => true,
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 128, 'default' => '', 'platformOptions' => ['collation' => 'utf8mb4_bin']],
        ],
        'typ' => [
            'inputType' => 'select',
            'filter' => true,
            'sorting' => true,
            'options' => ['konsole', 'handheld', 'homecomputer', 'pda'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_geraet']['typ_opt'],
            'eval' => ['mandatory' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'konsole'],
        ],
        'generation' => [
            'inputType' => 'text',
            'filter' => true,
            'sorting' => true,
            'eval' => $ganz('w25'),
            'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false],
        ],
        'basis' => [
            'inputType' => 'select',
            'filter' => true,
            'foreignKey' => 'tl_retro_geraet.name',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'modellnummer' => ['inputType' => 'text', 'search' => true, 'eval' => ['maxlength' => 64, 'tl_class' => 'w25'], 'sql' => $text(64)],
        'bits' => ['inputType' => 'text', 'filter' => true, 'eval' => $ganz('w25'), 'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false]],

        // Speicher
        'ram_kb' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $kb],
        'ram_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],
        'vram_kb' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $kb],
        'vram_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],
        'rom_kb' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $kb],
        'rom_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],

        // Bild
        'display_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'long'], 'sql' => $text()],
        'display_zoll' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $decimal(5, 2)],
        'aufloesung_x' => ['inputType' => 'text', 'eval' => $ganz('w25'), 'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false]],
        'aufloesung_y' => ['inputType' => 'text', 'eval' => $ganz('w25'), 'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false]],
        'bildrate_hz' => ['inputType' => 'text', 'eval' => $zahl('w25'), 'sql' => $decimal(6, 2)],
        'aufloesung_text' => ['inputType' => 'text', 'eval' => $anzeige, 'sql' => $text()],
        'farben_anzeige' => ['inputType' => 'text', 'eval' => $ganz('w25 clr'), 'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false]],
        'farben_palette' => ['inputType' => 'text', 'eval' => $ganz('w25'), 'sql' => ['type' => 'integer', 'unsigned' => true, 'notnull' => false]],
        'farben_text' => ['inputType' => 'text', 'eval' => $anzeige, 'sql' => $text()],
        'sprites_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'], 'sql' => $text()],

        // Audio
        'audio_kanaele' => ['inputType' => 'text', 'eval' => $ganz('w25 clr'), 'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false]],
        'audio_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],

        // Medien
        'medium' => [
            'inputType' => 'checkbox',
            'filter' => true,
            'options' => ['modul', 'karte', 'kassette', 'diskette', 'cd', 'dvd', 'umd', 'bluray', 'festspeicher'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_geraet']['medium_opt'],
            'eval' => ['multiple' => true, 'tl_class' => 'clr'],
            'sql' => ['type' => 'blob', 'length' => AbstractMySQLPlatform::LENGTH_LIMIT_BLOB, 'notnull' => false],
        ],
        'medium_max_kb' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $kb],
        'medium_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],

        // Anschlüsse
        'mehrspieler_text' => ['inputType' => 'text', 'eval' => $anzeige, 'sql' => $text()],
        'kopfhoerer_text' => ['inputType' => 'text', 'eval' => $anzeige, 'sql' => $text()],
        'anschluesse_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'], 'sql' => $text()],

        // Bedienung und System (Homecomputer, PDA)
        'eingabe_text' => ['inputType' => 'text', 'eval' => $anzeige, 'sql' => $text()],
        'system_text' => ['inputType' => 'text', 'eval' => $anzeige, 'sql' => $text()],

        // Gehäuse
        'breite_mm' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $decimal(7, 2)],
        'hoehe_mm' => ['inputType' => 'text', 'eval' => $zahl('w25'), 'sql' => $decimal(7, 2)],
        'tiefe_mm' => ['inputType' => 'text', 'eval' => $zahl('w25'), 'sql' => $decimal(7, 2)],
        'abmessungen_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'], 'sql' => $text()],
        'gewicht_g' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $decimal(9, 2)],
        'gewicht_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],
        'strom_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'long clr'], 'sql' => $text()],
        'laufzeit_std' => ['inputType' => 'text', 'eval' => $zahl('w25 clr'), 'sql' => $decimal(6, 2)],
        'laufzeit_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w75'], 'sql' => $text()],

        // Markt
        'verkauft_stueck' => ['inputType' => 'text', 'sorting' => true, 'eval' => $ganz('w25 clr'), 'sql' => ['type' => 'bigint', 'unsigned' => true, 'notnull' => false]],
        'verkauft_text' => ['inputType' => 'text', 'eval' => ['maxlength' => 255, 'tl_class' => 'w50'], 'sql' => $text()],
        'produktionsende_jahr' => ['inputType' => 'text', 'eval' => $ganz('w25'), 'sql' => ['type' => 'smallint', 'unsigned' => true, 'notnull' => false]],

        // Besonderheiten
        'besonderheiten' => ['inputType' => 'textarea', 'search' => true, 'eval' => ['tl_class' => 'clr'], 'sql' => $lang],
        'neu_gegenueber_basis' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => $lang],
        'weitere' => [
            'inputType' => 'rowWizard',
            'fields' => [
                'bezeichnung' => ['label' => &$GLOBALS['TL_LANG']['tl_retro_geraet']['weitere_bezeichnung'], 'inputType' => 'text'],
                'wert' => ['label' => &$GLOBALS['TL_LANG']['tl_retro_geraet']['weitere_wert'], 'inputType' => 'text'],
            ],
            'eval' => ['tl_class' => 'clr'],
            'sql' => $lang,
        ],

        // Verknüpfung
        'artikel' => [
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],

        // Prüfung und Freigabe
        'pruefstatus' => [
            'inputType' => 'select',
            'filter' => true,
            'sorting' => true,
            'options' => ['ungeprueft', 'geprueft'],
            'reference' => &$GLOBALS['TL_LANG']['tl_retro_geraet']['pruefstatus_opt'],
            'eval' => ['tl_class' => 'w50'],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => 'ungeprueft'],
        ],
        'geprueft_am' => [
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'datepicker' => true, 'doNotCopy' => true, 'tl_class' => 'w50 wizard'],
            'sql' => ['type' => 'integer', 'notnull' => false],
        ],
        'pruefnotiz' => ['inputType' => 'textarea', 'eval' => ['tl_class' => 'clr'], 'sql' => $lang],
        'published' => [
            'toggle' => true,
            'filter' => true,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];
