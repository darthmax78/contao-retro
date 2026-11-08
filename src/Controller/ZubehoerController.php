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
 * Tabelle des Original-Zubehörs eines oder mehrerer Geräte (mehrere seit rg-23).
 *
 * Jedes Teil erscheint einmal; bei mehreren Geräten zeigt die Spalte „Passt zu“, zu welchen
 * der gewählten Geräte es gehört. Ohne veröffentlichtes Zubehör bleibt das Element leer.
 * Ältere Elemente ohne retro_geraete nutzen noch das Einzelfeld retro_geraet.
 * Der Tabellen-Tag deckt neue, geänderte und gelöschte Teile ab.
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
        $sichtbar = $this->isBackendScope($request) || $this->tokenChecker->isPreviewMode();

        $ids = array_values(array_unique(array_filter(array_map('intval', StringUtil::deserialize($model->retro_geraete, true)))));

        if (!$ids && (int) $model->retro_geraet > 0) {
            $ids = [(int) $model->retro_geraet];
        }

        $geraete = [];

        foreach ($ids as $id) {
            $daten = $this->lader->lade($id);

            if (null !== $daten && ($daten['geraet']['published'] || $sichtbar)) {
                $geraete[$id] = $daten;
            }
        }

        if (!$geraete) {
            return new Response();
        }

        $tags = ['contao.db.tl_retro_zubehoer'];

        foreach (array_keys($geraete) as $id) {
            $tags[] = 'contao.db.tl_retro_geraet.'.$id;
        }

        $this->tagResponse($tags);

        $namen = array_map(static fn (array $d): string => (string) $d['geraet']['name'], $geraete);
        $teile = $this->bauer->zubehoer($this->lader->zubehoer(...array_keys($geraete)), $namen);

        if (!$teile) {
            return new Response();
        }

        $headline = StringUtil::deserialize($model->headline, true);
        $eigenerTitel = trim((string) ($headline['value'] ?? ''));
        $tag = (string) ($headline['unit'] ?? '');

        // Automatischer Titel: erstes Gerät mit Hersteller, weitere nur mit Namen („Atari Lynx und Lynx II“)
        $titel = $this->bauer->kopf(reset($geraete))['titel'];
        $weitere = \array_slice($namen, 1);

        if ($weitere) {
            $letztes = array_pop($weitere);
            $titel = implode(', ', [$titel, ...$weitere]).' und '.$letztes;
        }

        $template->set('zubehoer_titel', '' !== $eigenerTitel ? $eigenerTitel : $titel);
        $template->set('zubehoer_tag', \in_array($tag, ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'], true) ? $tag : 'h3');
        $template->set('zubehoer_teile', $teile);
        $template->set('zubehoer_mehrere', \count($geraete) > 1);

        return $template->getResponse();
    }
}
