<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kacheln (rg-20): Rahmen mit optionaler Überschrift für Kachel-Elemente (retro_kachel).
 * Die Kacheln stehen als Kindelemente darin und werden im Raster nebeneinander ausgegeben.
 */
#[AsContentElement('retro_kacheln', category: 'links', nestedFragments: ['allowedTypes' => ['retro_kachel']])]
class KachelnController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        return $template->getResponse();
    }
}
