<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Backend;

use Contao\BackendModule;
use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Message;
use Contao\System;
use Darthmax\RetroBundle\Einspieler\Spool;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

/**
 * Backend-Modul „Arbeitspakete“: nur für Administratoren mit Zwei-Faktor-Anmeldung.
 *
 * Führt selbst nichts aus, sondern übergibt an den Dienst rg-einspieler (siehe Spool).
 */
class ArbeitspaketeModul extends BackendModule
{
    public function generate()
    {
        $container = System::getContainer();
        $user = BackendUser::getInstance();

        if (!$container->get('security.helper')->isGranted('IS_AUTHENTICATED_FULLY') || !$user->isAdmin) {
            throw new AccessDeniedException('Das Modul „Arbeitspakete“ ist nur für Administratoren.');
        }

        $spool = new Spool($container->getParameter('kernel.project_dir'));
        $zweiFaktor = (bool) $user->useTwoFactor;
        $eingerichtet = $spool->istEingerichtet();
        $request = $container->get('request_stack')->getCurrentRequest();

        if ($zweiFaktor && $eingerichtet && $request->isMethod('POST')) {
            $this->verarbeiten($request, $spool);
            Controller::reload();
        }

        $pakete = $eingerichtet ? $spool->pakete() : [];
        $laeuft = $eingerichtet && $spool->auftraegeOffen();

        foreach ($pakete as $paket) {
            $laeuft = $laeuft || $paket['laeuft'];
        }

        return $container->get('twig')->render('@Contao/backend/retro_arbeitspakete.html.twig', [
            'meldungen' => Message::generate(),
            'zwei_faktor' => $zweiFaktor,
            'eingerichtet' => $eingerichtet,
            'pakete' => $pakete,
            'laeuft' => $laeuft,
            'request_token' => $container->get('contao.csrf.token_manager')->getDefaultTokenValue(),
        ]);
    }

    protected function compile(): void
    {
    }

    private function verarbeiten(Request $request, Spool $spool): void
    {
        try {
            match ($request->request->get('FORM_SUBMIT')) {
                'retro_arbeitspaket_upload' => $this->hochladen($request, $spool),
                'retro_arbeitspaket_aktion' => $spool->beauftragen(
                    (string) $request->request->get('aktion'),
                    (string) $request->request->get('paket'),
                ),
                default => null,
            };
        } catch (\RuntimeException $e) {
            Message::addError($e->getMessage());
        }
    }

    private function hochladen(Request $request, Spool $spool): void
    {
        $paket = $request->files->get('paket');
        $signatur = $request->files->get('signatur');

        if (!$paket instanceof UploadedFile || !$signatur instanceof UploadedFile) {
            throw new \RuntimeException('Bitte Paket und Signatur auswählen.');
        }

        Message::addConfirmation($spool->annehmen($paket, $signatur).' hochgeladen, Signatur wird geprüft.');
    }
}
