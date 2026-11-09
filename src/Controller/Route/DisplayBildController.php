<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller\Route;

use Darthmax\RetroBundle\Spiele\DisplayFaerber;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Liefert einen Screenshot im Look eines anderen Displays (rg-29), erzeugt ihn beim ersten Abruf.
 *
 * Nur Dateien, die als Screenshot bei einem Spiel hinterlegt sind – sonst ließe sich über die
 * Route jedes Bild der Dateiverwaltung abrufen, auch aus geschützten Ordnern.
 * Die Spielegalerie verlinkt fertige Bilder direkt (assets/…) und nur fehlende über diese Route.
 */
#[AsController]
class DisplayBildController
{
    public function __construct(
        private readonly DisplayFaerber $faerber,
        private readonly Connection $db,
    ) {
    }

    #[Route(
        '/_retro/display/{look}/{breite}/{uuid}.webp',
        name: 'darthmax_retro_display',
        requirements: ['look' => 'dmg|pocket|light', 'breite' => '600|1000', 'uuid' => '[0-9a-f]{32}'],
        methods: ['GET', 'HEAD'],
    )]
    public function __invoke(string $look, int $breite, string $uuid, Request $request): BinaryFileResponse
    {
        $binaer = (string) hex2bin($uuid);
        $pfad = $this->db->fetchOne("SELECT path FROM tl_files WHERE uuid = ? AND type = 'file'", [$binaer]);

        if (false === $pfad || !$this->db->fetchOne('SELECT 1 FROM tl_retro_spiel WHERE INSTR(screenshots, ?) > 0 LIMIT 1', [$binaer])) {
            throw new NotFoundHttpException('Kein Screenshot.');
        }

        $datei = $this->faerber->faerben((string) $pfad, $look, $breite);

        if (null === $datei) {
            throw new NotFoundHttpException('Screenshot lässt sich nicht umfärben.');
        }

        $antwort = new BinaryFileResponse($datei, 200, ['Content-Type' => 'image/webp'], true, null, true);
        $antwort->setPublic();
        $antwort->setMaxAge(86400);
        $antwort->isNotModified($request);

        return $antwort;
    }
}
