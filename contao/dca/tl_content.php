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
    '{type_legend},type,headline,sa_kicker;'
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

// Zubehör-Tabelle aus der Geräte-Datenbank (rg-12)
$GLOBALS['TL_DCA']['tl_content']['palettes']['retro_zubehoer'] =
    '{type_legend},type,headline;'
    .'{geraet_legend},retro_geraet;'
    .'{template_legend:hide},customTpl;'
    .'{protected_legend:hide},protected;'
    .'{expert_legend:hide},guests,cssID;'
    .'{invisible_legend:hide},invisible,start,stop';
