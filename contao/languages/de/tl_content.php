<?php

$GLOBALS['TL_LANG']['tl_content']['sa_kicker'] = [
    'Überzeile',
    'Kurze Zeile über der Überschrift im Terminal-Look, z. B. „Lynx · 1989“. Erscheint auch in der Sprungleiste, außer dort ist ein eigener Text gesetzt.',
];

$GLOBALS['TL_LANG']['tl_content']['sa_sprungtext'] = [
    'Text in der Sprungleiste',
    'Optional, kurz halten, z. B. „Lynx“. Leer = Überzeile, ohne Überzeile die Überschrift.',
];

$GLOBALS['TL_LANG']['tl_content']['sa_lead'] = [
    'Einleitung',
    'Optionaler kurzer Text unter der Überschrift. Zeilenumbrüche bleiben erhalten.',
];

$GLOBALS['TL_LANG']['tl_content']['geraet_legend'] = 'Gerät';
$GLOBALS['TL_LANG']['tl_content']['retro_geraet'] = [
    'Gerät',
    'Eintrag aus der Geräte-Datenbank. Unveröffentlichte Geräte erscheinen nur im Backend und in der Vorschau.',
];

$GLOBALS['TL_LANG']['tl_content']['retro_geraete'] = [
    'Geräte',
    'Ein oder mehrere Einträge aus der Geräte-Datenbank. Zubehör: Bei mehreren Geräten steht dabei, wozu ein Teil passt. Spielegalerie: Spiele aller gewählten Geräte.',
];

// Spielegalerie (rg-25)
$GLOBALS['TL_LANG']['tl_content']['spielegalerie_legend'] = 'Galerie';
$GLOBALS['TL_LANG']['tl_content']['spielegalerie_angaben'] = [
    'Angaben unter dem Namen',
    'Am besten zwei auswählen, sie stehen in einer Zeile, getrennt durch „·“.',
];
$GLOBALS['TL_LANG']['tl_content']['spielegalerie_angaben_opt'] = [
    'jahr' => 'Jahr',
    'genre' => 'Genre',
    'publisher' => 'Publisher',
    'entwickler' => 'Entwickler',
];
$GLOBALS['TL_LANG']['tl_content']['spielegalerie_max'] = [
    'Höchstens',
    'Anzahl der Spiele (Vorgabe 12). 6, 9 oder 12 füllen das Raster mit 3 Spalten.',
];

// Quellen (rg-24)
$GLOBALS['TL_LANG']['tl_content']['quellen_legend'] = 'Quellen';
$GLOBALS['TL_LANG']['tl_content']['quellen_liste'] = [
    'Quellen',
    'Je Zeile eine Quelle, Reihenfolge = Nummer. Herausgeber oder Titel genügt; Link mit https://, /pfad oder #anker. „Wofür“ z. B. „Modelle, Preise“, „Abgerufen“ z. B. 07.11.2026.',
];
$GLOBALS['TL_LANG']['tl_content']['quellen_herausgeber'] = ['Herausgeber / Website'];
$GLOBALS['TL_LANG']['tl_content']['quellen_titel'] = ['Titel'];
$GLOBALS['TL_LANG']['tl_content']['quellen_url'] = ['Link'];
$GLOBALS['TL_LANG']['tl_content']['quellen_bezug'] = ['Wofür'];
$GLOBALS['TL_LANG']['tl_content']['quellen_abgerufen'] = ['Abgerufen'];
$GLOBALS['TL_LANG']['tl_content']['quellen_kennung'] = [
    'Anker-Kennung',
    'Optional. Leer: Anker quelle-1, quelle-2 … Mit „lynx“: quelle-lynx-1 … – nur nötig, wenn mehrere Quellenblöcke auf einer Seite stehen.',
];

// Zitat (rg-17)
$GLOBALS['TL_LANG']['tl_content']['zitat_legend'] = 'Zitat';
$GLOBALS['TL_LANG']['tl_content']['nachweis_legend'] = 'Nachweis';
$GLOBALS['TL_LANG']['tl_content']['zitat_text'] = [
    'Zitat',
    'Der Originalwortlaut ohne Anführungszeichen. Jede Zeile wird ein eigener Absatz.',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_sprache'] = [
    'Sprache des Zitats',
    'Sprachkürzel, wenn das Zitat nicht deutsch ist, z. B. „en“. Damit lesen Screenreader es richtig vor.',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_uebersetzung'] = [
    'Übersetzung',
    'Optional. Erscheint kursiv unter dem Original.',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_name'] = [
    'Name',
    'Wer das gesagt hat, z. B. „Dave Needle“.',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_zusatz'] = [
    'Zusatz',
    'Lebensdaten oder Rolle, z. B. „(1947 – 2016), Lynx-Mitentwickler“.',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_quelle_text'] = [
    'Linktext der Quelle',
    'z. B. „Quelle [1]“. Leer = „Quelle“.',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_bild_legend'] = 'Porträt';
$GLOBALS['TL_LANG']['tl_content']['zitat_bild'] = [
    'Porträtfoto',
    'Optional. Erscheint mit abgerundeten Ecken über dem Namen. Ohne Bildgröße wird es quadratisch zugeschnitten (144 × 144 px, angezeigt mit 72 px).',
];
$GLOBALS['TL_LANG']['tl_content']['zitat_quelle_url'] = [
    'Link zur Quelle',
    'Anker im Quellenblock (z. B. „#quelle-lynx-1“), Seite oder Webadresse. Leer = kein Link.',
];

// Kacheln (rg-20)
$GLOBALS['TL_LANG']['tl_content']['kachel_legend'] = 'Kachel';
$GLOBALS['TL_LANG']['tl_content']['kachel_ziel_legend'] = 'Ziel';
$GLOBALS['TL_LANG']['tl_content']['kachel_bild_legend'] = 'Bild';
$GLOBALS['TL_LANG']['tl_content']['kachel_marke'] = [
    'Marke',
    'Kurze Beschriftung oben, z. B. „Lynx (1989)“.',
];
$GLOBALS['TL_LANG']['tl_content']['kachel_linktext'] = [
    'Linktext',
    'Text unten, z. B. „Der Atari Lynx“. Der Pfeil kommt automatisch.',
];
$GLOBALS['TL_LANG']['tl_content']['kachel_abschnitt'] = [
    'Abschnitt auf dieser Seite',
    'Sprung zu einem Abschnittstitel im selben Artikel. Hat Vorrang vor „Link“.',
];
$GLOBALS['TL_LANG']['tl_content']['kachel_url'] = [
    'Link',
    'Andere Seite oder Webadresse, falls kein Abschnitt gewählt ist. Ohne Ziel ist die Kachel nicht klickbar.',
];
$GLOBALS['TL_LANG']['tl_content']['kachel_bild'] = [
    'Bild',
    'z. B. die Strichzeichnung. Ohne Bildgröße höchstens 600 × 400 px, Seitenverhältnis bleibt.',
];
