<?php

$l = &$GLOBALS['TL_LANG']['tl_retro_release'];

$l['release_legend'] = 'Datum und Region';
$l['preis_legend'] = 'Preis';
$l['anmerkung_legend'] = 'Anmerkung';
$l['art'] = ['Art', 'Marktstart oder spätere Preisänderung.'];
$l['region'] = ['Region', 'Markt, für den der Eintrag gilt.'];
$l['jahr'] = ['Jahr', 'Pflichtfeld.'];
$l['monat'] = ['Monat', 'Leer lassen, wenn unbekannt.'];
$l['tag'] = ['Tag', 'Leer lassen, wenn unbekannt.'];
$l['datum_text'] = ['Datum (Anzeige)', 'z. B. „Herbst 1989“.'];
$l['preis'] = ['Preis', 'Zahl in der Originalwährung.'];
$l['waehrung'] = ['Währung', 'Originalwährung, nicht umrechnen.'];
$l['preis_text'] = ['Preis (Anzeige)', 'z. B. „99 US$ (Basis), 120 US$ (Deluxe)“.'];
$l['anmerkung'] = ['Anmerkung', 'Interner oder ergänzender Hinweis.'];
$l['art_opt'] = ['marktstart' => 'Marktstart', 'preisaenderung' => 'Preisänderung'];
$l['region_opt'] = [
    'welt' => 'Weltweit', 'jp' => 'Japan', 'us' => 'USA', 'eu' => 'Europa', 'de' => 'Deutschland',
    'at' => 'Österreich', 'uk' => 'Großbritannien', 'fr' => 'Frankreich', 'au' => 'Australien',
];
$l['waehrung_opt'] = [
    'USD' => 'US-Dollar', 'JPY' => 'Yen', 'DEM' => 'D-Mark', 'ATS' => 'Schilling', 'EUR' => 'Euro',
    'GBP' => 'Pfund', 'FRF' => 'Französischer Franc', 'AUD' => 'Australischer Dollar', 'CHF' => 'Schweizer Franken',
];

unset($l);
