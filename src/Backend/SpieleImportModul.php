<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Backend;

use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\DataContainer;
use Contao\System;
use Darthmax\RetroBundle\Spiele\SpieleCsv;
use Darthmax\RetroBundle\Spiele\SpieleImport;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * CSV-Import der Spiele-Datenbank (rg-25), aufgerufen über die Schaltfläche „CSV-Import“
 * in der Spieleliste (do=retro_spiele&key=csv_import).
 *
 * Ablauf: Datei hochladen → Probelauf mit Bericht → „Jetzt importieren“ schickt denselben
 * Inhalt (Base64 im Formular) noch einmal und schreibt. Nichts wird ohne Probelauf geschrieben.
 */
class SpieleImportModul
{
    private const MAX_BYTES = 2 * 1024 * 1024;

    public function erzeugen(DataContainer $dc): string
    {
        $container = System::getContainer();
        $user = BackendUser::getInstance();

        if (!$user->isAdmin && !$user->hasAccess('retro_spiele', 'modules')) {
            throw new AccessDeniedException('Kein Zugriff auf die Spiele-Datenbank.');
        }

        $request = $container->get('request_stack')->getCurrentRequest();
        $zurueck = preg_replace('/&(amp;)?key=csv_import/', '', (string) $request->getRequestUri());
        $optionen = [
            'bildordner' => 'files/Bilder/Spiele',
            'veroeffentlichen' => true,
        ];
        $daten = [
            'zurueck' => $zurueck,
            'request_token' => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
            'fehler' => [],
            'hinweise' => [],
            'bericht' => null,
            'probelauf' => true,
            'inhalt' => '',
            'dateiname' => '',
        ];

        if ($request->isMethod('POST') && 'retro_spiele_import' === $request->request->get('FORM_SUBMIT')) {
            $optionen['bildordner'] = trim((string) $request->request->get('bildordner', $optionen['bildordner']));
            $optionen['veroeffentlichen'] = '1' === $request->request->get('veroeffentlichen');
            $probelauf = 'importieren' !== $request->request->get('schritt');
            $inhalt = null;

            $datei = $request->files->get('csv');

            if ($datei instanceof UploadedFile && $datei->isValid()) {
                if ($datei->getSize() > self::MAX_BYTES) {
                    $daten['fehler'][] = 'Die Datei ist größer als 2 MB.';
                } else {
                    $inhalt = (string) file_get_contents($datei->getPathname());
                    $daten['dateiname'] = $datei->getClientOriginalName();
                    $probelauf = true; // Neue Datei: immer zuerst Probelauf
                }
            } elseif ('' !== (string) $request->request->get('inhalt')) {
                $inhalt = base64_decode((string) $request->request->get('inhalt'), true);
                $daten['dateiname'] = (string) $request->request->get('dateiname');

                if (false === $inhalt) {
                    $inhalt = null;
                    $daten['fehler'][] = 'Der Inhalt aus dem Probelauf ist beschädigt. Bitte die Datei noch einmal hochladen.';
                }
            } else {
                $daten['fehler'][] = 'Bitte eine CSV-Datei auswählen.';
            }

            if (null !== $inhalt) {
                $gelesen = (new SpieleCsv())->lesen($inhalt);
                $daten['fehler'] = [...$daten['fehler'], ...$gelesen['fehler']];
                $daten['hinweise'] = $gelesen['hinweise'];

                if (!$gelesen['fehler']) {
                    $import = new SpieleImport($container->get('database_connection'));

                    try {
                        $daten['bericht'] = $import->ausfuehren($gelesen['zeilen'], $optionen['bildordner'], $optionen['veroeffentlichen'], $probelauf);
                        $daten['probelauf'] = $probelauf;
                        $daten['inhalt'] = $probelauf ? base64_encode($inhalt) : '';
                    } catch (\Throwable $e) {
                        $daten['fehler'][] = 'Import abgebrochen, nichts gespeichert: '.$e->getMessage();
                    }
                }
            }
        }

        $daten['optionen'] = $optionen;

        return $container->get('twig')->render('@Contao/backend/retro_spiele_import.html.twig', $daten);
    }
}
