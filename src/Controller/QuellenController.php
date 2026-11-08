<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Link\SeitenAnker;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Quellen (rg-24): nummerierte Quellenliste am Ende eines Artikels.
 *
 * Jeder Eintrag bekommt den Anker quelle-N (mit Kennung quelle-<kennung>-N) – Ziel für den
 * Quellenlink im Zitat oder für Verweise im Text. Das Element ist selbst Sprungziel der
 * Sprungleiste (abschnitt-<id>, Beschriftung: Leistentext, sonst Überschrift, sonst „Quellen“).
 * Links: nur http(s), Seitenpfade und #Anker; alles andere wird als reiner Text ausgegeben.
 */
#[AsContentElement('retro_quellen', category: 'texts')]
class QuellenController extends AbstractContentElementController
{
    public function __construct(private readonly SeitenAnker $seitenAnker)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $kennung = trim((string) preg_replace('/[^a-z0-9]+/', '-', strtolower((string) $model->quellen_kennung)), '-');
        $praefix = '' === $kennung ? 'quelle-' : 'quelle-'.$kennung.'-';
        $eintraege = [];

        foreach (StringUtil::deserialize($model->quellen_liste, true) as $zeile) {
            if (!\is_array($zeile)) {
                continue;
            }

            $feld = static fn (string $name): string => trim(StringUtil::decodeEntities((string) ($zeile[$name] ?? '')));
            $eintrag = [
                'herausgeber' => $feld('herausgeber'),
                'titel' => $feld('titel'),
                'url' => $this->link($feld('url')),
                'bezug' => $feld('bezug'),
                'abgerufen' => $feld('abgerufen'),
            ];

            if ('' === $eintrag['herausgeber'].$eintrag['titel']) {
                continue;
            }

            $eintrag['anker'] = $praefix.(\count($eintraege) + 1);
            $eintraege[] = $eintrag;
        }

        if (!$eintraege && !$this->isBackendScope($request)) {
            return new Response();
        }

        $headline = StringUtil::deserialize($model->headline, true);
        $titel = trim((string) ($headline['value'] ?? ''));

        $template->set('quellen_titel', '' !== $titel ? $titel : 'Quellen');
        $template->set('quellen_anker', 'abschnitt-'.$model->id);
        $template->set('quellen_eintraege', $eintraege);
        $template->set('quellen_praefix', $praefix);

        return $template->getResponse();
    }

    private function link(string $url): string
    {
        if ('' === $url) {
            return '';
        }

        if (str_starts_with($url, '#')) {
            return $this->seitenAnker->aufSeite($url);
        }

        if (preg_match('~^(https?://|/|\{\{)~i', $url)) {
            return $url;
        }

        return '';
    }
}
