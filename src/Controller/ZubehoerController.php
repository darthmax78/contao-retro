<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Steckbrief\GeraetLader;
use Darthmax\RetroBundle\Steckbrief\SteckbriefBauer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tabelle des Original-Zubehörs eines Geräts. Ohne veröffentlichtes Zubehör bleibt das
 * Element leer. Der Tabellen-Tag deckt neue, geänderte und gelöschte Teile ab.
 */
#[AsContentElement('retro_zubehoer', category: 'texts')]
class ZubehoerController extends AbstractContentElementController
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

        if (!$daten['geraet']['published'] && !$this->isBackendScope($request) && !$this->tokenChecker->isPreviewMode()) {
            return new Response();
        }

        $this->tagResponse(['contao.db.tl_retro_zubehoer', 'contao.db.tl_retro_geraet.'.$daten['geraet']['id']]);

        $teile = $this->bauer->zubehoer($this->lader->zubehoer((int) $daten['geraet']['id']));

        if (!$teile) {
            return new Response();
        }

        $headline = StringUtil::deserialize($model->headline, true);
        $eigenerTitel = trim((string) ($headline['value'] ?? ''));
        $tag = (string) ($headline['unit'] ?? '');

        $template->set('zubehoer_titel', '' !== $eigenerTitel ? $eigenerTitel : $this->bauer->kopf($daten)['titel']);
        $template->set('zubehoer_tag', \in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $tag : 'h3');
        $template->set('zubehoer_teile', $teile);

        return $template->getResponse();
    }
}
