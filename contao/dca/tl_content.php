<?php

// Palette für das Inhaltselement "last_edited" (keine eigenen Felder nötig)
$GLOBALS['TL_DCA']['tl_content']['palettes']['last_edited'] =
    '{type_legend},type;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

// Palette für das Inhaltselement "stub" – nutzt die vorhandenen
// Standardfelder "headline" und "text" von tl_content, beide optional
// (Controller setzt bei leerem Feld einen Vorgabetext ein).
$GLOBALS['TL_DCA']['tl_content']['palettes']['stub'] =
    '{type_legend},type;'
    .'{headline_legend},headline;'
    .'{text_legend},text;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

// Abschnittstitel + Sprungleiste (rg-08, aus contao-inventar v1.4.2 – Namen sa_* unverändert).
// Überschrift = Standardfeld "headline"; Überzeile (sa_kicker) = Beschriftung in der Sprungleiste.
$GLOBALS['TL_DCA']['tl_content']['palettes']['sa_abschnitt'] =
    '{type_legend},type,headline,sa_kicker,sa_sprungtext;'
    .'{text_legend},sa_lead;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['sa_sprungleiste'] =
    '{type_legend},type;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['sa_kicker'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

// Eigene Beschriftung in der Sprungleiste (rg-21), leer = Überzeile, sonst Überschrift
$GLOBALS['TL_DCA']['tl_content']['fields']['sa_sprungtext'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['sa_lead'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr'],
    'sql' => 'text NULL',
];

// Steckbrief aus der Geräte-Datenbank (rg-10)
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_steckbrief'] =
    '{type_legend},type,headline;'
    .'{geraet_legend},retro_geraet;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['retro_geraet'] = [
    'inputType' => 'select',
    'foreignKey' => 'tl_retro_geraet.name',
    'eval' => ['mandatory' => true, 'chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];

// Mehrere Geräte (rg-23, Zubehör). Reihenfolge der Auswahl = Reihenfolge in der Tabelle.
$GLOBALS['TL_DCA']['tl_content']['fields']['retro_geraete'] = [
    'inputType' => 'select',
    'foreignKey' => 'tl_retro_geraet.name',
    'eval' => ['mandatory' => true, 'multiple' => true, 'chosen' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
    'relation' => ['type' => 'hasMany', 'load' => 'lazy'],
];

// Unterschiede zum Basismodell aus der Geräte-Datenbank (rg-22), nutzt das Feld retro_geraet
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_unterschiede'] =
    '{type_legend},type,headline;'
    .'{geraet_legend},retro_geraet;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

// Zubehör-Tabelle aus der Geräte-Datenbank (rg-12), mehrere Geräte seit rg-23
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_zubehoer'] =
    '{type_legend},type,headline;'
    .'{geraet_legend},retro_geraete;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

// Zitat mit Übersetzung und Nachweis (rg-17)
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_zitat'] =
    '{type_legend},type;'
    .'{zitat_legend},zitat_text,zitat_sprache,zitat_uebersetzung;'
    .'{nachweis_legend},zitat_name,zitat_zusatz,zitat_quelle_text,zitat_quelle_url;'
    .'{zitat_bild_legend},zitat_bild,size;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_text'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['mandatory' => true, 'tl_class' => 'clr'],
    'sql' => 'text NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_sprache'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 16, 'rgxp' => 'language', 'tl_class' => 'w50 clr'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_uebersetzung'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr'],
    'sql' => 'text NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_name'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql' => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_zusatz'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql' => "varchar(128) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_quelle_text'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

// Porträt (rg-18). Eigenes Feld statt singleSRC, weil singleSRC im Core Pflicht ist.
$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_bild'] = [
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'clr'],
    'sql' => ['type' => 'binary', 'length' => 16, 'fixed' => true, 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['zitat_quelle_url'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

// Quellen (rg-24): Liste mit Anker je Eintrag, selbst Sprungziel (Leistentext sa_sprungtext)
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_quellen'] =
    '{type_legend},type,headline,sa_sprungtext;'
    .'{quellen_legend},quellen_liste,quellen_kennung;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['quellen_liste'] = [
    'exclude' => true,
    'inputType' => 'rowWizard',
    'fields' => [
        'herausgeber' => ['label' => &$GLOBALS['TL_LANG']['tl_content']['quellen_herausgeber'], 'inputType' => 'text'],
        'titel' => ['label' => &$GLOBALS['TL_LANG']['tl_content']['quellen_titel'], 'inputType' => 'text'],
        'url' => ['label' => &$GLOBALS['TL_LANG']['tl_content']['quellen_url'], 'inputType' => 'text'],
        'bezug' => ['label' => &$GLOBALS['TL_LANG']['tl_content']['quellen_bezug'], 'inputType' => 'text'],
        'abgerufen' => ['label' => &$GLOBALS['TL_LANG']['tl_content']['quellen_abgerufen'], 'inputType' => 'text'],
    ],
    'eval' => ['tl_class' => 'clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['quellen_kennung'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 32, 'rgxp' => 'alnum', 'tl_class' => 'w50 clr'],
    'sql' => "varchar(32) NOT NULL default ''",
];

// Spielegalerie (rg-25): Spiele mit „In der Galerie zeigen“ der gewählten Geräte
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_spielegalerie'] =
    '{type_legend},type,headline;'
    .'{geraet_legend},retro_geraete;'
    .'{spielegalerie_legend},spielegalerie_angaben,spielegalerie_max,size;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['spielegalerie_angaben'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['jahr', 'genre', 'publisher', 'entwickler', 'programmierer', 'grafiker', 'musiker'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['spielegalerie_angaben_opt'],
    'default' => ['jahr', 'genre'],
    'eval' => ['multiple' => true, 'tl_class' => 'w50 clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['spielegalerie_max'] = [
    'exclude' => true,
    'inputType' => 'text',
    'default' => 12,
    'eval' => ['rgxp' => 'natural', 'minval' => 1, 'maxval' => 48, 'nospace' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 12],
];

// Kacheln + Kachel (rg-20). Kacheln ist ein Rahmen mit Kindelementen (nur retro_kachel).
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_kacheln'] =
    '{type_legend},type,headline;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_kachel'] =
    '{type_legend},type;'
    .'{kachel_legend},kachel_marke,kachel_linktext;'
    .'{kachel_ziel_legend},kachel_abschnitt,kachel_url;'
    .'{kachel_bild_legend},kachel_bild,size;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['kachel_marke'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['kachel_linktext'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 128, 'tl_class' => 'w50'],
    'sql' => "varchar(128) NOT NULL default ''",
];

// Optionen: KachelListener (Abschnittstitel im selben Artikel)
$GLOBALS['TL_DCA']['tl_content']['fields']['kachel_abschnitt'] = [
    'exclude' => true,
    'inputType' => 'select',
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['kachel_url'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['kachel_bild'] = [
    'exclude' => true,
    'inputType' => 'fileTree',
    'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'extensions' => '%contao.image.valid_extensions%', 'tl_class' => 'clr'],
    'sql' => ['type' => 'binary', 'length' => 16, 'fixed' => true, 'notnull' => false],
];

// Timeline (rg-27): News mit „In der Timeline zeigen“ + Einträge aus Retro-Datenbank › Timeline
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_timeline'] =
    '{type_legend},type,headline,timeline_einleitung;'
    .'{timeline_legend},timeline_archive,timeline_reihenfolge,timeline_max,timeline_pause_monate,timeline_kompakt;'
    .'{timeline_mehr_legend:hide},timeline_mehr_url,timeline_mehr_text;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_einleitung'] = [
    'exclude' => true,
    'inputType' => 'textarea',
    'eval' => ['tl_class' => 'clr', 'rows' => 3],
    'sql' => ['type' => 'text', 'notnull' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_archive'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_news_archive.title',
    'eval' => ['multiple' => true, 'tl_class' => 'clr'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_reihenfolge'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['asc', 'desc'],
    'reference' => &$GLOBALS['TL_LANG']['tl_content']['timeline_reihenfolge_opt'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'string', 'length' => 4, 'default' => 'asc'],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_max'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'natural', 'maxval' => 500, 'nospace' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_pause_monate'] = [
    'exclude' => true,
    'inputType' => 'text',
    'default' => 6,
    'eval' => ['rgxp' => 'natural', 'maxval' => 120, 'nospace' => true, 'tl_class' => 'w50'],
    'sql' => ['type' => 'smallint', 'unsigned' => true, 'default' => 6],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_kompakt'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 m12'],
    'sql' => ['type' => 'boolean', 'default' => false],
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_mehr_url'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['timeline_mehr_text'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
