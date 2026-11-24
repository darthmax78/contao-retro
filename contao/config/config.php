<?php

use Darthmax\RetroBundle\Backend\ArbeitspaketeModul;
use Darthmax\RetroBundle\Backend\SpieleImportModul;

// Backend-Bereich „Geräte“ (rg-09). Bezeichnungen: contao/languages/de/modules.php
$GLOBALS['BE_MOD']['retro'] = [
    'retro_geraete' => [
        'tables' => [
            'tl_retro_geraet',
            'tl_retro_geraet_firma',
            'tl_retro_geraet_komponente',
            'tl_retro_release',
            'tl_retro_quelle',
        ],
    ],
    // rg-25: Spiele je Plattform, CSV-Import über die Listen-Schaltfläche (key=csv_import)
    'retro_spiele' => [
        'tables' => ['tl_retro_spiel'],
        'csv_import' => [SpieleImportModul::class, 'erzeugen'],
    ],
    'retro_firmen' => ['tables' => ['tl_retro_firma']],
    'retro_komponenten' => ['tables' => ['tl_retro_komponente']],
    'retro_zubehoer' => ['tables' => ['tl_retro_zubehoer']],
    // rg-15: nur Administratoren mit Zwei-Faktor-Anmeldung, prüft das Modul selbst
    'retro_arbeitspakete' => ['callback' => ArbeitspaketeModul::class],
];

// rg-34: Bereich „Chronik“ wie in den anderen Projekten (Chronik-Paket)
// Tagesprotokoll: schreibt der Contao-Cronjob jede Nacht (src/Chronik/Cron/JournalCron.php)
// Timeline: Stationen ohne eigene News und Pausen für das Element Timeline (rg-27)
$GLOBALS['BE_MOD']['chronik'] = [
    'chronik_journal' => ['tables' => ['tl_chronik_journal']],
    'retro_timeline' => ['tables' => ['tl_retro_timeline']],
];
