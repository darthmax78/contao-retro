<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

// Öffentlicher Anzeigename für die "Zuletzt bearbeitet"-Box.
// Derzeit NICHT ausgewertet (die Box zeigt tl_user.username). Bleibt erhalten,
// damit sich das Datenbankschema beim Umzug ins Bundle nicht ändert.
$GLOBALS['TL_DCA']['tl_user']['fields']['nickname'] = [
    'exclude'   => true,
    'search'    => true,
    'inputType' => 'text',
    'eval'      => ['maxlength' => 64, 'tl_class' => 'w50'],
    'sql'       => "varchar(64) NOT NULL default ''",
];

// Direkt nach dem Namen einfügen, auch unter "Persönliche Daten" (Palette login)
PaletteManipulator::create()
    ->addField('nickname', 'name', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('login', 'tl_user')
    ->applyToPalette('admin', 'tl_user')
    ->applyToPalette('default', 'tl_user')
    ->applyToPalette('group', 'tl_user')
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;
