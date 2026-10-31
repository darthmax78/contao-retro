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
