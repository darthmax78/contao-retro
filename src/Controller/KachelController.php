<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Image\Studio\Figure;
use Contao\CoreBundle\Image\Studio\Studio;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kachel (rg-20): Marke, Bild, Linktext; die ganze Kachel ist der Link.
 *
 * Ziel: ein Abschnittstitel derselben Seite (kachel_abschnitt → #abschnitt-<id>, das
 * Sprungziel von sa_abschnitt) oder eine frei eingegebene Adresse (kachel_url).
 * Ohne Ziel wird die Kachel als einfache Box ausgegeben.
 */
#[AsContentElement('retro_kachel', category: 'links')]
class KachelController extends AbstractContentElementController
{
    /** Ohne gewählte Bildgröße: höchstens 600 px breit (Anzeige bis ca. 300 px, doppelt für scharfe Bildschirme) */
    private const BILD_GROESSE = [600, 400, 'box'];

    public function __construct(private readonly Studio $studio)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('marke', self::text((string) $model->kachel_marke));
        $template->set('linktext', self::text((string) $model->kachel_linktext));
        $template->set('href', $this->ziel($model));
        $template->set('bild', $this->bild($model));

        return $template->getResponse();
    }

    private function ziel(ContentModel $model): string
    {
        if ((int) $model->kachel_abschnitt > 0) {
            return '#abschnitt-'.(int) $model->kachel_abschnitt;
        }

        $url = self::text((string) $model->kachel_url);

        if (str_starts_with($url, '#') || str_starts_with($url, '/') || str_starts_with($url, '{{')
            || preg_match('~^https?://~i', $url)) {
            return $url;
        }

        return '';
    }

    private function bild(ContentModel $model): ?Figure
    {
        if (!$model->kachel_bild) {
            return null;
        }

        $groesse = StringUtil::deserialize($model->size, true);
        $gewaehlt = '' !== implode('', array_map('strval', $groesse));

        return $this->studio
            ->createFigureBuilder()
            ->fromUuid((string) $model->kachel_bild)
            ->setSize($gewaehlt ? $model->size : self::BILD_GROESSE)
            ->buildIfResourceExists()
        ;
    }

    private static function text(string $wert): string
    {
        return trim(StringUtil::decodeEntities($wert));
    }
}
