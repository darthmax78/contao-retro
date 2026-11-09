<?php

$l = &$GLOBALS['TL_LANG']['tl_retro_zubehoer'];

$l['zubehoer_legend'] = 'Zubehör';
$l['daten_legend'] = 'Daten';
$l['quelle_legend'] = 'Quelle';
$l['publish_legend'] = 'Veröffentlichung';
$l['name'] = ['Name', 'z. B. ComLynx-Kabel.'];
$l['art'] = ['Art', 'Bestimmt die Reihenfolge in der Tabelle.'];
$l['modellnummer'] = ['Modellnummer', 'z. B. PAG1200.'];
$l['geraete'] = ['Passt zu', 'Alle Geräte, zu denen dieses Teil gehört. Wird nicht vom Basismodell geerbt.'];
$l['daten_text'] = ['Technische Daten', 'Kurz, z. B. Ausgang und Stecker.'];
$l['marktstart_jahr'] = ['Marktstart', 'Jahr.'];
$l['produktionsende_jahr'] = ['Produktionsende', 'Jahr.'];
$l['beschreibung'] = ['Beschreibung', 'Optionaler Zusatz, erscheint in der Tabelle unter den Daten.'];
$l['quelle_text'] = ['Quelle', 'Interner Beleg, wird nicht ausgegeben.'];
$l['published'] = ['Veröffentlicht', 'Nur veröffentlichtes Zubehör erscheint im Frontend.'];
$l['art_opt'] = \Darthmax\RetroBundle\Steckbrief\GeraetLader::ZUBEHOER_ARTEN;

unset($l);
