<?php

use Darthmax\RetroBundle\Spiele\Genres;

$l = &$GLOBALS['TL_LANG']['tl_retro_spiel'];

$l['spiel_legend'] = 'Spiel';
$l['firmen_legend'] = 'Firmen';
$l['markt_legend'] = 'Erscheinen';
$l['bilder_legend'] = 'Screenshots';
$l['galerie_legend'] = 'Galerie im Konsolenartikel';
$l['text_legend'] = 'Beschreibung und Quelle';
$l['publish_legend'] = 'Veröffentlichung';

$l['name'] = ['Name', 'Titel des Spiels, wie er auf dem Modul steht.'];
$l['geraet'] = ['Gerät', 'Plattform aus der Geräte-Datenbank. Für jede Plattform ein eigener Eintrag.'];
$l['genre'] = ['Genre', ''];
$l['genre_opt'] = Genres::LISTE;
$l['spieler'] = ['Spieler', 'z. B. „1“, „1–4 (ComLynx)“'];
$l['entwickler'] = ['Entwickler', 'Aus dem Firmen-Katalog.'];
$l['publisher'] = ['Publisher', 'Aus dem Firmen-Katalog.'];
$l['jahr'] = ['Jahr', 'Erscheinungsjahr.'];
$l['monat'] = ['Monat', 'Optional, 1–12.'];
$l['tag'] = ['Tag', 'Optional, 1–31.'];
$l['region'] = ['Region', 'Region der Angabe, z. B. „USA“ oder „EU“.'];
$l['modellnummer'] = ['Modellnummer', 'z. B. „PA2025“.'];
$l['modul_kb'] = ['Modulgröße (KB)', 'z. B. 128, 256, 512 oder 1024.'];
$l['screenshots'] = ['Screenshots', 'Höchstens 6. Das erste Bild der Sortierung steht in der Galerie, alle zusammen in der Großansicht.'];
$l['galerie'] = ['In der Galerie zeigen', 'Das Spiel erscheint im Element „Spielegalerie“ seines Geräts.'];
$l['sortierung'] = ['Sortierung in der Galerie', 'Kleinere Zahl weiter vorne. 0 = nach den nummerierten, alphabetisch.'];
$l['beschreibung'] = ['Beschreibung', 'Optional, kurz.'];
$l['quelle_text'] = ['Quelle', 'Woher die Angaben stammen.'];
$l['published'] = ['Veröffentlicht', 'Nur veröffentlichte Spiele erscheinen auf der Website.'];

$l['csv_import'] = ['CSV-Import', 'Spiele aus einer CSV-Datei anlegen oder aktualisieren'];
