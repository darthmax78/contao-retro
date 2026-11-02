<?php

declare(strict_types=1);

namespace Darthmax\RetroBundle\Controller;

use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Contao\StringUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abschnittstitel: Überzeile, Überschrift und optionale Einleitung.
 *
 * Die Überschrift kommt aus dem Standardfeld „headline“ (Text + Ebene),
 * Überzeile und Einleitung aus den Feldern sa_kicker und sa_lead.
 * span#abschnitt-<id> ist das Sprungziel für die Sprungleiste.
 */
#[AsContentElement('sa_abschnitt', category: 'texts')]
class AbschnittController extends AbstractContentElementController
{
    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $template->set('kicker', trim(StringUtil::decodeEntities((string) $model->sa_kicker)));
        $template->set('anker', 'abschnitt-'.$model->id);
        $template->set('lead', trim(StringUtil::decodeEntities((string) $model->sa_lead)));

        return $template->getResponse();
    }
}
