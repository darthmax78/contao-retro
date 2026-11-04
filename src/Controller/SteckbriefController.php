<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Darthmax\RetroBundle\Steckbrief\GeraetLader;
use Darthmax\RetroBundle\Steckbrief\SteckbriefBauer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Steckbrief eines Geräts aus der Geräte-Datenbank, im Look von "retro-table steckbrief".
 *
 * Unveröffentlichte Geräte erscheinen nur im Backend und in der Vorschau.
 * Die Cache-Tags decken Gerät, Basismodell, Firmen und Komponenten ab; Änderungen an den
 * Kindtabellen löst Contao über den Tag des übergeordneten Geräts mit aus.
 */
#[AsContentElement('retro_steckbrief', category: 'texts')]
class SteckbriefController extends AbstractContentElementController
{
    public function __construct(
        private readonly GeraetLader $lader,
        private readonly SteckbriefBauer $bauer,
        private readonly TokenChecker $tokenChecker,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $daten = $this->lader->lade((int) $model->retro_geraet);

        if (null === $daten) {
            return new Response();
        }

        $sichtbar = (bool) $daten['geraet']['published'];

        if (!$sichtbar && !$this->isBackendScope($request) && !$this->tokenChecker->isPreviewMode()) {
            return new Response();
        }

        $tags = ['contao.db.tl_retro_geraet.'.$daten['geraet']['id']];

        if ($daten['basis']) {
            $tags[] = 'contao.db.tl_retro_geraet.'.$daten['basis']['id'];
        }

        foreach ($daten['firmen'] as $firma) {
            $tags[] = 'contao.db.tl_retro_firma.'.$firma['firma_id'];
        }

        foreach ($daten['komponenten'] as $komponente) {
            $tags[] = 'contao.db.tl_retro_komponente.'.$komponente['komponente_id'];
        }

        $this->tagResponse($tags);

        $template->set('steckbrief_gruppen', $this->bauer->baue($daten));
        $template->set('steckbrief_entwurf', !$sichtbar);

        return $template->getResponse();
    }
}
