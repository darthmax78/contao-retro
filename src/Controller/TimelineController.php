<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Security\Authentication\Token\TokenChecker;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Darthmax\RetroBundle\Link\SeitenAnker;
use Darthmax\RetroBundle\Timeline\TimelineBauer;
use Darthmax\RetroBundle\Timeline\TimelineLader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Timeline (rg-27): Stationen der Seite als Zeitleiste im CRT-Look.
 *
 * Quellen: News mit „In der Timeline zeigen“ und Einträge aus Retro-Datenbank › Timeline.
 * Jahreszahlen und Pausen (Lücke ab X Monaten) entstehen automatisch. Einstellbar sind
 * Archive, Reihenfolge, Anzahl, Darstellung (kompakt ohne Text) und ein Link zur ganzen Geschichte.
 */
#[AsContentElement('retro_timeline', category: 'texts')]
class TimelineController extends AbstractContentElementController
{
    public function __construct(
        private readonly TimelineLader $lader,
        private readonly TokenChecker $tokenChecker,
        private readonly SeitenAnker $seitenAnker,
    ) {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $backend = $this->isBackendScope($request);
        $archive = array_values(array_filter(array_map('intval', StringUtil::deserialize($model->timeline_archive, true))));
        $neuesteZuerst = 'desc' === $model->timeline_reihenfolge;
        $kompakt = (bool) $model->timeline_kompakt;

        $this->tagResponse(['contao.db.tl_news', 'contao.db.tl_retro_timeline']);

        $folge = TimelineBauer::baue(
            $this->lader->eintraege($archive, $backend || $this->tokenChecker->isPreviewMode()),
            $neuesteZuerst,
            (int) $model->timeline_max,
            (int) $model->timeline_pause_monate,
        );

        if (!$folge && !$backend) {
            return new Response();
        }

        $mehrUrl = trim(StringUtil::decodeEntities((string) $model->timeline_mehr_url));
        $mehrText = trim(StringUtil::decodeEntities((string) $model->timeline_mehr_text));

        $template->set('timeline_folge', $folge);
        $template->set('timeline_einleitung', trim(StringUtil::decodeEntities((string) $model->timeline_einleitung)));
        $template->set('timeline_kompakt', $kompakt);
        $template->set('timeline_neueste_zuerst', $neuesteZuerst);
        $template->set('timeline_legende', !$kompakt && $this->mitMeilensteinOderPause($folge));
        $template->set('timeline_mehr_url', '' === $mehrUrl ? '' : $this->seitenAnker->aufSeite($mehrUrl));
        $template->set('timeline_mehr_text', '' !== $mehrText ? $mehrText : 'Die ganze Geschichte');

        return $template->getResponse();
    }

    /** @param list<array<string, mixed>> $folge */
    private function mitMeilensteinOderPause(array $folge): bool
    {
        foreach ($folge as $e) {
            if (!empty($e['meilenstein']) || 'pause' === ($e['art'] ?? '')) {
                return true;
            }
        }

        return false;
    }
}
