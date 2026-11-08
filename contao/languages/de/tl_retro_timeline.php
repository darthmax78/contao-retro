<?php

$l = &$GLOBALS['TL_LANG']['tl_retro_timeline'];

$l['eintrag_legend'] = 'Eintrag';
$l['inhalt_legend'] = 'Inhalt';
$l['publish_legend'] = 'Veröffentlichung';
$l['typ'] = ['Art', 'Eintrag, Meilenstein (Amber) oder Pause (gestrichelt). Eine Pause ersetzt an ihrer Stelle die automatische.'];
$l['typ_opt'] = ['eintrag' => 'Eintrag', 'meilenstein' => 'Meilenstein', 'pause' => 'Pause'];
$l['datum'] = ['Datum', 'Bestimmt Position und Jahr. Bei einer Pause: ihr Beginn.'];
$l['datum_text'] = ['Anzeige des Datums', 'Optional, z. B. „08/2025 – 09/2026“ oder „Herbst 2024“. Leer = Datum.'];
$l['titel'] = ['Titel', 'Bei einer Pause optional (leer = „Pause“).'];
$l['text'] = ['Text', 'Ein bis zwei Sätze.'];
$l['tags'] = ['Tags', 'Durch Komma getrennt.'];
$l['url'] = ['Link', 'Optional: Seite, Anker oder Webadresse. Erscheint als „Zum Beitrag“.'];
$l['published'] = ['Veröffentlicht', 'Nur veröffentlichte Einträge erscheinen im Frontend.'];

unset($l);
